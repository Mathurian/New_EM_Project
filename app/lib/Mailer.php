<?php
declare(strict_types=1);
namespace App;

class Mailer {
    public static function sendHtml(string $toEmail, string $subject, string $html, ?string $fromEmail = null, ?string $fromName = null): bool {
        // Lazy include PHPMailer if installed in vendor
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        } else {
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

            return $mail->send();
        } catch (\Throwable $e) {
            error_log('Mailer send failed: ' . $e->getMessage());
            return false;
        }
    }
}


