<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $code = $exception->getCode() ?: 400;
        $message = 'dev' !== $_ENV['APP_ENV'] ? 'Oops! An Error Occurred' : $exception->getMessage();

        if ($exception->getPrevious() instanceof InsufficientAuthenticationException) {
            $code = 403;
        }

        $response = new JsonResponse(
            json_encode(['message' => $message], JSON_UNESCAPED_UNICODE),
            $code,
            [],
            true
        );
        $event->setResponse($response);
    }
}
