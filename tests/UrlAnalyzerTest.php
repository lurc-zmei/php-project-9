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
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'analyzer_test';
            $user = getenv('DB_USERNAME') ?: 'postgres';
            $password = getenv('DB_PASSWORD') ?: 'password';

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
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/urls')
            ->withParsedBody(['url' => 'not-a-valid-url']);

        $response = $this->app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testPostValidUrl(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/urls')
            ->withParsedBody(['url' => 'https://example.com']);

        $response = $this->app->handle($request);

        $this->assertEquals(302, $response->getStatusCode());

        $stmt = $this->pdo->query('SELECT * FROM urls WHERE name = \'https://example.com\'');
        $this->assertNotNull($stmt->fetch());
    }

    public function testPostExistingUrl(): void
    {
        $this->pdo->exec("INSERT INTO urls (name, created_at) VALUES ('https://example.com', '2026-07-01')");

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/urls')
            ->withParsedBody(['url' => 'https://example.com']);

        $response = $this->app->handle($request);

        $this->assertEquals(302, $response->getStatusCode());

        $stmt = $this->pdo->query('SELECT count(*) FROM urls');
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testShowUrlPage(): void
    {
        $this->pdo->exec("INSERT INTO urls (name, created_at) VALUES ('https://example.com', '2026-07-01')");

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/urls/1');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('https://example.com', (string)$response->getBody());
    }
}
