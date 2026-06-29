<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\App;
use DI\Container;
use PDO;
use GuzzleHttp\Client;
use Slim\Psr7\Factory\ServerRequestFactory;

class UrlAnalyzerTest extends TestCase
{
    private App $app;
    private PDO $pdo;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];

        $this->container = new Container();

        $this->container->set(\Slim\Views\PhpRenderer::class, new \Slim\Views\PhpRenderer(
            __DIR__ . '/../templates',
            [],
            'layout.php'
        ));

        $this->container->set('flash', function () {
            return new \Slim\Flash\Messages();
        });

        $this->container->set(PDO::class, function () {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $name = $_ENV['DB_NAME'] ?? 'analyzer_test';
            $user = $_ENV['DB_USERNAME'] ?? 'postgres';
            $password = $_ENV['DB_PASSWORD'] ?? 'password';

            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

            return new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        });

        $this->container->set(Client::class, function () {
            return new Client();
        });

        $this->pdo = $this->container->get(PDO::class);

        $this->pdo->exec('TRUNCATE TABLE url_checks RESTART IDENTITY CASCADE');
        $this->pdo->exec('TRUNCATE TABLE urls RESTART IDENTITY CASCADE');

        AppFactory::setContainer($this->container);
        $this->app = AppFactory::create();
        $this->app->addErrorMiddleware(false, false, false);

        $routes = require __DIR__ . '/../routes/routes.php';
        $routes($this->app, $this->container);
    }

    public function testHomePage(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Анализатор страниц', (string)$response->getBody());
    }

    public function testPostInvalidUrl(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')
            ->withParsedBody(['url' => 'not-a-valid-url']);

        $response = $this->app->handle($request);

        $this->assertEquals(302, $response->getStatusCode());
    }
}
