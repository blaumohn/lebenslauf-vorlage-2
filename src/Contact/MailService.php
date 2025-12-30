<?php

namespace App\Contact;

use App\Config;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function send(string $replyName, string $replyEmail, string $message): bool
    {
        if ($this->config->getBool('MAIL_STDOUT', false)) {
            $payload = "=== CONTACT FORM ===\n";
            $payload .= "Name: {$replyName}\n";
            $payload .= "Email: {$replyEmail}\n\n";
            $payload .= $message . "\n";
            $stream = fopen('php://stdout', 'wb');
            if ($stream === false) {
                error_log($payload);
            } else {
                fwrite($stream, $payload);
            }
            return true;
        }

        $to = (string) $this->config->get('CONTACT_TO', '');
        if ($to === '') {
            return false;
        }

        $subject = (string) $this->config->get('CONTACT_SUBJECT', 'Kontaktformular');
        $fromEmail = (string) $this->config->get('CONTACT_FROM', $to);

        $mailer = new PHPMailer(true);
        $smtpHost = (string) $this->config->get('SMTP_HOST', '');
        if ($smtpHost !== '') {
            $mailer->isSMTP();
            $mailer->Host = $smtpHost;
            $mailer->Port = (int) $this->config->get('SMTP_PORT', 587);
            $mailer->SMTPAuth = true;
            $mailer->Username = (string) $this->config->get('SMTP_USER', '');
            $mailer->Password = (string) $this->config->get('SMTP_PASS', '');
            $mailer->SMTPSecure = (string) $this->config->get('SMTP_ENCRYPTION', 'tls');
        }

        $fromName = (string) $this->config->get('SMTP_FROM_NAME', 'Web');
        $configuredFromEmail = (string) $this->config->get('SMTP_FROM_EMAIL', '');
        if ($configuredFromEmail !== '') {
            $fromEmail = $configuredFromEmail;
        }

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($to);
        $mailer->addReplyTo($replyEmail, $replyName);
        $mailer->Subject = $subject;
        $mailer->Body = $message;

        return $mailer->send();
    }
}
