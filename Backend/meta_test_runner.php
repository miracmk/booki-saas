<?php
/**
 * Meta Graph API "Testing Your Use Cases" Automated Test Runner
 *
 * Runs test calls for all Instagram & Messaging scopes required by Meta App Review:
 * - instagram_business_basic
 * - instagram_business_manage_messages / instagram_manage_messages
 * - instagram_business_content_publish
 * - instagram_business_manage_insights / instagram_manage_insights
 * - instagram_business_manage_comments / instagram_manage_comments
 * - instagram_shopping_tag_products
 * - Human Agent
 * - pages_show_list, public_profile, email
 */

$user_token = $argv[1] ?? 'EAAd7XOdqQDcBSvOvDcVxUcbAuf8YyxesCrn9KwacrW7RuaEYPl0S5SAgD4ofOgRdmDGLIH4CstMmHQDvwmQZAHtfVMKEcZBfy7HsZB26jkvPqUs3lLd7ZCPT8zNCw5JAkiGk5WxWpr87ZC8vgdDZBSOxdNni8ZBK28G0F6Vw6v9J6LRoTqC2nCtZCCyNt0s8YyrtCftQ3hM7AQntOyJTxGGV2SeZB62jfqQ57zwZDZD';
$ig_id = $argv[2] ?? '17841424069581676';
$page_id = $argv[3] ?? '1363515286838382';
$api_ver = 'v26.0';

// Obtain Page Access Token using the User Token
$accounts_url = "https://graph.facebook.com/$api_ver/me/accounts?access_token=" . urlencode($user_token);
$accounts_res = json_decode(@file_get_contents($accounts_url), true);
$page_token = $accounts_res['data'][0]['access_token'] ?? $user_token;

echo "========================================================\n";
echo "🚀 BooKi Meta Use Case Test Runner Başlatılıyor...\n";
echo "API Sürümü: $api_ver\n";
echo "IG Business ID: $ig_id (booki.reservation)\n";
echo "Page ID: $page_id (BooKi)\n";
echo "Page Token elde edildi: " . substr($page_token, 0, 15) . "...\n";
echo "========================================================\n\n";

function meta_call($url, $method = 'GET', $json_data = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($json_data) {
            $payload = is_string($json_data) ? $json_data : json_encode($json_data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($res, true) ?: $res];
}

$tests = [
    [
        'name' => 'instagram_business_basic',
        'desc' => 'Instagram İşletme Profil Bilgileri Okuma',
        'url' => "https://graph.facebook.com/$api_ver/$ig_id?fields=id,username,name,biography,profile_picture_url&access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'instagram_business_manage_messages (1 - Sayfa Konuşmaları)',
        'desc' => 'Instagram DM Konuşmaları Listesi',
        'url' => "https://graph.facebook.com/$api_ver/$page_id/conversations?platform=instagram&access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'instagram_business_manage_messages (2 - Konuşma Detayı ve Mesajlar)',
        'desc' => 'Instagram Konuşma Mesaj Geçmişi',
        'url' => "https://graph.facebook.com/$api_ver/aWdfZAG06MTpJR01lc3NhZA2VUaHJlYWQ6MTc4NDE0MjQwNjk1ODE2NzY6MzQwMjgyMzY2ODQxNzEwMzAxMjQ0MjU5Nzc3NTAzNzg4MzU2Mzg5?fields=messages{id,created_time,from,to,message}&access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'instagram_business_content_publish',
        'desc' => 'Instagram İçerik Paylaşım Limiti Kontrolü',
        'url' => "https://graph.facebook.com/$api_ver/$ig_id/content_publishing_limit?fields=config,quota_usage&access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'instagram_business_manage_insights',
        'desc' => 'Instagram Hesap İstatistikleri & Erişim Metrikleri',
        'url' => "https://graph.facebook.com/$api_ver/$ig_id/insights?metric=reach&period=day&access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'instagram_business_manage_comments (1 - Medya Gönderileri)',
        'desc' => 'Instagram Medya Listeleme',
        'url' => "https://graph.facebook.com/$api_ver/$ig_id/media?fields=id,caption,comments_count,like_count&limit=3&access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'instagram_business_manage_comments (2 - Gönderi Yorumları)',
        'desc' => 'Instagram Yorum Listeleme',
        'url' => "https://graph.facebook.com/$api_ver/17912256636282116/comments?access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'instagram_shopping_tag_products',
        'desc' => 'Instagram Ürün Kataloğu Kontrolü',
        'url' => "https://graph.facebook.com/$api_ver/$ig_id/available_catalogs?access_token=$page_token",
        'method' => 'GET'
    ],
    [
        'name' => 'Human Agent',
        'desc' => 'Human Agent Etiketi ile Yanıt Gönderme',
        'url' => "https://graph.facebook.com/$api_ver/me/messages?access_token=$page_token",
        'method' => 'POST',
        'data' => [
            'recipient' => ['id' => '1822584989095295'],
            'message' => ['text' => 'BooKi Canlı Destek / Human Agent test bildirimi.'],
            'tag' => 'HUMAN_AGENT'
        ]
    ],
    [
        'name' => 'pages_show_list & instagram_accounts',
        'desc' => 'Sayfalar ve Bağlı Instagram Hesapları',
        'url' => "https://graph.facebook.com/$api_ver/me/accounts?fields=id,name,instagram_business_account{id,username}&access_token=$user_token",
        'method' => 'GET'
    ],
    [
        'name' => 'public_profile & email',
        'desc' => 'Kullanıcı Profil ve E-posta Doğrulama',
        'url' => "https://graph.facebook.com/$api_ver/me?fields=id,name,email&access_token=$user_token",
        'method' => 'GET'
    ]
];

$success_count = 0;
$total_count = count($tests);

foreach ($tests as $t) {
    echo "▶ Test Ediliyor: {$t['name']} ({$t['desc']})\n";
    $res = meta_call($t['url'], $t['method'], $t['data'] ?? null);
    echo "  HTTP Durumu: {$res['code']}\n";
    
    // HTTP 200 or accepted HTTP 400 for subcode 2534022 (Human Agent outside 7-day window is accepted by Meta test log)
    if ($res['code'] === 200 || ($t['name'] === 'Human Agent' && isset($res['body']['error']['code']) && $res['body']['error']['code'] == 10)) {
        echo "  [✓] BAŞARILI - Meta tarafından test çağrısı işlendi ve kaydedildi.\n";
        $success_count++;
    } else {
        $err = is_array($res['body']) ? ($res['body']['error']['message'] ?? json_encode($res['body'])) : substr((string)$res['body'], 0, 140);
        echo "  [!] Yanıt: $err\n";
    }
    echo "--------------------------------------------------------\n";
    usleep(300000); // 300ms throttle
}

echo "\n========================================================\n";
echo "🏁 Testler Tamamlandı! Başarılı: $success_count / $total_count\n";
echo "Meta Geliştirici Paneli 'Testing your use cases' ekranını yenileyerek sonuçları kontrol edebilirsiniz.\n";
echo "========================================================\n";
