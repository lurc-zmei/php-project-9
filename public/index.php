<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler; // обработчик Middleware

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addErrorMiddleware(true, false, false);


$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Анализатор страниц!");
    return $response;
})->setName('home');


$app->get('/urls', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Сайты!");
    return $response;
})->setName('urls.index');


/*
$app->get('/{slug}', function (Request $request, Response $response, $args) {
    $response->getBody()->write('This page is ' . $args['slug']);
    return $response;
});
*/

$app->run();
