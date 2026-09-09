<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Automation Engine (Dalga 3 / Faz 3.2, 2026-09-08).
 *
 * WHEN( event ) IF( conditions ) THEN( actions ).
 *
 *   evaluate($event, $ctx)  - called by the event hooks; loads the enabled rules
 *                             for $event, runs their condition matchers, and
 *                             executes matching actions.
 *
 * Actions are dispatched through Communication_hub (message) for notifications
 * and recorded into automation_log for auditability.
 *
 * A rule may carry a `customer.appointment_count` condition; this value is
 * derived on the fly from the customer id in the context (see
 * customer_appointment_count()) so templates like "VIP 5+" work.
 * ---------------------------------------------------------------------------- */

class Automation_engine
{
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('communication_hub');
    }

    public const EVENTS = [
        'appointment_created',
        'appointment_completed',
        'appointment_cancelled',
    ];

    /**
     * Run the engine for one event. Enriches the context with derived virtual
     * fields (customer.appointment_count) before any rule runs so both the
     * condition matcher and the message templates can use them.
     */
    public function evaluate(string $event, array $ctx): void
    {
        if (!in_array($event, self::EVENTS, true)) {
            return;
        }

        try {
            $total = $this->customer_appointment_count($ctx);
            if ($total !== null && !isset($ctx['appointment_count'])) {
                $ctx['appointment_count'] = $total;
            }

            $rules = $this->get_rules($event);

            foreach ($rules as $rule) {
                $this->run($rule, $event, $ctx);
            }
        } catch (Throwable $e) {
            log_message('error', 'Automation_engine::evaluate(' . $event . ') failed: ' . $e->getMessage());
        }
    }

    /**
     * Run a single rule (used by evaluate and by the console runner).
     */
    public function run(array $rule, string $event, array $ctx): void
    {
        try {
            if ((int) $rule['enabled'] !== 1 || !$this->matches($rule, $ctx)) {
                return;
            }

            $actions = $this->decode_actions($rule);

            $run = 0;
            foreach ($actions as $action) {
                $run += $this->execute_action($action, $event, $ctx) ? 1 : 0;
            }

            $this->log($rule, $event, $ctx, $run);
        } catch (Throwable $e) {
            log_message('error', 'Automation_engine::run(rule ' . $rule['id'] . ') failed: ' . $e->getMessage());
        }
    }

    /**
     * Load enabled rules for an event.
     */
    public function get_rules(string $event): array
    {
        return $this->CI->db
            ->from('automation_rules')
            ->where('event', $event)
            ->where('enabled', 1)
            ->order_by('id', 'asc')
            ->get()
            ->result_array();
    }

    /**
     * All rules (any state), newest first.
     */
    public function get_all_rules(): array
    {
        return $this->CI->db
            ->from('automation_rules')
            ->order_by('id', 'asc')
            ->get()
            ->result_array();
    }

    public function set_enabled(int $id, bool $enabled): bool
    {
        return $this->CI->db->update(
            'automation_rules',
            ['enabled' => $enabled ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $id],
        );
    }

    /* ------------------------------------------------------------------ */

    /**
     * Decode the actions JSON.
     */
    protected function decode_actions(array $rule): array
    {
        $raw = (string) ($rule['actions'] ?? '[]');
        $actions = json_decode($raw, true);

        return is_array($actions) ? $actions : [];
    }

    /**
     * Execute one action descriptor. Returns true if it did work.
     */
    protected function execute_action(array $action, string $event, array $ctx): bool
    {
        $type = (string) ($action['type'] ?? '');

        switch ($type) {
            case 'message':
                return $this->execute_message($action, $ctx);

            case 'note':
                return $this->execute_note($action, $ctx);

            case 'log':
                return true;

            default:
                return false;
        }
    }

    /**
     * Message action - build a fake "rule" template around the action text and
     * hand it to Communication_hub::deliver().
     */
    protected function execute_message(array $action, array $ctx): bool
    {
        $recipient_key = (string) ($action['recipient'] ?? 'customer');
        $channels = (string) ($action['channels'] ?? 'sms');
        $subject = (string) ($action['subject'] ?? '');
        $text = (string) ($action['text'] ?? '');

        if ($text === '') {
            return false;
        }

        $channel_list = array_values(array_filter(array_map('trim', explode(',', $channels))));

        if ($channel_list === []) {
            return false;
        }

        // Reuse hub template rendering so {service_name}/{customer_name}/... work.
        $rule = [
            'event' => '',
            'message' => $text,
            'subject' => $subject,
        ];

        $rendered_text = $this->CI->communication_hub->render_message($rule, $ctx);
        $rendered_subject = $this->CI->communication_hub->render_subject($rule, $ctx);

        $this->CI->communication_hub->deliver(
            $recipient_key,
            $ctx,
            $rendered_subject,
            $rendered_text,
            $channel_list,
        );

        return true;
    }

    /**
     * Note action - append to the appointment_notes log when an appointment id
     * is available in context.
     */
    protected function execute_note(array $action, array $ctx): bool
    {
        $appointment_id = (int) ($ctx['appointment']['id'] ?? 0);
        $text = (string) ($action['text'] ?? '');

        if ($appointment_id <= 0 || $text === '') {
            return false;
        }

        if (!$this->CI->db->table_exists('appointment_notes')) {
            return false;
        }

        $this->CI->db->insert('appointment_notes', [
            'appointment_id' => $appointment_id,
            'note' => $text,
            'date_created' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Condition matching against the context using dot-notation field paths.
     */
    protected function matches(array $rule, array $ctx): bool
    {
        $raw = (string) ($rule['conditions'] ?? '');
        if (trim($raw) === '') {
            return true;
        }

        $conditions = json_decode($raw, true);
        if (!is_array($conditions) || $conditions === []) {
            return true;
        }

        foreach ($conditions as $cond) {
            if (!$this->matches_one($cond, $ctx)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Match a single condition { field, op, value }.
     */
    protected function matches_one(array $cond, array $ctx): bool
    {
        $field = (string) ($cond['field'] ?? '');
        $op = (string) ($cond['op'] ?? '=');

        // Special derived virtual field: customer.appointment_count
        if ($field === 'customer.appointment_count') {
            $actual = $this->customer_appointment_count($ctx);
        } else {
            $actual = $this->lookup($field, $ctx);
        }

        switch ($op) {
            case '=':
                return (string) $actual === (string) ($cond['value'] ?? '');

            case '!=':
                return (string) $actual !== (string) ($cond['value'] ?? '');

            case '>':
                return is_numeric($actual) && (float) $actual > (float) ($cond['value'] ?? 0);

            case '>=':
                return is_numeric($actual) && (float) $actual >= (float) ($cond['value'] ?? 0);

            case '<':
                return is_numeric($actual) && (float) $actual < (float) ($cond['value'] ?? 0);

            case '<=':
                return is_numeric($actual) && (float) $actual <= (float) ($cond['value'] ?? 0);

            case 'in':
                $values = array_filter(array_map('trim', explode(',', (string) ($cond['value'] ?? ''))));
                return in_array((string) $actual, $values, true);

            case 'weekdays_only':
                return $this->is_weekday($actual);

            default:
                return false;
        }
    }

    /**
     * Resolve a dot.notation path against the context.
     */
    protected function lookup(string $path, array $ctx): mixed
    {
        $parts = array_values(array_filter(explode('.', $path)));

        if ($parts === []) {
            return null;
        }

        // First segment must map to a ctx group key.
        $root = array_shift($parts);
        $value = $ctx[$root] ?? null;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Virtual field: total number of appointments the customer has attended
     * (status=3, i.e. completed). If no customer id, returns null.
     */
    protected function customer_appointment_count(array $ctx): mixed
    {
        $customer_id = (int) ($ctx['customer']['id'] ?? 0);
        if ($customer_id <= 0) {
            return null;
        }

        if (!$this->CI->db->table_exists('appointments') || !$this->CI->db->field_exists('status', 'appointments')) {
            return null;
        }

        return (int) $this->CI->db
            ->from('appointments')
            ->where('id_users_customer', $customer_id)
            ->where('status', 3)
            ->count_all_results();
    }

    /**
     * Whether a datetime string falls on a weekday (Mon-Fri).
     */
    protected function is_weekday(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return false;
        }

        $dow = (int) date('N', $ts); // 1=Mon .. 7=Sun

        return $dow >= 1 && $dow <= 5;
    }

    /**
     * Append an automation_log row.
     */
    protected function log(array $rule, string $event, array $ctx, int $actions_run): void
    {
        $this->CI->db->insert('automation_log', [
            'rule_id' => (int) $rule['id'],
            'event' => $event,
            'appointment_id' => (int) ($ctx['appointment']['id'] ?? 0) ?: null,
            'actions_run' => $actions_run,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}