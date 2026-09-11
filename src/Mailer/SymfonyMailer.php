<?php

declare(strict_types=1);

namespace Nexum\Mailer;

use Nexum\Mailer\Contract\MailerInterface;
use Nexum\Mailer\Model\EmailMessage;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Email;

final readonly class SymfonyMailer implements MailerInterface
{
    public function __construct(private SymfonyMailerInterface $mailer, private ?string $defaultFrom = null)
    {
    }

    public function send(EmailMessage $message): void
    {
        $email = (new Email())->to(...$message->to)->subject($message->subject);
        $from = $message->from ?? $this->defaultFrom;
        if (null !== $from) {
            $email->from($from);
        }
        if (null !== $message->text) {
            $email->text($message->text);
        }
        if (null !== $message->html) {
            $email->html($message->html);
        }
        $this->mailer->send($email);
    }
}
