<?php

declare(strict_types=1);

namespace Nexum\Api\Error;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Nexum\Api\Serializer\ApiResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ApiResponseFactoryInterface $responses,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 100]];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (true !== $event->getRequest()->attributes->get('_nexum_api')) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof AuthenticationException || $throwable instanceof AccessDeniedException) {
            return;
        }

        if ($throwable instanceof ApiProblemException) {
            $event->setResponse($this->responses->problem($throwable));

            return;
        }

        if ($throwable instanceof UniqueConstraintViolationException) {
            $event->setResponse($this->responses->problem(new ApiProblemException(
                409,
                'resource_conflict',
                'Resource conflict',
                'A resource with the submitted unique values already exists.',
            )));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $problem = new ApiProblemException(
                status: $throwable->getStatusCode(),
                problemCode: 'http_error',
                title: $throwable->getStatusCode() >= 500 ? 'Internal server error' : 'Request failed',
                detail: $throwable->getStatusCode() >= 500 ? 'The request could not be completed.' : $throwable->getMessage(),
                headers: $throwable->getHeaders(),
            );
            $event->setResponse($this->responses->problem($problem));

            return;
        }

        $this->logger->error('Unhandled Nexum API exception: {exception_class}: {exception_message}', [
            'exception' => $throwable,
            'exception_class' => $throwable::class,
            'exception_message' => $throwable->getMessage(),
        ]);
        $event->setResponse($this->responses->problem(new ApiProblemException(
            500,
            'internal_error',
            'Internal server error',
            'The request could not be completed.',
        )));
    }
}
