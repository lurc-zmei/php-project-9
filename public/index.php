<?php

session_start();

use Slim\Views\PhpRenderer;
use Slim\Factory\AppFactory;
use DI\Container;
use GuzzleHttp\Client;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$container = new Container();

$container->set(PhpRenderer::class, new PhpRenderer(__DIR__ . '/../templates', [], 'layout.php'));

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$container->set(PDO::class, function () {
    $dsn = "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        return new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $options);
    } catch (\PDOException $e) {
        die("Ошибка подключения к БД: " . $e->getMessage());
    }
});

$container->set(Client::class, function () {
    return new Client(['timeout' => 5,'connect_timeout' => 5]);
});


AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, false, false);

$routes = require __DIR__ . '/../routes/routes.php';
$routes($app, $container);

$app->run();
