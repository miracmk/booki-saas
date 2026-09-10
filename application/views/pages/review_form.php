<?php defined('BASEPATH') or exit('No direct script access allowed');

// Standalone public page (not wrapped by the backend layout engine) - pull html_vars()
// into plain PHP variables explicitly, like pages/marketplace_business.php does.
extract(html_vars());

$error_message = $error_message ?? '';
$token = $token ?? '';
$customer_name = $customer_name ?? '';
$csrf_token = $csrf_token ?? '';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Değerlendirme'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .card h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .card .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .error-box {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 1.25rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .error-box .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #721c24;
            font-weight: 600;
            text-decoration: none;
        }

        .error-box .back-link:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 110px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 4px rgba(76, 175, 80, 0.2);
        }

        .rating-input {
            display: flex;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .rating-input input[type="radio"] {
            display: none;
            width: auto;
        }

        .rating-input label {
            display: inline-block;
            width: 2.75rem;
            height: 2.75rem;
            text-align: center;
            line-height: 2.75rem;
            cursor: pointer;
            font-size: 1.6rem;
            background-color: #f0f0f0;
            border-radius: 4px;
            border: 2px solid transparent;
            transition: background-color 0.2s, color 0.2s;
        }

        .rating-input input[type="radio"]:checked + label {
            background-color: #ffc107;
            color: white;
            border-color: #ff9800;
        }

        .rating-input label:hover {
            background-color: #e0e0e0;
        }

        .submit-button {
            width: 100%;
            background-color: #4CAF50;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .submit-button:hover {
            background-color: #45a049;
        }

        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: none;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($error_message !== ''): ?>
            <div class="error-box">
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <?php if (isset($booking_url) && $booking_url !== ''): ?>
                    <a href="<?php echo htmlspecialchars($booking_url); ?>" class="back-link">← Randevu Sayfasına Dön</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h1>Deneyiminizi Değerlendirin</h1>
            <p class="subtitle">Randevunuz sonrası deneyiminizi paylaşmanız bize çok yardımcı olur. Puanınız ve yorumunuz yayınlanmadan önce işletme tarafından incelenir.</p>

            <div id="form-message" class="message"></div>

            <form id="review-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="customer_name">Adınız</label>
                    <input type="text" id="customer_name" name="customer_name" maxlength="128" value="<?php echo htmlspecialchars($customer_name); ?>">
                </div>

                <div class="form-group">
                    <label>Derecelendirmeniz *</label>
                    <div class="rating-input">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" id="rating-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                            <label for="rating-<?php echo $i; ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comment">Yorumunuz</label>
                    <textarea id="comment" name="comment" maxlength="2000"></textarea>
                </div>

                <button type="submit" class="submit-button">Değerlendirmeyi Gönder</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            const form = document.getElementById('review-form');

            if (!form) {
                return;
            }

            const messageDiv = document.getElementById('form-message');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const rating = document.querySelector('input[name="rating"]:checked');

                if (!rating) {
                    showMessage('Lütfen bir derecelendirme seçin.', 'error');
                    return;
                }

                const formData = new FormData(form);
                const data = new URLSearchParams(formData);

                fetch('<?php echo site_url('review/submit'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: data.toString()
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showMessage(result.message || 'Değerlendirmeniz için teşekkür ederiz!', 'success');
                        form.style.display = 'none';
                    } else {
                        showMessage(result.message || 'Bir hata oluştu.', 'error');
                    }
                })
                .catch(function () {
                    showMessage('İsteğiniz işlenirken bir hata oluştu.', 'error');
                });
            });

            function showMessage(text, type) {
                messageDiv.classList.remove('success', 'error');
                messageDiv.classList.add(type);
                messageDiv.textContent = text;
                messageDiv.style.display = 'block';
            }
        })();
    </script>
</body>
</html>