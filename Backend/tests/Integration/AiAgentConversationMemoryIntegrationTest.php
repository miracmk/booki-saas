<?php declare(strict_types=1);

namespace Tests\Integration;

use Tests\TenantTestCase;

class AiAgentConversationMemoryIntegrationTest extends TenantTestCase
{
    private array $createdConvIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::require_multi_tenant();

        $tenant = self::db()->get_where('tenants', ['subdomain' => 'demo-guzellik'])->row_array();
        if ($tenant) {
            self::connect_tenant($tenant);
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdConvIds)) {
            $ci = self::ci();
            $ci->db->where_in('id_conversation', $this->createdConvIds)->delete('ai_agent_messages');
            $ci->db->where_in('id', $this->createdConvIds)->delete('ai_agent_conversations');
        }

        parent::tearDown();
    }

    public function testConversationLifecycleAndMemoryStorage(): void
    {
        $ci = self::ci();
        $ci->load->model('ai_agent_conversations_model');
        $model = $ci->ai_agent_conversations_model;

        $userId = 888000 + random_int(1, 99999);

        // 1. Create a conversation
        $convId = $model->create($userId);
        $this->createdConvIds[] = $convId;
        $this->assertGreaterThan(0, $convId);

        // 2. Append messages including tool calls and tool responses
        $messages = [
            ['role' => 'user', 'content' => 'Yarın için randevuları listeler misin?'],
            [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => [
                    [
                        'id' => 'call_123',
                        'type' => 'function',
                        'function' => [
                            'name' => 'list_appointments',
                            'arguments' => '{"from_date":"2026-09-20","to_date":"2026-09-20"}',
                        ],
                    ],
                ],
            ],
            [
                'role' => 'tool',
                'tool_call_id' => 'call_123',
                'name' => 'list_appointments',
                'content' => '{"count":1,"appointments":[{"id":10,"customer":{"name":"Ayşe Demir"}}]}',
            ],
            [
                'role' => 'assistant',
                'content' => 'Yarın saat 14:00 için Ayşe Demir adına 1 randevu bulunmaktadır.',
            ],
        ];

        $model->append_messages($convId, $messages);

        // Verify messages in DB
        $storedMessages = $model->get_messages($convId);
        $this->assertCount(4, $storedMessages);
        $this->assertSame('user', $storedMessages[0]['role']);
        $this->assertSame('Yarın için randevuları listeler misin?', $storedMessages[0]['content']);

        $this->assertSame('assistant', $storedMessages[1]['role']);
        $this->assertNotEmpty($storedMessages[1]['tool_calls']);
        $decodedToolCalls = json_decode($storedMessages[1]['tool_calls'], true);
        $this->assertSame('call_123', $decodedToolCalls[0]['id']);

        $this->assertSame('tool', $storedMessages[2]['role']);
        $this->assertSame('call_123', $storedMessages[2]['tool_call_id']);
        $this->assertSame('list_appointments', $storedMessages[2]['tool_name']);

        // 3. Update summary
        $summaryText = 'Müşteri yarınki randevuları sordu (1 adet Ayşe Demir randevusu aktarıldı).';
        $model->update_summary($convId, $summaryText);

        $conv = $model->find($convId);
        $this->assertNotNull($conv);
        $this->assertSame($summaryText, $conv['summary']);

        // 4. Create another conversation with summary
        $convId2 = $model->create($userId);
        $this->createdConvIds[] = $convId2;
        $summaryText2 = 'İkinci oturum özeti: Müşteri çalışma saatlerini sorguladı.';
        $model->update_summary($convId2, $summaryText2);

        // 5. Test get_recent_summaries (newest first)
        $summaries = $model->get_recent_summaries($userId, 5);
        $this->assertCount(2, $summaries);
        $this->assertSame($summaryText2, $summaries[0]);
        $this->assertSame($summaryText, $summaries[1]);
    }

    public function testSummarizeHistoryProducesValidSummary(): void
    {
        $ci = self::ci();
        $ci->load->library('ai_agent_client');

        $history = [
            ['role' => 'user', 'content' => 'Merhaba, randevu almak istiyorum.'],
            ['role' => 'assistant', 'content' => 'Tabii, hangi hizmet için istersiniz?'],
            ['role' => 'user', 'content' => 'Cilt bakımı için lütfen.'],
        ];

        $summary = $ci->ai_agent_client->summarize_history($history);
        $this->assertNotEmpty($summary);
        $this->assertMatchesRegularExpression('/(cilt bakımı|Cilt bakımı|randevu)/iu', $summary);
    }

    public function testSimulatedSessionIdleTimeoutAndArchival(): void
    {
        $ci = self::ci();
        $ci->load->model('ai_agent_conversations_model');
        $ci->load->library('ai_agent_client');

        $userId = 777000 + random_int(1, 99999);

        // Simulate session state before idle timeout
        $sessionHistory = [
            ['role' => 'user', 'content' => 'Lazer epilasyon fiyatı nedir?'],
            ['role' => 'assistant', 'content' => 'Lazer epilasyon paketimiz seans başına 1500 TL dir.'],
        ];

        // Simulate close_conversation routine
        $convId = $ci->ai_agent_conversations_model->create($userId);
        $this->createdConvIds[] = $convId;

        $ci->ai_agent_conversations_model->append_messages($convId, $sessionHistory);
        $summary = $ci->ai_agent_client->summarize_history($sessionHistory);
        $ci->ai_agent_conversations_model->update_summary($convId, $summary);

        // Verify that conversation was properly archived in DB
        $archivedConv = $ci->ai_agent_conversations_model->find($convId);
        $this->assertNotNull($archivedConv);
        $this->assertSame($userId, (int) $archivedConv['id_users']);
        $this->assertNotEmpty($archivedConv['summary']);

        $archivedMessages = $ci->ai_agent_conversations_model->get_messages($convId);
        $this->assertCount(2, $archivedMessages);
        $this->assertSame('user', $archivedMessages[0]['role']);
        $this->assertSame('assistant', $archivedMessages[1]['role']);

        // Next turn retrieves summaries for this user
        $recentSummaries = $ci->ai_agent_conversations_model->get_recent_summaries($userId);
        $this->assertCount(1, $recentSummaries);
        $this->assertSame($archivedConv['summary'], $recentSummaries[0]);
    }

    public function testChannelResponderPreservesMultiTurnHistory(): void
    {
        $ci = self::ci();
        $ci->load->library('ai_channel_responder');

        $chatId = 'test_tg_' . random_int(100000, 999999);

        // Simulate existing conversation in telegram_messages table
        $ci->db->insert('telegram_messages', [
            'chat_id' => $chatId,
            'direction' => 'in',
            'message' => 'Klasik masaj 60 dakika almak istiyorum.',
            'created_at' => date('Y-m-d H:i:s', time() - 30),
        ]);
        $msgId1 = $ci->db->insert_id();

        $ci->db->insert('telegram_messages', [
            'chat_id' => $chatId,
            'direction' => 'out',
            'message' => 'Tabii, randevunuzu kaydetmemiz için telefon numaranızı paylaşabilir misiniz?',
            'created_at' => date('Y-m-d H:i:s', time() - 20),
        ]);
        $msgId2 = $ci->db->insert_id();

        $ci->db->insert('telegram_messages', [
            'chat_id' => $chatId,
            'direction' => 'in',
            'message' => '0555 123 45 67',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $msgId3 = $ci->db->insert_id();

        try {
            $reply = $ci->ai_channel_responder->respond('telegram', $chatId, '0555 123 45 67', null);
            $this->assertNotEmpty($reply);
            // Must NOT just say "Merhaba! Size nasıl yardımcı olabilirim?" like a blank greeting;
            // it must reference the massage / appointment / proposal or thank for the phone number
            $this->assertDoesNotMatchRegularExpression('/^Merhaba!?\s*(Salon Flora.*)?\s*Size nasıl yardımcı olabilirim\?*$/iu', trim((string) $reply));
        } finally {
            $ci->db->where_in('id', [$msgId1, $msgId2, $msgId3])->delete('telegram_messages');
        }
    }
}

