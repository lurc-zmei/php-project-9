<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';


// Создание контейнера PHP-DI
$container = new Container();

$container->set(PhpRenderer::class, new PhpRenderer(__DIR__ . '/../templates', [], 'layout.php'));


// Создаём объект приложения Slim и передаём в него контейнер
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, false, false);


$app->get('/', function (Request $request, Response $response) use ($container) {
    return $container->get(PhpRenderer::class)->render($response, 'home.php');
})->setName('home');



$app->get('/urls', function (Request $request, Response $response) use ($container) {
    return $container->get(PhpRenderer::class)->render($response, 'index.php');
})->setName('urls.index');


$app->run();
