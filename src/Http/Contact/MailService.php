<?php

namespace App\Http\Contact;

use App\Env\Env;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    private Env $config;

    public function __construct(Env $config)
    {
        $this->config = $config;
    }

    public function send(string $replyName, string $replyEmail, string $message): bool
    {
        if ($this->config->getBool('MAIL_STDOUT', false)) {
            return $this->sendToStdout($replyName, $replyEmail, $message);
        }

        $to = $this->contactRecipient();
        if ($to === '') {
            return false;
        }

        $mailer = $this->createMailer($replyName, $replyEmail, $to);
        $mailer->Body = $message;

        return $mailer->send();
    }

    private function sendToStdout(string $replyName, string $replyEmail, string $message): bool
    {
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

    private function contactRecipient(): string
    {
        return (string) $this->config->get('CONTACT_TO', '');
    }

    private function createMailer(string $replyName, string $replyEmail, string $to): PHPMailer
    {
        $mailer = new PHPMailer(true);
        $this->configureSmtp($mailer);

        $fromEmail = $this->resolveFromEmail($to);
        $fromName = (string) $this->config->get('SMTP_FROM_NAME', 'Web');
        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($to);
        $mailer->addReplyTo($replyEmail, $replyName);
        $mailer->Subject = (string) $this->config->get('CONTACT_SUBJECT', 'Kontaktformular');

        return $mailer;
    }

    private function configureSmtp(PHPMailer $mailer): void
    {
        $smtpHost = (string) $this->config->get('SMTP_HOST', '');
        if ($smtpHost === '') {
            return;
        }

        $mailer->isSMTP();
        $mailer->Host = $smtpHost;
        $mailer->Port = (int) $this->config->get('SMTP_PORT', 587);
        $mailer->SMTPAuth = true;
        $mailer->Username = (string) $this->config->get('SMTP_USER', '');
        $mailer->Password = (string) $this->config->get('SMTP_PASS', '');
        $mailer->SMTPSecure = (string) $this->config->get('SMTP_ENCRYPTION', 'tls');
    }

    private function resolveFromEmail(string $recipient): string
    {
        $fromEmail = (string) $this->config->get('CONTACT_FROM', $recipient);
        $configuredFromEmail = (string) $this->config->get('SMTP_FROM_EMAIL', '');
        return $configuredFromEmail !== '' ? $configuredFromEmail : $fromEmail;
    }
}
