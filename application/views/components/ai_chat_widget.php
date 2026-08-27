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
            <div id="ai-widget-status" class="ai-widget-status">
                <p>Click the microphone button below to start recording your appointment request.</p>
            </div>

            <div id="ai-widget-transcript" class="ai-widget-transcript" hidden>
                <p><strong>I heard:</strong> <span id="ai-transcript-text"></span></p>
                <p class="ai-transcript-hint">AI Assistant will soon process this request and fill in your booking details automatically.</p>
            </div>

            <div class="ai-widget-controls">
                <button id="ai-record-btn" class="ai-record-btn" type="button" title="Start recording">
                    <i class="fas fa-circle"></i>
                    <span>Start Recording</span>
                </button>
                <span id="ai-record-time" class="ai-record-time" hidden>00:00</span>
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
    }

    .ai-widget-status {
        font-size: 14px;
        color: #333;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .ai-widget-transcript {
        background: #f5f5f5;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 16px;
        font-size: 14px;
        line-height: 1.5;
    }

    .ai-widget-transcript p {
        margin: 0 0 8px 0;
    }

    .ai-widget-transcript p:last-child {
        margin-bottom: 0;
    }

    .ai-transcript-hint {
        font-size: 12px;
        color: #666 !important;
        font-style: italic;
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

        .ai-widget-status {
            color: #e0e0e0;
        }

        .ai-widget-transcript {
            background: #404040;
            color: #e0e0e0;
        }

        .ai-transcript-hint {
            color: #999 !important;
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
            status: document.getElementById('ai-widget-status'),
            transcript: document.getElementById('ai-widget-transcript'),
            transcriptText: document.getElementById('ai-transcript-text'),
            error: document.getElementById('ai-widget-error'),
            recordTime: document.getElementById('ai-record-time'),
        };

        // Initialize widget
        function init() {
            if (!mediaRecorderSupported) {
                widget.recordBtn.disabled = true;
                showStatus('Your browser does not support voice recording. Please use a modern browser (Chrome, Firefox, Safari, Edge).', 'error');
                return;
            }

            widget.toggle.addEventListener('click', togglePanel);
            widget.close.addEventListener('click', closePanel);
            widget.recordBtn.addEventListener('click', toggleRecording);
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
                        displayTranscript(data.text);
                        showStatus('I heard: ' + data.text);
                    } else if (data.error === 'not_configured') {
                        showError('AI Assistant is not configured. OpenAI API key is missing.');
                    } else {
                        showError('Transcription failed: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    showError('Network error: ' + err.message);
                });
        }

        function displayTranscript(text) {
            widget.transcriptText.textContent = text;
            widget.transcript.removeAttribute('hidden');
        }

        function showStatus(message, type = 'info') {
            if (type === 'error') {
                showError(message);
            } else {
                widget.status.innerHTML = '<p>' + escapeHtml(message) + '</p>';
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
