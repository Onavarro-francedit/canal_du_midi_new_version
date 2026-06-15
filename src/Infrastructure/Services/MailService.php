<?php
namespace App\Infrastructure\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;

class MailService {

    private PHPMailer $mailer;

    public function __construct() {
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host       = MAIL_HOST;
        $mailer->Port       = MAIL_PORT;
        $mailer->Username   = MAIL_USERNAME;
        $mailer->Password   = MAIL_PASSWORD;
        $mailer->SMTPAuth   = true;
        $mailer->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->isHTML(true);
        $mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

        if (APP_ENV === 'dev') {
            $mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $this->mailer = $mailer;
    }

    /**
     * Envía un email HTML.
     * En modo test (MAIL_TEST_ADDRESS definido) todos los destinatarios
     * se reemplazan por la dirección de prueba y el asunto lleva un prefijo
     * con los destinatarios originales.
     *
     * @param  string|array $to      Dirección(es) destinatario real(es)
     * @param  string       $subject Asunto
     * @param  string       $body    Cuerpo HTML
     * @return bool
     */
    public function send(string|array $to, string $subject, string $body): bool {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();

            $recipients = array_filter(
                array_map('trim', (array)$to),
                fn($a) => $a && filter_var($a, FILTER_VALIDATE_EMAIL)
            );

            if (empty($recipients)) {
                return false;
            }

            $testAddress = defined('MAIL_TEST_ADDRESS') ? trim(MAIL_TEST_ADDRESS) : '';

            if ($testAddress && filter_var($testAddress, FILTER_VALIDATE_EMAIL)) {
                // ── Modo test: redirigir todo a la dirección de prueba ──────
                $this->mailer->addAddress($testAddress);
                $originalTo  = implode(', ', $recipients);
                $subject     = '[TEST → ' . $originalTo . '] ' . $subject;
                $body        = $this->testBanner($originalTo) . $body;
            } else {
                // ── Modo producción: destinatarios reales ───────────────────
                foreach ($recipients as $address) {
                    $this->mailer->addAddress($address);
                }
            }

            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->AltBody = strip_tags(
                str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</tr>'], "\n", $body)
            );

            $this->mailer->send();
            return true;

        } catch (MailerException $e) {
            error_log('[MailService] ' . $e->getMessage());
            return false;
        }
    }

    private function testBanner(string $originalTo): string {
        return '<div style="background:#fff3cd;border:2px solid #ffc107;border-radius:6px;'
            . 'padding:12px 16px;margin-bottom:20px;font-family:Arial,sans-serif;font-size:13px;">'
            . '<strong>⚠ EMAIL DE TEST</strong> — Ce message était destiné à : '
            . '<strong>' . htmlspecialchars($originalTo) . '</strong>'
            . '</div>';
    }
}
