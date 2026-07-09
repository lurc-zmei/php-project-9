<?php

use Carbon\Carbon;
use Slim\Exception\HttpNotFoundException;

function formatDate(string $date)
{
    return $date ? Carbon::parse($date)->format('Y-m-d') : null;
}

function truncate(string|null $text, int $limit = 200): string
{
    $text ??= '';
    return mb_strlen($text) <= $limit ? $text : mb_substr($text, 0, $limit) . '...';
}

function getErrorHandler(mixed $app, mixed $container): callable
{
    return function (
        $request,
        $exception,
        $_displayErrorDetails,
        $_logErrors,
        $_logErrorDetails
    ) use ($app, $container) {
        
        $response = $app->getResponseFactory()->createResponse();

        if ($exception instanceof HttpNotFoundException) {
            $statusCode = 404;
            $template = 'errors/404.php';
        } else {
            $statusCode = 500;
            $template = 'errors/500.php';
        }

        return $container->get('renderer')->render(
            $response->withStatus($statusCode),
            $template,
            ['message' => $exception->getMessage()]
        );
    };
}