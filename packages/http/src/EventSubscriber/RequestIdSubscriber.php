<?php

declare(strict_types=1);

namespace Nexum\Http\EventSubscriber;

use Nexum\Http\Request\ClientContextResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class RequestIdSubscriber implements EventSubscriberInterface
{
    public function __construct(private ClientContextResolverInterface $contexts)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Request-Id', $this->contexts->resolve($event->getRequest())->requestId);
    }
}
