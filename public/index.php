<?php

use Slim\Views\PhpRenderer;
use Slim\Factory\AppFactory;
use DI\Container;
use GuzzleHttp\Client;
use Dotenv\Dotenv;
use Slim\Flash\Messages;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$container = new Container();

$container->set('flash', function () {
    return new Messages();
});

$container->set('pdo', function () {
    $dbUrl = getenv('DATABASE_URL');

    if ($dbUrl) {
        $parts = parse_url($dbUrl);
        $host = $parts['host'];
        $port = $parts['port'];
        $db   = ltrim($parts['path'] ?? '', '/');
        $user = $parts['user'];
        $pass = $parts['pass'];
    } else {
        $host = getenv('DB_HOST');
        $port = getenv('DB_PORT');
        $db   = getenv('DB_NAME');
        $user = getenv('DB_USERNAME');
        $pass = getenv('DB_PASSWORD');
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$db}";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $user, $pass, $options);
});

$container->set('client', function () {
    return new Client(['timeout' => 5, 'connect_timeout' => 5]);
});


AppFactory::setContainer($container);
$app = AppFactory::create();


$container->set('renderer', function () use ($app, $container) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates', [], 'layout.php');
    $renderer->addAttribute('router', $app->getRouteCollector()->getRouteParser());
    $renderer->addAttribute('flash', $container->get('flash')->getMessages());

    return $renderer;
});

$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler(getErrorHandler($app, $container));

$routes = require __DIR__ . '/../routes/routes.php';
$routes($app, $container);

$app->run();
