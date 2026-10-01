<?php extend('layouts/booking_layout'); ?>

<?php section('content'); ?>

<?php if (empty(vars('available_services')) || empty(vars('available_providers'))): ?>

<?php component('booking_no_services_message'); ?>

<?php else: ?>

<!-- Booking Cancellation Frame -->

<?php component('booking_cancellation_frame', [
    'manage_mode' => vars('manage_mode'),
    'appointment_data' => vars('appointment_data'),
    'display_delete_personal_information' => vars('display_delete_personal_information'),
]); ?>

<!-- Select Service & Provider -->

<?php component('booking_type_step', ['available_services' => vars('available_services')]); ?>

<!-- Pick An Appointment Date -->

<?php component('booking_time_step', ['grouped_timezones' => vars('grouped_timezones')]); ?>

<!-- Enter Customer Information -->

<?php component('booking_info_step', [
    'display_first_name' => vars('display_first_name'),
    'require_first_name' => vars('require_first_name'),
    'display_last_name' => vars('display_last_name'),
    'require_last_name' => vars('require_last_name'),
    'display_email' => vars('display_email'),
    'require_email' => vars('require_email'),
    'display_phone_number' => vars('display_phone_number'),
    'require_phone_number' => vars('require_phone_number'),
    'display_address' => vars('display_address'),
    'require_address' => vars('require_address'),
    'display_city' => vars('display_city'),
    'require_city' => vars('require_city'),
    'display_zip_code' => vars('display_zip_code'),
    'require_zip_code' => vars('require_zip_code'),
    'display_notes' => vars('display_notes'),
    'require_notes' => vars('require_notes'),
]); ?>

<!-- Appointment Data Confirmation -->

<?php component('booking_final_step', [
    'manage_mode' => vars('manage_mode'),
    'display_terms_and_conditions' => vars('display_terms_and_conditions'),
    'display_privacy_policy' => vars('display_privacy_policy'),
]); ?>

<?php endif; ?>

<!-- AI Chat Widget (conditional) -->

<?php if (vars('ai_assistant_enabled')): ?>
    <?php component('ai_chat_widget', [
        'ai_assistant_enabled' => vars('ai_assistant_enabled'),
    ]); ?>
<?php endif; ?>

<!-- Modal: Müşteriye Özel Onam & Sözleşme Okuma Penceresi -->
<div class="modal fade" id="modal-booking-consent-viewer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="booking-consent-viewer-title">
                        <i class="fas fa-file-signature text-danger me-2"></i>Bilgilendirilmiş Onam & Hizmet Sözleşmesi
                    </h5>
                    <span class="badge bg-success-subtle text-success border border-success-subtle small mt-1">
                        <i class="fas fa-user-check me-1"></i>Kişiselleştirilmiş Yasal Metin
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="booking-consent-viewer-body" style="font-size: 0.95rem; line-height: 1.6;">
                <!-- Doldurulmuş onam metni buraya gelecek -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Metni Okudum & Kapat</button>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<script src="<?= asset_url('assets/js/utils/lang.js') ?>"></script>
<script src="<?= asset_url('assets/js/utils/ui.js') ?>"></script>
<script src="<?= asset_url('assets/js/http/booking_http_client.js') ?>"></script>
<script src="<?= asset_url('assets/js/pages/booking.js') ?>"></script>

<?php end_section('scripts'); ?>
