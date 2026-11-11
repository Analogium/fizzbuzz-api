<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Toute erreur sous /api (404, 405, 400 sur un paramètre mal formé, 500...) renvoie du JSON
 * au lieu de la page d'erreur HTML par défaut de Symfony.
 *
 * Priorité -64 : après le logger d'erreurs de Symfony (priorité 0, l'erreur est donc
 * bien loguée) et avant son rendu HTML (priorité -128).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        // Headers de l'exception conservés : ex. "Allow: GET" sur un 405.
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        // Message générique ("Not Found"...) : on n'expose pas le message interne de l'exception.
        $event->setResponse(new JsonResponse(
            ['error' => Response::$statusTexts[$status] ?? 'Error'],
            $status,
            $headers,
        ));
    }
}
