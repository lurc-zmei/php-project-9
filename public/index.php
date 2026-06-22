<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$container = new Container();

$container->set(PhpRenderer::class, new PhpRenderer(__DIR__ . '/../templates', [], 'layout.php'));

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


AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, false, false);


$app->get('/', function (Request $request, Response $response) use ($container) {
    return $container->get(PhpRenderer::class)->render($response, 'home.php');
})->setName('home');


$app->get('/urls', function (Request $request, Response $response) use ($container) {
    $pdo = $container->get(PDO::class);
    $sql = 'SELECT * FROM urls ORDER BY id DESC';
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    return $container->get(PhpRenderer::class)->render($response, 'index.php', compact('rows')); //['rows' => $rows]
})->setName('urls.index');


$app->post('/', function (Request $request, Response $response) use ($container) {
    $data = $request->getParsedBody();
    $url = $data['url'] ?? '';
    $pdo = $container->get(PDO::class);
    $sql = 'INSERT INTO urls (name) VALUES (:url)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['url' => $url]);
    return $response->withHeader('Location', '/')->withStatus(302);
});


$app->run();
