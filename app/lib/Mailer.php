<?php
declare(strict_types=1);
namespace App;

class Mailer {
    public static function sendHtml(string $toEmail, string $subject, string $html, ?string $fromEmail = null, ?string $fromName = null): bool {
        // Log email attempt
        \App\Logger::debug('email_send_attempt', 'email', null, "Attempting to send email to: {$toEmail}, subject: {$subject}");
        
        // Lazy include PHPMailer if installed in vendor
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        } else {
            \App\Logger::error('email_phpmailer_missing', 'email', null, 'PHPMailer not installed (vendor/autoload.php missing)');
            error_log('PHPMailer not installed (vendor/autoload.php missing).');
            return false;
        }

        // Load settings from DB (system_settings), fallback to env
        $settings = [];
        try {
            $stmt = DB::pdo()->query('SELECT setting_key, setting_value FROM system_settings');
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) { $settings[$row['setting_key']] = $row['setting_value']; }
        } catch (\Throwable $e) {
            // ignore, fallback to env
        }

        $fromEmail = $fromEmail ?: ($settings['smtp_from_email'] ?? (getenv('SMTP_FROM_EMAIL') ?: 'no-reply@example.com'));
        $fromName = $fromName ?: ($settings['smtp_from_name'] ?? (getenv('SMTP_FROM_NAME') ?: 'Event Manager'));

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // SMTP config
            $useSmtp = (bool)(($settings['smtp_enabled'] ?? '') !== '' ? (int)$settings['smtp_enabled'] : (getenv('SMTP_ENABLED') ?: 1));
            if ($useSmtp) {
                $mail->isSMTP();
                $mail->Host = $settings['smtp_host'] ?? (getenv('SMTP_HOST') ?: 'localhost');
                $mail->Port = (int)($settings['smtp_port'] ?? (getenv('SMTP_PORT') ?: 25));
                $smtpSecure = $settings['smtp_secure'] ?? (getenv('SMTP_SECURE') ?: '');
                if ($smtpSecure) { $mail->SMTPSecure = $smtpSecure; }
                $smtpAuth = (bool)(($settings['smtp_auth'] ?? '') !== '' ? (int)$settings['smtp_auth'] : (getenv('SMTP_AUTH') ?: 0));
                $mail->SMTPAuth = $smtpAuth;
                if ($smtpAuth) {
                    $mail->Username = $settings['smtp_username'] ?? (getenv('SMTP_USERNAME') ?: '');
                    $mail->Password = $settings['smtp_password'] ?? (getenv('SMTP_PASSWORD') ?: '');
                }
            }

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);

            $result = $mail->send();
            if ($result) {
                \App\Logger::info('email_sent_success', 'email', null, "Email sent successfully to: {$toEmail}, subject: {$subject}");
            } else {
                \App\Logger::error('email_send_failed', 'email', null, "Failed to send email to: {$toEmail}, subject: {$subject}");
            }
            return $result;
        } catch (\Throwable $e) {
            \App\Logger::error('email_send_exception', 'email', null, "Email send exception to {$toEmail}: " . $e->getMessage());
            error_log('Mailer send failed: ' . $e->getMessage());
            return false;
        }
    }
}


