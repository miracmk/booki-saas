<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - AI agent conversation memory model.
 *
 * Long-term memory + archive for the admin AI Asistan (migration 155). The
 * ACTIVE conversation lives in the PHP session. When a conversation is closed
 * (idle timeout / manual reset / session-cap overflow) it becomes its OWN row
 * in ai_agent_conversations (per user) - raw messages are archived into
 * ai_agent_messages under that row and a per-conversation summary is stored
 * on it. Long-term memory is therefore per-customer (id_users) and built from
 * the RECENT closed conversations' summaries (see get_recent_summaries()),
 * never a single global blob.
 *
 * @package Models
 */
class Ai_agent_conversations_model extends App_Model
{
    /** How many recent closed-conversation summaries form the memory context. */
    public const MEMORY_SUMMARY_LIMIT = 5;

    /**
     * Create a fresh conversation row for this user (called when a conversation
     * is closed and archived).
     */
    public function create(int $user_id): int
    {
        $this->db->insert('ai_agent_conversations', [
            'id_users' => $user_id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->insert_id();
    }

    /**
     * Summaries of this user's most recent CLOSED conversations (newest first).
     * This is the per-customer long-term memory injected into each turn.
     */
    public function get_recent_summaries(int $user_id, int $limit = self::MEMORY_SUMMARY_LIMIT): array
    {
        $rows = $this->db
            ->select('summary')
            ->where('id_users', $user_id)
            ->where('summary IS NOT NULL')
            ->where('summary !=', '')
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get('ai_agent_conversations')
            ->result_array();

        return array_map(static fn (array $r): string => (string) $r['summary'], $rows);
    }

    public function find(int $id): ?array
    {
        $row = $this->db->get_where('ai_agent_conversations', ['id' => $id])->row_array();

        return $row ?: null;
    }

    public function get_messages(int $conversation_id): array
    {
        return $this->db
            ->where('id_conversation', $conversation_id)
            ->order_by('id', 'ASC')
            ->get('ai_agent_messages')
            ->result_array();
    }

    /**
     * Append messages (user, assistant with tool_calls, tool results) in one
     * atomic insert set. Rows MUST be inserted in-order; assistant rows may
     * carry their own 'tool_calls' array (multi-turn archive), falling back
     * to the $tool_calls argument for single-turn inserts. Tool messages
     * carry tool_call_id/tool_name so providers can reconstruct the pair.
     */
    public function append_messages(int $conversation_id, array $messages, array $tool_calls = []): void
    {
        if (empty($messages)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $batch = [];

        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $tool_calls_json = null;
            if ($role === 'assistant') {
                $row_tool_calls = !empty($m['tool_calls']) ? $m['tool_calls'] : $tool_calls;
                if (!empty($row_tool_calls)) {
                    $tool_calls_json = json_encode($row_tool_calls, JSON_UNESCAPED_UNICODE);
                }
            }

            $tool_call_id = null;
            $tool_name = null;
            if ($role === 'tool') {
                $tool_call_id = $m['tool_call_id'] ?? null;
                $tool_name = $m['name'] ?? null;
            }

            $batch[] = [
                'id_conversation' => $conversation_id,
                'role' => in_array($role, ['user', 'assistant', 'tool'], true) ? $role : 'user',
                'content' => (string) ($m['content'] ?? ''),
                'tool_calls' => $tool_calls_json,
                'tool_call_id' => $tool_call_id,
                'tool_name' => $tool_name,
                'created_at' => $now,
            ];
        }

        $this->db->insert_batch('ai_agent_messages', $batch);

        $this->db->update('ai_agent_conversations', ['updated_at' => $now], ['id' => $conversation_id]);
    }

    public function update_summary(int $conversation_id, string $summary): void
    {
        $this->db->update('ai_agent_conversations', [
            'summary' => $summary,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $conversation_id]);
    }
}
