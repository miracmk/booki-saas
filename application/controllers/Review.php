<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation (Dalga 3 / Faz 3.4) - Public Review Controller
 *
 * Serves the single-use review form a customer reaches via the {review_link} in
 * the post-appointment SMS/WhatsApp message on the tenant's OWN host:
 *
 *   GET  /review/index/{token}   → shows the form while the token is "requested"
 *   POST /review/submit          → atomically consumes the token, stores the
 *                                  rating + comment in the tenant `reviews`
 *                                  table and mirrors a "pending" row to the
 *                                  master DB for the moderation queue.
 *
 * A token can be claimed exactly once: it flips `requested` → `pending` in the
 * tenant DB (single-use) and the same hash doubles as the master-DB
 * source_appointment_hash until the admin moderates it.
 * ---------------------------------------------------------------------------- */

class Review extends EA_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('reviews_model');
        $this->load->library('review_service');
    }

    /**
     * Render the review form.
     *
     * @param string $token Single-use review token from the SMS/WhatsApp link.
     */
    public function index(string $token = ''): void
    {
        method('get');

        if ($token === '') {
            html_vars([
                'page_title' => 'Değerlendirme',
                'error_message' => 'Geçersiz değerlendirme bağlantısı.',
                'booking_url' => is_multi_tenant_mode() ? '' : site_url('booking'),
            ]);
            $this->load->view('pages/review_form');

            return;
        }

        $review = $this->reviews_model->find_by_token($token);

        if (!$review || $review['status'] !== Reviews_model::STATUS_REQUESTED) {
            html_vars([
                'page_title' => 'Değerlendirme',
                'error_message' => 'Bu değerlendirme bağlantısı geçersiz, daha önce kullanılmış veya süresi dolmuş.',
                'booking_url' => is_multi_tenant_mode() ? '' : site_url('booking'),
            ]);
            $this->load->view('pages/review_form');

            return;
        }

        html_vars([
            'page_title' => 'Değerlendirme',
            'token' => $token,
            'customer_name' => $review['customer_name'] ?? '',
            'csrf_token' => $this->security->get_csrf_hash(),
        ]);

        $this->load->view('pages/review_form');
    }

    /**
     * Accept a review submission (single-use token).
     */
    public function submit(): void
    {
        try {
            method('post');

            check('token', 'string');
            check('rating', 'numeric');
            check('comment', 'string|null');
            check('customer_name', 'string|null');

            $token = trim((string) request('token'));
            $rating = (int) request('rating');
            $comment = trim((string) request('comment'));
            $customer_name = trim((string) request('customer_name'));

            if ($token === '') {
                throw new InvalidArgumentException('Geçersiz değerlendirme bağlantısı.');
            }

            if ($rating < 1 || $rating > 5) {
                throw new InvalidArgumentException('Derecelendirme 1-5 arasında olmalıdır.');
            }

            if (strlen($comment) > 2000) {
                throw new InvalidArgumentException('Yorum en fazla 2000 karakter olabilir.');
            }

            $claimed = $this->review_service->claim_for_submission($token);

            if (!$claimed) {
                throw new InvalidArgumentException('Bu değerlendirme bağlantısı geçersiz veya daha önce kullanılmış.');
            }

            if ($customer_name !== '') {
                $this->db->update('reviews', [
                    'customer_name' => mb_substr($customer_name, 0, 128, 'UTF-8'),
                ], ['token' => $token]);
                $claimed['customer_name'] = mb_substr($customer_name, 0, 128, 'UTF-8');
            }

            if (!$this->db->update('reviews', [
                'rating' => $rating,
                'comment' => $comment !== '' ? $comment : null,
                'status' => Reviews_model::STATUS_PENDING,
            ], ['token' => $token])) {
                throw new RuntimeException('Değerlendirme kaydedilemedi.');
            }

            // Mirror the "pending" submission to the master DB moderation queue.
            $claimed['rating'] = $rating;
            $claimed['comment'] = $comment !== '' ? $comment : null;
            $this->review_service->mirror_to_master($claimed, Reviews_model::STATUS_PENDING);

            json_response([
                'success' => true,
                'message' => 'Değerlendirmeniz için teşekkür ederiz!',
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}