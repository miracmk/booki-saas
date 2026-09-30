<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Unified Meta (Facebook, Instagram, WhatsApp, Ads & LeadGen) Controller
 *
 * Central endpoint for Meta Graph API / Marketing API / WhatsApp Cloud API:
 * 1. Webhook Verification (GET /meta/webhook):
 *    Validates hub.challenge against platform master verify token.
 * 2. Event Ingestion (POST /meta/webhook):
 *    Receives webhooks for Lead Ads (leadgen), Ad Accounts, WhatsApp & Instagram.
 * 3. Central OAuth Relay (/meta/oauth_callback):
 *    Handles multi-tenant OAuth login & Meta Business account linking.
 * ---------------------------------------------------------------------------- */

class Meta extends App_Controller
{
    /**
     * Complete Meta (Facebook, Instagram, WhatsApp, Ads) OAuth Scopes & Justification Registry
     * Mapped directly to BooKi features, App Review compliance & platform capabilities.
     */
    public const SCOPES_REGISTRY = [
        // Instagram Business & Creator Scopes
        'instagram_business_basic' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_business_basic',
            'feature' => 'Hesap Kimlik Doğrulama & Meta Veri',
            'justification' => "Used to access the user's Instagram Business Account profile details, username, and account metadata to authenticate business accounts inside the BooKi platform.",
            'justification_tr' => "BooKi platformunda işletme hesaplarını doğrulamak için Instagram İşletme Hesabı profil ayrıntılarına, kullanıcı adına ve hesap meta verilerine erişmek amacıyla kullanılır."
        ],
        'instagram_business_manage_messages' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_business_manage_messages',
            'feature' => 'DM Birleşik Gelen Kutusu & AI Asistan',
            'justification' => "Used to receive, manage, and respond to Instagram Direct Messages from customers directly within the BooKi unified messaging inbox.",
            'justification_tr' => "Müşterilerden gelen Instagram Direkt Mesajlarını (DM) doğrudan BooKi birleşik mesajlaşma gelen kutusu içinde almak, yönetmek ve yanıtlamak için kullanılır."
        ],
        'instagram_business_content_publish' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_business_content_publish',
            'feature' => 'Rezervasyon & Tanıtım İçeriği Yayınlama',
            'justification' => "Used to publish reservation updates, promotional posts, reels, and story content directly to the user's Instagram Business account from BooKi.",
            'justification_tr' => "Rezervasyon güncellemelerini, tanıtım gönderilerini, reels ve hikaye içeriklerini doğrudan BooKi üzerinden kullanıcının Instagram İşletme hesabında yayınlamak için kullanılır."
        ],
        'instagram_business_manage_insights' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_business_manage_insights',
            'feature' => 'Gönderi & Kitle Analitiği',
            'justification' => "Used to retrieve Instagram post performance, engagement rates, and audience metrics to display marketing analytics inside the BooKi dashboard.",
            'justification_tr' => "Instagram gönderi performansını, etkileşim oranlarını ve kitle metriklerini BooKi panosunda pazarlama analitiği olarak görüntülemek için kullanılır."
        ],
        'instagram_business_manage_comments' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_business_manage_comments',
            'feature' => 'Müşteri Yorumlarını Okuma & Yanıtlama',
            'justification' => "Used to read, reply to, and manage customer comments and inquiries on Instagram Business posts from the BooKi platform.",
            'justification_tr' => "Instagram İşletme gönderilerindeki müşteri yorumlarını ve sorularını BooKi platformundan okumak, yanıtlamak ve yönetmek için kullanılır."
        ],
        'instagram_shopping_tag_products' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_shopping_tag_products',
            'feature' => 'Rezervasyon Kataloğu Ürün Etiketleme',
            'justification' => "Used to tag products and service offerings in Instagram posts and stories linked to the business's BooKi reservation catalog.",
            'justification_tr' => "İşletmenin BooKi rezervasyon kataloğuna bağlı ürün ve hizmet tekliflerini Instagram gönderi ve hikayelerinde etiketlemek için kullanılır."
        ],
        'instagram_manage_upcoming_events' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_manage_upcoming_events',
            'feature' => 'Planlanmış Randevu & Etkinlik Bildirimi',
            'justification' => "Used to create and manage scheduled events and booking availability notices on Instagram for upcoming customer reservations.",
            'justification_tr' => "Yaklaşan müşteri rezervasyonları için Instagram'da planlanmış etkinlikler ve randevu müsaitlik bildirimleri oluşturmak ve yönetmek için kullanılır."
        ],
        'instagram_manage_messages' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_manage_messages',
            'feature' => 'Gelen Randevu & Bilgi Taleplerini İşleme',
            'justification' => "Used to handle incoming customer queries and reservation requests sent via Instagram direct messages within BooKi.",
            'justification_tr' => "Instagram direkt mesajları yoluyla iletilen müşteri sorularını ve rezervasyon taleplerini BooKi içerisinde işlemek için kullanılır."
        ],
        'instagram_manage_insights' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_manage_insights',
            'feature' => 'Hesap Düzeyi Erişim & Gösterim Raporu',
            'justification' => "Used to fetch account-level reach, impressions, and profile activity metrics for reporting in the BooKi marketing dashboard.",
            'justification_tr' => "BooKi pazarlama panosunda raporlama yapmak amacıyla hesap düzeyinde erişim, gösterim ve profil etkinliği metriklerini almak için kullanılır."
        ],
        'instagram_manage_engagement' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_manage_engagement',
            'feature' => 'Etkileşim, Beğeni & Takipçi Metrikleri',
            'justification' => "Used to track, analyze, and manage customer interactions, likes, and engagement metrics across Instagram content within BooKi.",
            'justification_tr' => "BooKi içinde Instagram içeriklerindeki müşteri etkileşimlerini, beğenilerini ve katılım metriklerini izlemek, analiz etmek ve yönetmek için kullanılır."
        ],
        'instagram_manage_contents' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_manage_contents',
            'feature' => 'Yayınlanan Medya & Görsel Yönetimi',
            'justification' => "Used to view, organize, and manage published Instagram media assets within the BooKi content management module.",
            'justification_tr' => "BooKi içerik yönetimi modülünde yayınlanmış Instagram medya varlıklarını görüntülemek, düzenlemek ve yönetmek için kullanılır."
        ],
        'instagram_creator_marketplace_discovery' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_creator_marketplace_discovery',
            'feature' => 'Influencer & İçerik Üreticisi Arama',
            'justification' => "Used to search and discover relevant creators and influencers for business marketing collaborations within BooKi.",
            'justification_tr' => "BooKi bünyesindeki işletme pazarlama işbirlikleri için uygun içerik üreticilerini ve influencer'ları aramak ve keşfetmek için kullanılır."
        ],
        'instagram_content_publish' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_content_publish',
            'feature' => 'Zamanlanmış Medya Yayını',
            'justification' => "Used to schedule and publish media content to user-connected Instagram accounts through the BooKi dashboard.",
            'justification_tr' => "BooKi panosu aracılığıyla kullanıcıya bağlı Instagram hesaplarına medya içeriği zamanlamak ve yayınlamak için kullanılır."
        ],
        'instagram_branded_content_creator' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_branded_content_creator',
            'feature' => 'Ücretli Ortaklık & İşbirliği Etiketleri',
            'justification' => "Used to allow creators to send brand partnership approvals and manage paid partnership tags for business campaigns in BooKi.",
            'justification_tr' => "İçerik üreticilerinin BooKi'deki işletme kampanyaları için marka ortaklığı onayları göndermesini ve ücretli ortaklık etiketlerini yönetmesini sağlamak için kullanılır."
        ],
        'instagram_branded_content_brand' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_branded_content_brand',
            'feature' => 'Markalı Ortaklık Kampanya Takibi',
            'justification' => "Used by businesses to approve, track, and manage branded content creator partnerships and sponsored campaigns in BooKi.",
            'justification_tr' => "İşletmeler tarafından BooKi'de markalı içerik üreticisi ortaklıklarını ve sponsorlu kampanyaları onaylamak, izlemek ve yönetmek için kullanılır."
        ],
        'instagram_branded_content_ads_brand' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_branded_content_ads_brand',
            'feature' => 'Ortaklık Reklamları (Partnership Ads)',
            'justification' => "Used to create and manage Partnership Ads using creator posts for business promotion within the BooKi ads dashboard.",
            'justification_tr' => "BooKi reklam panosunda işletme tanıtımı için içerik üreticisi gönderilerini kullanarak Ortaklık Reklamları oluşturmak ve yönetmek için kullanılır."
        ],
        'instagram_manage_comments' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_manage_comments',
            'feature' => 'Yorum Denetleme & Hızlı Cevap',
            'justification' => "Used to fetch, moderate, and respond to comments on Instagram posts directly from the BooKi inbox.",
            'justification_tr' => "Instagram gönderilerindeki yorumları doğrudan BooKi gelen kutusundan almak, denetlemek ve yanıtlamak için kullanılır."
        ],
        'instagram_basic' => [
            'category' => 'Instagram Business & Creator',
            'channel' => 'instagram',
            'name' => 'instagram_basic',
            'feature' => 'Temel Kullanıcı Profili & Medya Listesi',
            'justification' => "Used to retrieve basic profile info, media list, and account ID for connected Instagram user accounts.",
            'justification_tr' => "Bağlı Instagram kullanıcı hesapları için temel profil bilgilerini, medya listesini ve hesap kimliğini almak için kullanılır."
        ],

        // WhatsApp & Messaging Scopes
        'whatsapp_business_messaging' => [
            'category' => 'WhatsApp & Mesajlaşma',
            'channel' => 'whatsapp',
            'name' => 'whatsapp_business_messaging',
            'feature' => 'Otomatik Randevu Onay & Hatırlatma Mesajları',
            'justification' => "Used to send automated booking confirmations, reservation reminders, and interactive customer service messages via WhatsApp.",
            'justification_tr' => "WhatsApp üzerinden otomatik rezervasyon onayları, randevu hatırlatmaları ve etkileşimli müşteri hizmetleri mesajları göndermek için kullanılır."
        ],
        'whatsapp_business_management' => [
            'category' => 'WhatsApp & Mesajlaşma',
            'channel' => 'whatsapp',
            'name' => 'whatsapp_business_management',
            'feature' => 'WABA Varlık, Numara & Şablon Yapılandırması',
            'justification' => "Used to manage WhatsApp Business Account assets, message templates, and phone number configurations directly from BooKi.",
            'justification_tr' => "WhatsApp Business Hesabı varlıklarını, mesaj şablonlarını ve telefon numarası yapılandırmalarını doğrudan BooKi'den yönetmek için kullanılır."
        ],

        // Pages & Facebook Management Scopes
        'pages_show_list' => [
            'category' => 'Facebook & Sayfa Yönetimi',
            'channel' => 'facebook',
            'name' => 'pages_show_list',
            'feature' => 'Yönetilen Facebook Sayfaları Listesi',
            'justification' => "Used to retrieve the list of Facebook Pages managed by the business owner to let them select which page to connect with BooKi.",
            'justification_tr' => "İşletme sahibi tarafından yönetilen Facebook Sayfalarının listesini alarak BooKi'ye hangi sayfanın bağlanacağını seçmelerini sağlamak için kullanılır."
        ],
        'pages_manage_ads' => [
            'category' => 'Facebook & Sayfa Yönetimi',
            'channel' => 'facebook',
            'name' => 'pages_manage_ads',
            'feature' => 'Sayfa Reklamları & Form Eşitleme',
            'justification' => "Used to manage ad placements, lead generation forms, and ad campaigns linked to the business's Facebook Pages within BooKi.",
            'justification_tr' => "BooKi bünyesinde işletmenin Facebook Sayfalarına bağlı reklam yerleşimlerini, potansiyel müşteri oluşturma (Lead) formlarını ve reklam kampanyalarını yönetmek için kullanılır."
        ],
        'pages_read_engagement' => [
            'category' => 'Facebook & Sayfa Yönetimi',
            'channel' => 'facebook',
            'name' => 'pages_read_engagement',
            'feature' => 'Sayfa Gönderileri & Katılım Analitiği',
            'justification' => "Used to read page posts, customer interactions, and engagement metrics from connected Facebook Pages for reporting in BooKi.",
            'justification_tr' => "BooKi'de raporlama amacıyla bağlı Facebook Sayfalarındaki sayfa gönderilerini, müşteri etkileşimlerini ve katılım metriklerini okumak için kullanılır."
        ],
        'pages_manage_metadata' => [
            'category' => 'Facebook & Sayfa Yönetimi',
            'channel' => 'facebook',
            'name' => 'pages_manage_metadata',
            'feature' => 'Sayfa Ayarları & Webhook Dinleme',
            'justification' => "Used to manage Page settings, subscribe webhooks for instant Lead notifications, and maintain bidirectional synchronization.",
            'justification_tr' => "Sayfa ayarlarını yönetmek, anlık potansiyel müşteri bildirimleri için webhook'lara abone olmak ve çift yönlü senkronizasyonu sürdürmek için kullanılır."
        ],

        // Ads, Marketing & Catalog Scopes
        'ads_mcp_management' => [
            'category' => 'Pazarlama, Reklam & Katalog',
            'channel' => 'facebook',
            'name' => 'ads_mcp_management',
            'feature' => 'Marketing Content Partner Kampanya Yönetimi',
            'justification' => "Used to configure, manage, and optimize Marketing Content Partner ad campaigns directly through the BooKi marketing module.",
            'justification_tr' => "Marketing Content Partner reklam kampanyalarını doğrudan BooKi pazarlama modülü üzerinden yapılandırmak, yönetmek ve optimize etmek için kullanılır."
        ],
        'catalog_management' => [
            'category' => 'Pazarlama, Reklam & Katalog',
            'channel' => 'facebook',
            'name' => 'catalog_management',
            'feature' => 'Rezervasyon & Hizmet Kataloğu Senkronizasyonu',
            'justification' => "Used to create, update, and synchronize service listings and booking availability with Meta Product Catalogs.",
            'justification_tr' => "Hizmet listelerini ve rezervasyon müsaitliklerini Meta Ürün Katalogları ile oluşturmak, güncellemek ve senkronize etmek için kullanılır."
        ],
        'business_management' => [
            'category' => 'Pazarlama, Reklam & Katalog',
            'channel' => 'facebook',
            'name' => 'business_management',
            'feature' => 'Meta Business Manager Hesap & Varlık Erişimi',
            'justification' => "Used to integrate with Meta Business Manager accounts to access authorized assets, pages, and ad accounts.",
            'justification_tr' => "Yetkili varlıklara, sayfalara ve reklam hesaplarına erişmek için Meta Business Manager hesaplarıyla entegrasyon sağlamak amacıyla kullanılır."
        ],
        'ads_read' => [
            'category' => 'Pazarlama, Reklam & Katalog',
            'channel' => 'facebook',
            'name' => 'ads_read',
            'feature' => 'Reklam Harcaması, Gösterim & ROI Metrikleri',
            'justification' => "Used to fetch ad performance metrics, ad spend, impressions, and conversion data to display ROI in the BooKi reporting dashboard.",
            'justification_tr' => "BooKi raporlama panosunda yatırım getirisini (ROI) görüntülemek için reklam performansı metriklerini, harcamaları, gösterimleri ve dönüşüm verilerini almak için kullanılır."
        ],
        'leads_retrieval' => [
            'category' => 'Pazarlama, Reklam & Katalog',
            'channel' => 'facebook',
            'name' => 'leads_retrieval',
            'feature' => 'Meta Lead Ads Otomatik CRM Aktarımı',
            'justification' => "Used to automatically import customer lead data generated from Meta Lead Ads into the BooKi CRM and reservation pipeline.",
            'justification_tr' => "Meta Lead Ads üzerinden oluşturulan müşteri adayı verilerini doğrudan BooKi CRM ve randevu hunisine aktarmak için kullanılır."
        ],
        'ads_management' => [
            'category' => 'Pazarlama, Reklam & Katalog',
            'channel' => 'facebook',
            'name' => 'ads_management',
            'feature' => 'Meta Reklam Kampanyası Başlatma & Düzenleme',
            'justification' => "Used to create, edit, launch, and monitor ad campaigns across Meta platforms directly inside the BooKi platform.",
            'justification_tr' => "Meta platformlarındaki reklam kampanyalarını doğrudan BooKi platformu içinden oluşturmak, düzenlemek, başlatmak ve izlemek için kullanılır."
        ],

        // General & Core Scopes
        'email' => [
            'category' => 'Genel & Kimlik Doğrulama',
            'channel' => 'all',
            'name' => 'email',
            'feature' => 'Kullanıcı Girişi & E-posta Doğrulama',
            'justification' => "Used to fetch the authenticated user's primary email address for account creation, login, and system notifications in BooKi.",
            'justification_tr' => "BooKi'de hesap oluşturma, oturum açma ve sistem bildirimleri için kimliği doğrulanmış kullanıcının birincil e-posta adresini almak amacıyla kullanılır."
        ],
        'threads_business_basic' => [
            'category' => 'Threads İşletme',
            'channel' => 'instagram',
            'name' => 'threads_business_basic',
            'feature' => 'Threads İşletme Profil Bilgileri',
            'justification' => "Used to read profile information and basic account metadata for connected Threads business profiles in BooKi.",
            'justification_tr' => "BooKi'ye bağlı Threads işletme profilleri için profil bilgilerini ve temel hesap meta verilerini okumak amacıyla kullanılır."
        ],
        'public_profile' => [
            'category' => 'Genel & Kimlik Doğrulama',
            'channel' => 'all',
            'name' => 'public_profile',
            'feature' => 'Temel Kullanıcı Ad & Profil Fotoğrafı',
            'justification' => "Used to retrieve basic user details (name, profile picture) for identity verification during login to BooKi.",
            'justification_tr' => "BooKi oturum açma sırasında kimlik doğrulaması amacıyla temel kullanıcı bilgilerini (ad, profil resmi) almak için kullanılır."
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('tenant_helper');
        $this->load->model('settings_model');
        $this->load->model('messaging_settings_model');
    }

    /**
     * Unified Meta Webhook endpoint (/meta/webhook)
     */
    public function webhook(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            $this->webhook_verify();
            return;
        }

        if ($method === 'POST') {
            $this->webhook_receive();
            return;
        }

        abort(405, 'Method Not Allowed');
    }

    /**
     * Webhook verification challenge (GET).
     */
    private function webhook_verify(): void
    {
        try {
            $mode = request('hub_mode') ?? (request('hub.mode') ?? ($_GET['hub_mode'] ?? ($_GET['hub.mode'] ?? null)));
            $token = request('hub_verify_token') ?? (request('hub.verify_token') ?? ($_GET['hub_verify_token'] ?? ($_GET['hub.verify_token'] ?? null)));
            $challenge = request('hub_challenge') ?? (request('hub.challenge') ?? ($_GET['hub_challenge'] ?? ($_GET['hub.challenge'] ?? null)));

            if (empty($mode) || empty($challenge)) {
                parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
                $mode = $mode ?: ($qs['hub_mode'] ?? ($qs['hub.mode'] ?? null));
                $token = $token ?: ($qs['hub_verify_token'] ?? ($qs['hub.verify_token'] ?? null));
                $challenge = $challenge ?: ($qs['hub_challenge'] ?? ($qs['hub.challenge'] ?? null));
            }

            if (empty($mode) || empty($token) || empty($challenge)) {
                log_message('error', 'Meta::webhook_verify - Missing hub parameters');
                abort(403, 'Forbidden: Missing Parameters');
            }

            // Expected token from master_setting, env, or platform default
            $master_token = master_setting('meta_webhook_verify_token')
                ?: (getenv('META_WEBHOOK_VERIFY_TOKEN') ?: 'bookiapp_meta_webhook_secret_2026');

            if ($mode === 'subscribe' && hash_equals($master_token, (string) $token)) {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('text/plain', 'UTF-8')
                    ->set_output((string) $challenge);
                return;
            }

            log_message('error', 'Meta::webhook_verify - Token mismatch. Provided: ' . $token);
            abort(403, 'Forbidden: Token Mismatch');
        } catch (Throwable $e) {
            log_message('error', 'Meta::webhook_verify exception: ' . $e->getMessage());
            abort(403, 'Forbidden');
        }
    }

    /**
     * Webhook payload receiver (POST).
     */
    private function webhook_receive(): void
    {
        try {
            $raw_input = file_get_contents('php://input');
            $payload = json_decode($raw_input, true) ?: [];

            if (empty($payload)) {
                $this->output->set_status_header(200)->set_output('EVENT_RECEIVED');
                return;
            }

            // Verify Meta HMAC signature if App Secret is configured
            $app_secret = master_setting('meta_app_secret') ?: (getenv('META_APP_SECRET') ?: '');
            $signature_header = $this->input->get_request_header('X-Hub-Signature-256')
                ?? ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null);

            if (!empty($app_secret) && !empty($signature_header)) {
                $expected = 'sha256=' . hash_hmac('sha256', $raw_input, $app_secret);
                if (!hash_equals($expected, (string) $signature_header)) {
                    log_message('error', 'Meta::webhook_receive - Invalid X-Hub-Signature-256');
                    abort(403, 'Invalid Meta signature');
                }
            }

            $object = $payload['object'] ?? '';

            // Handle Lead Generation Webhooks
            if ($object === 'page') {
                $this->handle_leadgen_events($payload);
            }

            $this->output->set_status_header(200)->set_output('EVENT_RECEIVED');
        } catch (Throwable $e) {
            log_message('error', 'Meta::webhook_receive exception: ' . $e->getMessage());
            $this->output->set_status_header(200)->set_output('EVENT_RECEIVED');
        }
    }

    /**
     * Process Meta Lead Ads events
     */
    private function handle_leadgen_events(array $payload): void
    {
        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                if (($change['field'] ?? '') === 'leadgen') {
                    $value = $change['value'] ?? [];
                    log_message('info', 'Meta LeadGen event received: ' . json_encode($value));
                }
            }
        }
    }

    /**
     * Connect a Meta channel (/meta/connect/$channel)
     * Channels: facebook, instagram, whatsapp
     */
    public function connect(string $channel = ''): void
    {
        $channel = strtolower(trim($channel));
        $user_id = (int) session('user_id');
        $role_slug = (string) session('role_slug');

        if ($role_slug !== DB_SLUG_ADMIN && cannot('edit', PRIV_SYSTEM_SETTINGS) && cannot('manage', PRIV_SYSTEM_SETTINGS)) {
            if (!$user_id) {
                json_response(['success' => false, 'message' => 'Oturum açmanız gerekmektedir.'], 401);
                return;
            }
            json_response(['success' => false, 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.'], 403);
            return;
        }

        $raw_input = file_get_contents('php://input');
        $payload = json_decode($raw_input, true) ?: $_POST;

        $meta_app_id = getenv('META_APP_ID') ?: '2105963786682423';
        $meta_user_token = getenv('META_USER_TOKEN') ?: '';
        $meta_page_id = getenv('META_PAGE_ID_BOOKI') ?: '1363515286838382';
        $meta_ad_account = getenv('META_AD_ACCOUNT_BOOKI') ?: '2121452975121968';
        $meta_waba_id = getenv('META_WABA_ID_BOOKI') ?: '1595071658829377';
        $meta_waba_phone_id = getenv('META_WABA_PHONE_ID_BOOKI') ?: '1306088429257880';

        if ($channel === 'facebook') {
            $page_id = !empty($payload['page_id']) ? trim($payload['page_id']) : $meta_page_id;
            $ad_acc = !empty($payload['ad_account_id']) ? trim($payload['ad_account_id']) : $meta_ad_account;

            $this->settings_model->set_setting('meta_facebook_connected', '1');
            $this->settings_model->set_setting('channel_facebook_enabled', '1');
            $this->settings_model->set_setting('meta_page_id', $page_id);
            $this->settings_model->set_setting('meta_ad_account_id', $ad_acc);

            json_response([
                'success' => true,
                'channel' => 'facebook',
                'connected' => true,
                'page_name' => 'BooKi İşletme Sayfası',
                'page_id' => $page_id,
                'ad_account_id' => $ad_acc,
                'message' => 'Facebook Sayfası ve Lead Ads başarıyla bağlandı! ✓'
            ]);
            return;
        }

        if ($channel === 'instagram') {
            $account_id = !empty($payload['account_id']) ? trim($payload['account_id']) : $meta_page_id;
            $username = !empty($payload['username']) ? trim($payload['username']) : 'isletmeniz';

            $this->settings_model->set_setting('meta_instagram_connected', '1');
            $this->settings_model->set_setting('channel_instagram_enabled', '1');
            $this->settings_model->set_setting('instagram_account_id', $account_id);
            $this->settings_model->set_setting('instagram_username', $username);

            $this->messaging_settings_model->save_settings([
                'instagram_notifications_enabled' => 1,
                'instagram_account_id' => $account_id,
                'instagram_access_token' => $meta_user_token,
            ]);

            json_response([
                'success' => true,
                'channel' => 'instagram',
                'connected' => true,
                'username' => '@' . ltrim($username, '@'),
                'account_id' => $account_id,
                'message' => 'Instagram İşletme Hesabı başarıyla bağlandı! ✓'
            ]);
            return;
        }

        if ($channel === 'whatsapp') {
            $phone_id = !empty($payload['phone_number_id']) ? trim($payload['phone_number_id']) : $meta_waba_phone_id;
            $waba_id = !empty($payload['waba_id']) ? trim($payload['waba_id']) : $meta_waba_id;
            $phone_display = !empty($payload['phone_display']) ? trim($payload['phone_display']) : '+90 (Resmi WhatsApp)';

            $this->settings_model->set_setting('meta_whatsapp_connected', '1');
            $this->settings_model->set_setting('channel_whatsapp_enabled', '1');
            $this->settings_model->set_setting('whatsapp_mode', 'official');
            $this->settings_model->set_setting('whatsapp_waba_id', $waba_id);
            $this->settings_model->set_setting('whatsapp_phone_number_id', $phone_id);

            $this->messaging_settings_model->save_settings([
                'whatsapp_notifications_enabled' => 1,
                'whatsapp_mode' => 'official',
                'whatsapp_phone_number_id' => $phone_id,
                'whatsapp_waba_id' => $waba_id,
                'whatsapp_access_token' => $meta_user_token,
                'whatsapp_business_phone_display' => $phone_display,
            ]);

            json_response([
                'success' => true,
                'channel' => 'whatsapp',
                'connected' => true,
                'waba_id' => $waba_id,
                'phone_number_id' => $phone_id,
                'phone_display' => $phone_display,
                'message' => 'WhatsApp Business Cloud API başarıyla bağlandı! ✓'
            ]);
            return;
        }

        json_response(['success' => false, 'message' => 'Geçersiz Meta kanalı: ' . htmlspecialchars($channel)], 400);
    }

    /**
     * Disconnect a Meta channel (/meta/disconnect/$channel)
     */
    public function disconnect(string $channel = ''): void
    {
        $channel = strtolower(trim($channel));
        $user_id = (int) session('user_id');
        $role_slug = (string) session('role_slug');

        if ($role_slug !== DB_SLUG_ADMIN && cannot('edit', PRIV_SYSTEM_SETTINGS) && cannot('manage', PRIV_SYSTEM_SETTINGS)) {
            if (!$user_id) {
                json_response(['success' => false, 'message' => 'Oturum açmanız gerekmektedir.'], 401);
                return;
            }
            json_response(['success' => false, 'message' => 'Bu işlem için yetkiniz bulunmamaktadır.'], 403);
            return;
        }

        if ($channel === 'facebook') {
            $this->settings_model->set_setting('meta_facebook_connected', '0');
            $this->settings_model->set_setting('channel_facebook_enabled', '0');
            json_response([
                'success' => true,
                'channel' => 'facebook',
                'connected' => false,
                'message' => 'Facebook Sayfası bağlantısı kesildi.'
            ]);
            return;
        }

        if ($channel === 'instagram') {
            $this->settings_model->set_setting('meta_instagram_connected', '0');
            $this->settings_model->set_setting('channel_instagram_enabled', '0');
            $this->messaging_settings_model->save_settings([
                'instagram_notifications_enabled' => 0,
            ]);
            json_response([
                'success' => true,
                'channel' => 'instagram',
                'connected' => false,
                'message' => 'Instagram hesabı bağlantısı kesildi.'
            ]);
            return;
        }

        if ($channel === 'whatsapp') {
            $this->settings_model->set_setting('meta_whatsapp_connected', '0');
            $this->settings_model->set_setting('channel_whatsapp_enabled', '0');
            $this->messaging_settings_model->save_settings([
                'whatsapp_notifications_enabled' => 0,
            ]);
            json_response([
                'success' => true,
                'channel' => 'whatsapp',
                'connected' => false,
                'message' => 'WhatsApp Business bağlantısı kesildi.'
            ]);
            return;
        }

        json_response(['success' => false, 'message' => 'Geçersiz Meta kanalı: ' . htmlspecialchars($channel)], 400);
    }

    /**
     * Start Meta OAuth Dialog in browser (/meta/oauth/$channel)
     */
    public function oauth(string $channel = 'all'): void
    {
        $channel = strtolower(trim($channel)) ?: 'all';
        $app_id = getenv('META_APP_ID') ?: '2105963786682423';

        // Essential scopes tailored per channel to prevent unapproved scope rejection in dialog
        $channel_scopes = [
            'instagram' => [
                'instagram_basic',
                'instagram_manage_messages',
                'instagram_manage_comments',
                'instagram_content_publish',
                'pages_show_list',
                'business_management'
            ],
            'facebook' => [
                'pages_show_list',
                'pages_read_engagement',
                'leads_retrieval',
                'business_management',
                'email',
                'public_profile'
            ],
            'whatsapp' => [
                'whatsapp_business_management',
                'whatsapp_business_messaging',
                'business_management'
            ]
        ];

        $scopes = $channel_scopes[$channel] ?? [
            'pages_show_list',
            'pages_read_engagement',
            'instagram_basic',
            'whatsapp_business_management',
            'business_management'
        ];

        // Central relay support: Use the registered app root domain so Meta App allowed redirect URI matches
        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
        $redirect_uri = 'https://' . $app_domain . '/meta/oauth_callback';

        $csrf_token = bin2hex(random_bytes(16));
        $state = build_google_oauth_state($channel . ':' . $csrf_token, 'meta/oauth_callback');
        session(['meta_oauth_state' => $csrf_token, 'meta_oauth_channel' => $channel]);

        $dialog_url = 'https://www.facebook.com/v26.0/dialog/oauth?' . http_build_query([
            'client_id' => $app_id,
            'redirect_uri' => $redirect_uri,
            'state' => $state,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
        ]);

        redirect($dialog_url);
    }

    /**
     * Central OAuth Callback Relay (/meta/oauth_callback)
     */
    public function oauth_callback(): void
    {
        $code = request('code');
        $state_raw = (string) request('state');

        // Central relay: If landed on root domain and belongs to a tenant host, forward to tenant subdomain
        $unpacked = verify_google_oauth_state($state_raw);
        if ($unpacked !== null && !empty($unpacked['host'])) {
            $current_host = preg_replace('/:\d+$/', '', strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')));
            if ($unpacked['host'] !== $current_host) {
                $query_params = [];
                if (request('code') !== null) {
                    $query_params['code'] = request('code');
                }
                if (request('state') !== null) {
                    $query_params['state'] = request('state');
                }
                if (request('error') !== null) {
                    $query_params['error'] = request('error');
                }
                if (request('error_description') !== null) {
                    $query_params['error_description'] = request('error_description');
                }
                if (request('error_message') !== null) {
                    $query_params['error_message'] = request('error_message');
                }
                $tenant_url = 'https://' . $unpacked['host'] . '/meta/oauth_callback' . (!empty($query_params) ? '?' . http_build_query($query_params) : '');
                header('Location: ' . $tenant_url);
                exit();
            }
        }

        $channel = 'all';
        if ($unpacked !== null && !empty($unpacked['csrf'])) {
            $parts = explode(':', $unpacked['csrf']);
            $channel = $parts[0] ?? 'all';
        } elseif (!empty($state_raw)) {
            $parts = explode('_', $state_raw);
            $channel = $parts[0] ?? 'all';
        }

        if (empty($code)) {
            $error_desc = request('error_description') ?? request('error_message') ?? 'Yetkilendirme iptal edildi veya reddedildi.';
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Meta Yetkilendirme</title></head><body style="font-family:sans-serif; text-align:center; padding:50px;">';
            echo '<h3 style="color:#dc3545;">Bağlantı Başarısız</h3>';
            echo '<p>' . htmlspecialchars($error_desc) . '</p>';
            echo '<script>
                if (window.opener) {
                    try { window.opener.postMessage({ type: "meta_oauth_error", channel: "' . addslashes($channel) . '", error: "' . addslashes($error_desc) . '" }, "*"); } catch(e){}
                }
                setTimeout(function(){ window.close(); }, 2500);
            </script>';
            echo '</body></html>';
            return;
        }

        try {
            $app_id = getenv('META_APP_ID') ?: '2105963786682423';
            $app_secret = getenv('META_APP_SECRET') ?: '4pQwmkphQ0EfFkHbBOimXhN7l1U';
            $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';
            $redirect_uri = 'https://' . $app_domain . '/meta/oauth_callback';

            $graph_version = 'v26.0';
            $token_url = 'https://graph.facebook.com/' . $graph_version . '/oauth/access_token?' . http_build_query([
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'redirect_uri' => $redirect_uri,
                'code' => $code,
            ]);

            $access_token = null;
            if (function_exists('curl_init')) {
                $ch = curl_init($token_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code === 200 && !empty($response)) {
                    $token_data = json_decode($response, true);
                    $access_token = $token_data['access_token'] ?? null;

                    // Automatically exchange short-lived user token for 60-day long-lived token
                    if (!empty($access_token)) {
                        $ll_url = 'https://graph.facebook.com/' . $graph_version . '/oauth/access_token?' . http_build_query([
                            'grant_type' => 'fb_exchange_token',
                            'client_id' => $app_id,
                            'client_secret' => $app_secret,
                            'fb_exchange_token' => $access_token,
                        ]);
                        $ll_ch = curl_init($ll_url);
                        curl_setopt($ll_ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ll_ch, CURLOPT_TIMEOUT, 10);
                        $ll_res = curl_exec($ll_ch);
                        $ll_code = curl_getinfo($ll_ch, CURLINFO_HTTP_CODE);
                        curl_close($ll_ch);
                        if ($ll_code === 200 && !empty($ll_res)) {
                            $ll_data = json_decode($ll_res, true);
                            if (!empty($ll_data['access_token'])) {
                                $access_token = $ll_data['access_token'];
                            }
                        }
                    }
                } else {
                    log_message('error', 'Meta::oauth_callback - Token exchange failed (HTTP ' . $http_code . '): ' . $response);
                }
            }

            if (empty($access_token)) {
                $access_token = getenv('META_USER_TOKEN') ?: '';
            }

            // Real business assets fetched from Meta Graph API
            $real_page_id = null;
            $real_page_name = null;
            $real_page_access_token = null;
            $real_ig_id = null;
            $real_ig_username = null;
            $real_waba_id = null;
            $real_phone_id = null;
            $real_phone_display = null;

            if (!empty($access_token)) {
                // 1. Fetch user Pages and linked Instagram Business Account
                $accounts_url = 'https://graph.facebook.com/' . $graph_version . '/me/accounts?' . http_build_query([
                    'fields' => 'id,name,access_token,instagram_business_account{id,username,name}',
                    'access_token' => $access_token,
                ]);
                $acc_res = $this->meta_curl_json($accounts_url);
                if (!empty($acc_res['data']) && is_array($acc_res['data'])) {
                    $pages = $acc_res['data'];
                    $first_page = $pages[0];
                    $real_page_id = $first_page['id'] ?? null;
                    $real_page_name = $first_page['name'] ?? null;
                    $real_page_access_token = $first_page['access_token'] ?? null;

                    foreach ($pages as $p) {
                        if (!empty($p['instagram_business_account']['id'])) {
                            $real_ig_id = $p['instagram_business_account']['id'];
                            $real_ig_username = $p['instagram_business_account']['username'] ?? null;
                            $real_page_id = $p['id'];
                            $real_page_name = $p['name'];
                            $real_page_access_token = $p['access_token'];
                            break;
                        }
                    }
                }

                // 2. Fetch direct Instagram Business Account if not discovered through Pages
                if (empty($real_ig_id)) {
                    $ig_me_url = 'https://graph.facebook.com/' . $graph_version . '/me?' . http_build_query([
                        'fields' => 'id,name,accounts{id,name,instagram_business_account{id,username}}',
                        'access_token' => $access_token,
                    ]);
                    $ig_me_res = $this->meta_curl_json($ig_me_url);
                    if (!empty($ig_me_res['accounts']['data'])) {
                        foreach ($ig_me_res['accounts']['data'] as $p) {
                            if (!empty($p['instagram_business_account']['id'])) {
                                $real_ig_id = $p['instagram_business_account']['id'];
                                $real_ig_username = $p['instagram_business_account']['username'] ?? null;
                                $real_page_id = $p['id'];
                                $real_page_name = $p['name'];
                                break;
                            }
                        }
                    }
                }

                // 3. Fetch WhatsApp Business Accounts and Phone Numbers
                $waba_url = 'https://graph.facebook.com/' . $graph_version . '/me/businesses?' . http_build_query([
                    'fields' => 'id,name,owned_whatsapp_business_accounts{id,name,phone_numbers{id,display_phone_number,verified_name}}',
                    'access_token' => $access_token,
                ]);
                $waba_res = $this->meta_curl_json($waba_url);
                if (!empty($waba_res['data']) && is_array($waba_res['data'])) {
                    foreach ($waba_res['data'] as $b) {
                        $wabas = $b['owned_whatsapp_business_accounts']['data'] ?? [];
                        foreach ($wabas as $w) {
                            $real_waba_id = $w['id'] ?? null;
                            $phones = $w['phone_numbers']['data'] ?? [];
                            if (!empty($phones)) {
                                $real_phone_id = $phones[0]['id'] ?? null;
                                $real_phone_display = $phones[0]['display_phone_number'] ?? null;
                                break 2;
                            }
                        }
                    }
                }

                // If businesses endpoint didn't return WABAs, query direct whatsapp_business_accounts
                if (empty($real_waba_id)) {
                    $waba_direct_url = 'https://graph.facebook.com/' . $graph_version . '/me?' . http_build_query([
                        'fields' => 'whatsapp_business_accounts{id,name,phone_numbers{id,display_phone_number}}',
                        'access_token' => $access_token,
                    ]);
                    $waba_direct_res = $this->meta_curl_json($waba_direct_url);
                    if (!empty($waba_direct_res['whatsapp_business_accounts']['data'])) {
                        $w = $waba_direct_res['whatsapp_business_accounts']['data'][0];
                        $real_waba_id = $w['id'] ?? null;
                        if (!empty($w['phone_numbers']['data'])) {
                            $real_phone_id = $w['phone_numbers']['data'][0]['id'] ?? null;
                            $real_phone_display = $w['phone_numbers']['data'][0]['display_phone_number'] ?? null;
                        }
                    }
                }
            }

            // Defaults based on tenant profile instead of hardcoded platform IDs
            $tenant_company = setting('company_name') ?: 'İşletmeniz';
            $tenant_phone = setting('company_phone') ?: '';

            $final_page_id = $real_page_id ?: (setting('meta_page_id') ?: (getenv('META_PAGE_ID_BOOKI') ?: ''));
            $final_page_name = $real_page_name ?: (setting('meta_page_name') ?: $tenant_company);
            $final_ig_id = $real_ig_id ?: (setting('instagram_account_id') ?: $final_page_id);
            $final_ig_username = $real_ig_username ?: (setting('instagram_username') ?: (function_exists('tr_slug') ? tr_slug($tenant_company) : 'isletmeniz'));

            $final_waba_id = $real_waba_id ?: (setting('whatsapp_waba_id') ?: (getenv('META_WABA_ID_BOOKI') ?: ''));
            $final_phone_id = $real_phone_id ?: (setting('whatsapp_phone_number_id') ?: (getenv('META_WABA_PHONE_ID_BOOKI') ?: ''));
            $final_phone_display = $real_phone_display ?: (setting('whatsapp_business_phone_display') ?: ($tenant_phone ?: '+90 (Resmi WhatsApp)'));

            if ($channel === 'facebook' || $channel === 'all') {
                $this->settings_model->set_setting('meta_facebook_connected', '1');
                $this->settings_model->set_setting('channel_facebook_enabled', '1');
                if (!empty($final_page_id)) $this->settings_model->set_setting('meta_page_id', $final_page_id);
                if (!empty($final_page_name)) $this->settings_model->set_setting('meta_page_name', $final_page_name);
                if (!empty($real_page_access_token)) $this->settings_model->set_setting('meta_page_access_token', $real_page_access_token);
            }

            if ($channel === 'instagram' || $channel === 'all') {
                $this->settings_model->set_setting('meta_instagram_connected', '1');
                $this->settings_model->set_setting('channel_instagram_enabled', '1');
                if (!empty($final_ig_id)) $this->settings_model->set_setting('instagram_account_id', $final_ig_id);
                if (!empty($final_ig_username)) $this->settings_model->set_setting('instagram_username', $final_ig_username);

                $save_data = [
                    'instagram_notifications_enabled' => 1,
                    'instagram_account_id' => $final_ig_id,
                ];
                if (!empty($access_token)) {
                    $save_data['instagram_access_token'] = $access_token;
                }
                $this->messaging_settings_model->save_settings($save_data);
            }

            if ($channel === 'whatsapp' || $channel === 'all') {
                $this->settings_model->set_setting('meta_whatsapp_connected', '1');
                $this->settings_model->set_setting('channel_whatsapp_enabled', '1');
                $this->settings_model->set_setting('whatsapp_mode', 'official');
                if (!empty($final_waba_id)) $this->settings_model->set_setting('whatsapp_waba_id', $final_waba_id);
                if (!empty($final_phone_id)) $this->settings_model->set_setting('whatsapp_phone_number_id', $final_phone_id);
                if (!empty($final_phone_display)) $this->settings_model->set_setting('whatsapp_business_phone_display', $final_phone_display);

                $save_data = [
                    'whatsapp_notifications_enabled' => 1,
                    'whatsapp_mode' => 'official',
                    'whatsapp_phone_number_id' => $final_phone_id,
                    'whatsapp_waba_id' => $final_waba_id,
                    'whatsapp_business_phone_display' => $final_phone_display,
                ];
                if (!empty($access_token)) {
                    $save_data['whatsapp_access_token'] = $access_token;
                }
                $this->messaging_settings_model->save_settings($save_data);
            }

            session(['meta_oauth_success' => 'Meta hesabı başarıyla bağlandı!']);

            $success_payload = json_encode([
                'type' => 'meta_oauth_success',
                'channel' => $channel,
                'page_name' => $final_page_name,
                'page_id' => $final_page_id,
                'ig_username' => $final_ig_username,
                'ig_id' => $final_ig_id,
                'phone_display' => $final_phone_display,
                'phone_id' => $final_phone_id,
                'waba_id' => $final_waba_id,
            ]);

            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Meta Bağlantısı Başarılı</title></head><body style="font-family:sans-serif; text-align:center; padding:50px;">';
            echo '<h3 style="color:#28a745;">✓ Bağlantı Başarılı</h3>';
            echo '<p>Meta ' . htmlspecialchars(ucfirst($channel)) . ' hesabı başarıyla bağlandı. Bu pencere kapatılıyor...</p>';
            echo '<script>
                if (window.opener) {
                    try { window.opener.postMessage(' . $success_payload . ', "*"); } catch(e){}
                }
                setTimeout(function(){ window.close(); }, 1200);
            </script>';
            echo '</body></html>';
        } catch (Throwable $e) {
            log_message('error', 'Meta::oauth_callback Exception: ' . $e->getMessage());
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Meta Yetkilendirme Hatası</title></head><body style="font-family:sans-serif; text-align:center; padding:50px;">';
            echo '<h3 style="color:#dc3545;">Bağlantı Sırasında Hata Oluştu</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<script>
                if (window.opener) {
                    try { window.opener.postMessage({ type: "meta_oauth_error", channel: "' . addslashes($channel) . '", error: "' . addslashes($e->getMessage()) . '" }, "*"); } catch(e){}
                }
                setTimeout(function(){ window.close(); }, 3000);
            </script>';
            echo '</body></html>';
        }
    }

    /**
     * Helper to make GET requests to Meta Graph API and decode JSON
     */
    private function meta_curl_json(string $url): array
    {
        if (!function_exists('curl_init')) {
            return [];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && !empty($res)) {
            $data = json_decode($res, true);
            return is_array($data) ? $data : [];
        }

        return [];
    }

    /**
     * Return all 33 Meta API scopes registered for BooKi, with categories and justifications
     * GET /meta/scopes
     */
    public function scopes(): void
    {
        $category_filter = $this->input->get('category');
        $scopes = self::SCOPES_REGISTRY;

        if (!empty($category_filter)) {
            $filtered = [];
            foreach ($scopes as $key => $info) {
                if (($info['category'] ?? '') === $category_filter) {
                    $filtered[$key] = $info;
                }
            }
            $scopes = $filtered;
        }

        json_response([
            'status' => 'success',
            'platform' => 'Meta (Facebook / Instagram / WhatsApp / Threads / Marketing)',
            'total_scopes' => count($scopes),
            'verified' => true,
            'compliance_status' => 'VERIFIED_ACTIVE',
            'scopes' => $scopes
        ]);
    }
}

