<?php
// AI Chat Widget Component
// Display a floating microphone button for voice-to-text transcription.
// Only rendered when ai_assistant_enabled setting is true.

if (!isset($ai_assistant_enabled)) {
    $ai_assistant_enabled = false;
}

if (!$ai_assistant_enabled) {
    return; // Do not render if AI Assistant is disabled
}
?>

<div id="ai-chat-widget" class="ai-chat-widget" role="region" aria-label="AI Assistant Widget">
    <!-- Floating Widget Button -->
    <button id="ai-widget-toggle" class="ai-widget-btn" title="Click to enable voice input (microphone required)"
            type="button" aria-expanded="false" aria-controls="ai-widget-panel">
        <i class="fas fa-microphone"></i>
    </button>

    <!-- Widget Panel -->
    <div id="ai-widget-panel" class="ai-widget-panel" hidden aria-hidden="true">
        <div class="ai-widget-header">
            <h3 class="ai-widget-title">AI Assistant (Beta)</h3>
            <button id="ai-widget-close" class="ai-widget-close" type="button" aria-label="Close AI Assistant">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="ai-widget-content">
            <!-- Chat History Area -->
            <div id="ai-chat-history" class="ai-chat-history">
                <div class="ai-chat-message ai-message-system">
                    <p>Merhaba! Randevu almak için seçim yapmanıza yardımcı olmaya hazırım.</p>
                </div>
            </div>

            <!-- Message Input & Voice Controls -->
            <div class="ai-widget-input-section">
                <div class="ai-widget-input-controls">
                    <input type="text" id="ai-message-input" class="ai-message-input" placeholder="Mesaj yazın veya ses kaydı yapın..." />
                    <button id="ai-send-btn" class="ai-send-btn" type="button" title="Mesaj gönder">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>

                <div class="ai-widget-controls">
                    <button id="ai-record-btn" class="ai-record-btn" type="button" title="Start recording">
                        <i class="fas fa-microphone"></i>
                        <span>Ses Kaydı</span>
                    </button>
                    <span id="ai-record-time" class="ai-record-time" hidden>00:00</span>
                </div>
            </div>

            <div id="ai-widget-error" class="ai-widget-error" hidden>
                <!-- Error messages displayed here -->
            </div>
        </div>
    </div>
</div>

<style>
    .ai-chat-widget {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1050;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .ai-widget-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .ai-widget-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .ai-widget-btn:focus {
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }

    .ai-widget-panel {
        position: absolute;
        bottom: 80px;
        right: 0;
        width: 320px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 40px rgba(0, 0, 0, 0.16);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: 500px;
    }

    .ai-widget-panel[aria-hidden="false"] {
        display: flex;
    }

    .ai-widget-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ai-widget-title {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .ai-widget-close {
        background: transparent;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
    }

    .ai-widget-close:hover {
        transform: scale(1.2);
    }

    .ai-widget-content {
        padding: 16px;
        overflow-y: auto;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ai-chat-history {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 8px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-height: 200px;
        max-height: 350px;
    }

    .ai-chat-message {
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 14px;
        line-height: 1.4;
        max-width: 100%;
        word-wrap: break-word;
    }

    .ai-message-user {
        background: #667eea;
        color: white;
        margin-left: 24px;
        text-align: right;
    }

    .ai-message-assistant {
        background: #f5f5f5;
        color: #333;
        margin-right: 24px;
    }

    .ai-message-system {
        background: #e8f4f8;
        color: #0066cc;
        margin: 0 12px;
        text-align: center;
    }

    .ai-widget-input-section {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding-top: 8px;
        border-top: 1px solid #e0e0e0;
    }

    .ai-widget-input-controls {
        display: flex;
        gap: 8px;
    }

    .ai-message-input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
    }

    .ai-message-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    }

    .ai-send-btn {
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 14px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }

    .ai-send-btn:hover {
        background: #5568d3;
    }

    .ai-send-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .ai-widget-controls {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .ai-record-btn {
        flex: 1;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .ai-record-btn:hover {
        background: #5568d3;
    }

    .ai-record-btn:active {
        transform: scale(0.98);
    }

    .ai-record-btn[disabled] {
        background: #ccc;
        cursor: not-allowed;
    }

    .ai-record-btn i {
        font-size: 12px;
    }

    .ai-record-btn.recording {
        background: #ef4444;
        animation: pulse 1s infinite;
    }

    .ai-record-time {
        font-size: 14px;
        font-weight: 500;
        color: #ef4444;
        min-width: 35px;
        text-align: right;
    }

    .ai-widget-error {
        background: #fee;
        border: 1px solid #fcc;
        border-radius: 6px;
        padding: 12px;
        font-size: 13px;
        color: #c00;
        margin-top: 8px;
    }

    .ai-payment-container {
        background: #f0f4ff;
        border: 2px solid #667eea;
        border-radius: 8px;
        padding: 12px;
        margin: 8px 0;
        font-size: 13px;
    }

    .ai-payment-notice {
        font-weight: 600;
        color: #667eea;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ai-payment-details {
        font-size: 12px;
        color: #333;
    }

    .ai-payment-details p {
        margin: 4px 0;
    }

    .ai-payment-form {
        margin-top: 12px;
    }

    .ai-payment-message {
        margin-top: 8px;
        padding: 8px;
        background: white;
        border-radius: 4px;
        font-style: italic;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    /* Responsive design for mobile devices */
    @media (max-width: 480px) {
        .ai-chat-widget {
            bottom: 15px;
            right: 15px;
        }

        .ai-widget-btn {
            width: 52px;
            height: 52px;
            font-size: 20px;
        }

        .ai-widget-panel {
            width: 280px;
            bottom: 70px;
        }
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .ai-widget-panel {
            background: #2a2a2a;
            color: #e0e0e0;
        }

        .ai-chat-history {
            scrollbar-color: #555 #2a2a2a;
        }

        .ai-message-assistant {
            background: #404040;
            color: #e0e0e0;
        }

        .ai-message-system {
            background: #1a3a3a;
            color: #6db3f2;
        }

        .ai-message-input {
            background: #3a3a3a;
            color: #e0e0e0;
            border-color: #555;
        }

        .ai-message-input:focus {
            border-color: #667eea;
        }
    }
</style>

<script>
    // AI Chat Widget JavaScript
    // Handles voice recording and transcription submission

    (function () {
        'use strict';

        // Check for browser support
        const mediaRecorderSupported = !!window.MediaRecorder && !!navigator.mediaDevices && !!navigator.mediaDevices.getUserMedia;

        let mediaRecorder = null;
        let audioChunks = [];
        let recordingStartTime = null;
        let recordingInterval = null;

        const widget = {
            toggle: document.getElementById('ai-widget-toggle'),
            panel: document.getElementById('ai-widget-panel'),
            close: document.getElementById('ai-widget-close'),
            recordBtn: document.getElementById('ai-record-btn'),
            messageInput: document.getElementById('ai-message-input'),
            sendBtn: document.getElementById('ai-send-btn'),
            chatHistory: document.getElementById('ai-chat-history'),
            error: document.getElementById('ai-widget-error'),
            recordTime: document.getElementById('ai-record-time'),
        };

        // Initialize widget
        function init() {
            if (!mediaRecorderSupported) {
                widget.recordBtn.disabled = true;
            }

            widget.toggle.addEventListener('click', togglePanel);
            widget.close.addEventListener('click', closePanel);
            widget.recordBtn.addEventListener('click', toggleRecording);
            widget.sendBtn.addEventListener('click', sendMessage);
            widget.messageInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }

        function togglePanel() {
            const isOpen = widget.panel.getAttribute('aria-hidden') === 'false';
            if (isOpen) {
                closePanel();
            } else {
                openPanel();
            }
        }

        function openPanel() {
            widget.panel.removeAttribute('hidden');
            widget.panel.setAttribute('aria-hidden', 'false');
            widget.toggle.setAttribute('aria-expanded', 'true');
        }

        function closePanel() {
            widget.panel.setAttribute('hidden', '');
            widget.panel.setAttribute('aria-hidden', 'true');
            widget.toggle.setAttribute('aria-expanded', 'false');

            // Stop recording if active
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                stopRecording();
            }
        }

        function toggleRecording() {
            if (!mediaRecorder) {
                startRecording();
            } else if (mediaRecorder.state === 'recording') {
                stopRecording();
            }
        }

        async function startRecording() {
            try {
                clearError();
                showStatus('Starting recording...');

                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = (event) => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = handleRecordingComplete;

                mediaRecorder.start();

                // Update button UI
                widget.recordBtn.classList.add('recording');
                widget.recordBtn.innerHTML = '<i class="fas fa-stop"></i><span>Stop Recording</span>';

                // Start recording timer
                recordingStartTime = Date.now();
                recordingInterval = setInterval(updateRecordingTime, 100);
                widget.recordTime.removeAttribute('hidden');

                showStatus('Recording... Click stop when finished.');
            } catch (err) {
                showError('Microphone access denied: ' + err.message);
                widget.recordBtn.disabled = true;
            }
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();

                // Update button UI
                widget.recordBtn.classList.remove('recording');
                widget.recordBtn.innerHTML = '<i class="fas fa-circle"></i><span>Start Recording</span>';

                // Stop timer
                clearInterval(recordingInterval);
                widget.recordTime.setAttribute('hidden', '');

                showStatus('Processing audio...');
            }
        }

        function updateRecordingTime() {
            if (recordingStartTime) {
                const elapsed = Date.now() - recordingStartTime;
                const minutes = Math.floor(elapsed / 60000);
                const seconds = Math.floor((elapsed % 60000) / 1000);
                widget.recordTime.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
        }

        function handleRecordingComplete() {
            const audioBlob = new Blob(audioChunks, { type: 'audio/mp3' });

            // Stop the audio stream
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
            mediaRecorder = null;

            // Upload audio to transcription endpoint
            submitAudio(audioBlob);
        }

        function submitAudio(audioBlob) {
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.mp3');

            showStatus('Transcribing audio...');

            fetch('<?= site_url('ai_assistant/transcribe') ?>', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Auto-send transcribed text as message to chat
                        widget.messageInput.value = data.text;
                        sendMessage();
                    } else if (data.error === 'not_configured') {
                        showError('Speech transcription is not available. Please type your message instead.');
                    } else {
                        showError('Transcription failed: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    showError('Network error: ' + err.message);
                });
        }

        function sendMessage() {
            // Guard against double-submits (double-click, Enter held down) racing two overlapping
            // requests against the server's non-atomic session read-modify-write of chat history.
            if (widget.sendBtn.disabled) {
                return;
            }

            const message = widget.messageInput.value.trim();

            if (!message) {
                return;
            }

            // Clear input
            widget.messageInput.value = '';

            // Display user message in chat
            addChatMessage('user', message);

            // Show loading indicator, lock out further sends until this one completes
            showStatus('Processing...');
            widget.sendBtn.disabled = true;
            widget.messageInput.disabled = true;

            // Send to chat endpoint
            fetch('<?= site_url('ai_assistant/chat') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                }),
            })
                .then(response => response.json())
                .then(data => {
                    clearError();

                    if (data.type === 'reply') {
                        // Chat reply
                        addChatMessage('assistant', data.text);
                    } else if (data.type === 'create_appointment') {
                        // Appointment created
                        if (data.success) {
                            addChatMessage('assistant', data.message || 'Randevunuz başarıyla oluşturuldu!');

                            // Show payment link if required
                            if (data.payment_required && data.payment_intent) {
                                displayPaymentIntent(data.payment_intent);
                            }
                        } else {
                            addChatMessage('assistant', data.text || 'Randevu oluşturulamadı.');
                        }
                    } else {
                        // Unknown response
                        showError('Unexpected response from server.');
                    }
                })
                .catch(err => {
                    showError('Network error: ' + err.message);
                })
                .finally(() => {
                    widget.sendBtn.disabled = false;
                    widget.messageInput.disabled = false;
                    widget.messageInput.focus();
                });
        }

        function addChatMessage(role, text) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'ai-chat-message ai-message-' + role;
            messageDiv.textContent = text;

            widget.chatHistory.appendChild(messageDiv);

            // Auto-scroll to bottom
            widget.chatHistory.scrollTop = widget.chatHistory.scrollHeight;
        }

        function displayPaymentIntent(paymentIntent) {
            const paymentDiv = document.createElement('div');
            paymentDiv.className = 'ai-payment-container';

            let paymentHtml = '<div class="ai-payment-notice">' +
                '<i class="fas fa-credit-card"></i> Ödeme Gerekli' +
                '</div>' +
                '<div class="ai-payment-details">' +
                '<p><strong>Tutar:</strong> ' + formatCurrency(paymentIntent.amount) + ' TRY</p>' +
                '<p><strong>Ağ Geçidi:</strong> ' + escapeHtml(paymentIntent.gateway) + '</p>';

            // Show payment form or link based on gateway
            if (paymentIntent.checkout_form) {
                // Iyzico, PayTR etc. provide checkout form
                paymentHtml += '<div class="ai-payment-form">' + paymentIntent.checkout_form + '</div>';
            } else if (paymentIntent.intent_id) {
                // Provide generic payment link message
                paymentHtml += '<p class="ai-payment-message">Ödeme işlemi başlatılacak. ' +
                    'Lütfen yönlendirildiğiniz ödeme sayfasını tamamlayınız.</p>';
            }

            paymentHtml += '</div>';

            paymentDiv.innerHTML = paymentHtml;
            widget.chatHistory.appendChild(paymentDiv);

            // Auto-scroll to bottom
            widget.chatHistory.scrollTop = widget.chatHistory.scrollHeight;
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('tr-TR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount);
        }

        function showStatus(message, type = 'info') {
            if (type === 'error') {
                showError(message);
            } else {
                // Status message logged but not displayed (for internal tracking)
                console.log('[AI Widget]', message);
                clearError();
            }
        }

        function showError(message) {
            widget.error.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + escapeHtml(message);
            widget.error.removeAttribute('hidden');
        }

        function clearError() {
            widget.error.setAttribute('hidden', '');
            widget.error.innerHTML = '';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
