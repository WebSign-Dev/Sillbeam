<?php

declare(strict_types=1);

namespace Nexum\Mailer\Contract;

use Nexum\Mailer\Model\EmailMessage;

interface MailerInterface
{
    public function send(EmailMessage $message): void;
}
