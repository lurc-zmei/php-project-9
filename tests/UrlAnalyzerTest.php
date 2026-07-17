<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\App;
use DI\Container;
use PDO;
use GuzzleHttp\Client;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class UrlAnalyzerTest extends TestCase
{
    private App $app;
    private PDO $pdo;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
        $_SESSION = [];

        $this->container = new Container();

        $this->container->set('flash', function () {
            return new class extends Messages {
                public function getMessages(): array
                {
                    return $_SESSION['slimFlash'] ?? [];
                }
            };
        });

        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'analyzer_test';
        $user = getenv('DB_USERNAME') ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: 'password';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        $this->pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->container->set('pdo', function () {
            return $this->pdo;
        });

        $mockHandler = new MockHandler([
            new Response(200, [], '<html><title>Тестовый сайт</title><body>Содержимое</body></html>')
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $this->container->set('client', function () use ($mockClient) {
            return $mockClient;
        });

        $this->pdo->beginTransaction();

        AppFactory::setContainer($this->container);
        $this->app = AppFactory::create();
        $this->app->addErrorMiddleware(true, true, true);

        $this->container->set('renderer', function () {
            $renderer = new PhpRenderer(__DIR__ . '/../templates', [], 'layout.php');
            $renderer->addAttribute('router', $this->app->getRouteCollector()->getRouteParser());
            $renderer->addAttribute('flash', $this->container->get('flash')->getMessages());
            return $renderer;
        });

        $routes = require __DIR__ . '/../routes/routes.php';
        $routes($this->app, $this->container);
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
        parent::tearDown();
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

        $flash = $this->container->get('flash');
        $messages = $flash->getMessages();
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Страница успешно добавлена', $messages['success'][0]);
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

        $flash = new Messages();
        $messages = $flash->getMessages();
        $this->assertArrayHasKey('success', $messages);
        $this->assertStringContainsString('Страница уже существует', $messages['success'][0]);
    }

    public function testShowUrlPage(): void
    {
        $this->pdo->exec("INSERT INTO urls (name, created_at) VALUES ('https://example.com', '2026-07-01')");
        $id = $this->pdo->lastInsertId();

        $request = (new ServerRequestFactory())->createServerRequest('GET', "/urls/{$id}");
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('https://example.com', (string)$response->getBody());
    }
}
