<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Notification bell feed (Dalga 4, 2026-09-12).
 *
 * Deliberately NOT named `Notifications` - that name is already taken by
 * application/libraries/Notifications.php (the outbound SENDING library).
 * This controller is read-only and only ever normalizes already-existing data
 * (whatsapp_messages today) into a small feed for the header bell dropdown -
 * see next steps in docs/SESSION_NOTES.md for extending this to other event
 * types (automation_log, appointment_created, ...) once there's a real need.
 *
 * Read/unread state is kept client-side (localStorage "last seen id") - no
 * new DB column, no migration, matching the "sensible default now, extend
 * later" instruction this feature shipped under.
 *
 * @package Controllers
 */
class Notifications_feed extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('whatsapp_messages_model');
    }

    /**
     * Recent notification-shaped events, newest first.
     */
    public function recent(): void
    {
        try {
            method('get');

            if (cannot('view', PRIV_CUSTOMERS)) {
                throw new RuntimeException('You do not have the required permissions for this task.');
            }

            $messages = $this->whatsapp_messages_model->get_recent(20);

            $items = array_map(static function (array $row): array {
                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                $title = $name !== '' ? $name : $row['wa_id'];

                return [
                    'id' => (int) $row['id'],
                    'type' => $row['direction'] === 'in' ? 'whatsapp_in' : 'whatsapp_out',
                    'title' => $title,
                    'subtitle' => mb_strimwidth((string) $row['message'], 0, 90, '…'),
                    'timestamp' => $row['created_at'],
                    'customer_id' => $row['id_users'] ? (int) $row['id_users'] : null,
                ];
            }, $messages);

            json_response(['success' => true, 'items' => $items]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
