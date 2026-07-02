<?php

session_start();

use Slim\Views\PhpRenderer;
use Slim\Factory\AppFactory;
use DI\Container;
use GuzzleHttp\Client;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$container = new Container();

$container->set(PhpRenderer::class, new PhpRenderer(__DIR__ . '/../templates', [], 'layout.php'));

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$container->set(PDO::class, function () {
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

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        die("Ошибка подключения к БД: " . $e->getMessage());
    }
});

$container->set(Client::class, function () {
    return new Client(['timeout' => 5, 'connect_timeout' => 5]);
});


AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(false, false, false);

$routes = require __DIR__ . '/../routes/routes.php';
$routes($app, $container);

$app->run();
