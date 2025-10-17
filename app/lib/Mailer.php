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

        $fromEmail = $fromEmail ?: (getenv('SMTP_FROM_EMAIL') ?: 'no-reply@example.com');
        $fromName = $fromName ?: (getenv('SMTP_FROM_NAME') ?: 'Event Manager');

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // SMTP config
            $useSmtp = (bool)(getenv('SMTP_ENABLED') ?: true);
            if ($useSmtp) {
                $mail->isSMTP();
                $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
                $mail->Port = (int)(getenv('SMTP_PORT') ?: 25);
                $smtpSecure = getenv('SMTP_SECURE') ?: '';
                if ($smtpSecure) { $mail->SMTPSecure = $smtpSecure; }
                $smtpAuth = (bool)(getenv('SMTP_AUTH') ?: false);
                $mail->SMTPAuth = $smtpAuth;
                if ($smtpAuth) {
                    $mail->Username = getenv('SMTP_USERNAME') ?: '';
                    $mail->Password = getenv('SMTP_PASSWORD') ?: '';
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


