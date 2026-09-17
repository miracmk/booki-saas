<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi (Dalga 3 / Faz 3.4) - Reviews Model
 *
 * Tenant-side review request source of truth:
 *
 *   reviews  – one row per review request issued by the automation engine
 *              (`requested` → `pending` on customer submission, then
 *              `published` / `rejected` on admin moderation). The single-use
 *              `token` doubles as the master-DB `source_appointment_hash` when a
 *              review is published, so marketplace aggregates and our own-facing
 *              records share one unforgeable identity.
 *
 * The "requested" row is created by Automation_engine::execute_review_request();
 * this model owns the rest of the lifecycle (claims, submission, moderation).
 * ---------------------------------------------------------------------------- */

class Reviews_model extends EA_Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_PENDING,
        self::STATUS_PUBLISHED,
        self::STATUS_REJECTED,
    ];

    protected array $casts = [
        'id' => 'integer',
        'appointment_id' => 'integer',
        'id_users_customer' => 'integer',
        'rating' => 'integer',
        'moderated_by' => 'integer',
    ];

    /**
     * Find a review request by its single-use token.
     *
     * @return array|null The review row, or null when the token is unknown.
     */
    public function find_by_token(string $token): ?array
    {
        $review = $this->db->get_where('reviews', ['token' => $token])->row_array();

        if (!$review) {
            return null;
        }

        $this->cast($review);

        return $review;
    }

    /**
     * BooKi (2026-09-17) - resolve the short alias (`/r/{code}`) sent over SMS/WhatsApp back
     * to its row, so Review::short() can redirect to the real token URL.
     */
    public function find_by_short_code(string $short_code): ?array
    {
        $review = $this->db->get_where('reviews', ['short_code' => $short_code])->row_array();

        if (!$review) {
            return null;
        }

        $this->cast($review);

        return $review;
    }

    /**
     * All review rows (newest first), optionally filtered by status.
     *
     * @param string|null $status One of self::STATUSES.
     */
    public function get(?string $status = null): array
    {
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $this->db->where('status', $status);
        }

        $reviews = $this->db
            ->order_by('id', 'DESC')
            ->get('reviews')
            ->result_array();

        foreach ($reviews as &$review) {
            $this->cast($review);
        }

        return $reviews;
    }

    /**
     * Number of reviews in each status (dashboard counters).
     *
     * @return array<string,int>
     */
    public function counts(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        $rows = $this->db
            ->select('status, COUNT(*) AS c')
            ->group_by('status')
            ->get('reviews')
            ->result_array();

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }

        return $counts;
    }

    /**
     * Claim a requested review for submission (atomic single-use).
     *
     * Flips `requested` → `pending` only when the row is still `requested`, so a
     * token can never be submitted twice.
     *
     * @return bool True when the claim succeeded.
     */
    public function claim_for_submission(string $token): bool
    {
        $this->db
            ->where('token', $token)
            ->where('status', self::STATUS_REQUESTED)
            ->set('status', self::STATUS_PENDING)
            ->set('submitted_at', date('Y-m-d H:i:s'))
            ->update('reviews');

        return $this->db->affected_rows() === 1;
    }

    /**
     * Persist the submitted rating + comment on a pending review.
     */
    public function save_submission(string $token, int $rating, string $comment): bool
    {
        return $this->db->update('reviews', [
            'rating' => $rating,
            'comment' => $comment !== '' ? $comment : null,
        ], ['token' => $token]);
    }

    /**
     * Moderate a review. Returns the updated row when the target status is valid and
     * the row existed; throws otherwise.
     *
     * @return array The updated review row.
     * @throws InvalidArgumentException
     */
    public function moderate(int $review_id, string $status, int $moderated_by): array
    {
        if (!in_array($status, self::STATUSES, true) || $status === self::STATUS_REQUESTED) {
            throw new InvalidArgumentException('Geçersiz moderasyon durumu: ' . $status);
        }

        $review = $this->db->get_where('reviews', ['id' => $review_id])->row_array();

        if (!$review) {
            throw new InvalidArgumentException('Yorum isteği bulunamadı: ' . $review_id);
        }

        $this->db->update('reviews', [
            'status' => $status,
            'moderated_at' => date('Y-m-d H:i:s'),
            'moderated_by' => $moderated_by,
        ], ['id' => $review_id]);

        $this->cast($review);

        return $review;
    }
}