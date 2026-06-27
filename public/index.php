<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;
use Valitron\Validator;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

require __DIR__ . '/../vendor/autoload.php';

session_start();

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


$app->get('/', function (Request $request, Response $response) use ($container) {
    $flash = $container->get('flash');
    $messages = $flash->getMessages();
    $oldInput = $messages['old_input'][0] ?? [];
    return $container->get(PhpRenderer::class)->render($response, 'home.php', ['flash' => $messages, 'oldInput' => $oldInput]);
})->setName('home');


$app->get('/urls', function (Request $request, Response $response) use ($container) {
    $pdo = $container->get(PDO::class);
    $flash = $container->get('flash');
    $messages = $flash->getMessages();

    $sql = 'SELECT
                urls.id,
                urls.name,
                urls.created_at,
                url_checks.status_code AS last_status_code,
                url_checks.created_at AS last_check_created_at
            FROM urls
            LEFT JOIN (
                SELECT DISTINCT ON (url_id) 
                    url_id, 
                    status_code, 
                    created_at
                FROM url_checks
                ORDER BY url_id, id DESC
            ) url_checks ON urls.id = url_checks.url_id
            ORDER BY id DESC';
    
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    foreach ($rows as $key => $row) {
        $rows[$key]['created_at'] = formatDate($row['created_at']);
    }

    return $container->get(PhpRenderer::class)->render($response, 'index.php', ['rows' => $rows, 'flash' => $messages]);
})->setName('urls.index');


$app->post('/', function (Request $request, Response $response) use ($container) {
    $data = $request->getParsedBody();
    $url = trim($data['url'] ?? '');
    $pdo = $container->get(PDO::class);
    $flash = $container->get('flash');

    if (parse_url($url, PHP_URL_SCHEME) === null) {
        $url = 'https://' . $url;
    }

    $url = rtrim($url, '/');

    $validator = new Validator(['url' => $url]);
    $validator->rule('required', 'url')->message('URL не должен быть пустым');
    $validator->rule('lengthMax', 'url', 255)->message('URL превышает 255 символов');
    $validator->rule('url', 'url')->message('Некорректный URL');

    if (!$validator->validate()) {
        $errors = $validator->errors();
        $flash->addMessage('old_input', $url);
        foreach ($errors['url'] as $error) {
            $flash->addMessage('danger', $error);
        }
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    $sql = 'SELECT id FROM urls WHERE name = :url';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['url' => $url]);
    $urlExist = $stmt->fetch();

    if ($urlExist) {
        $flash->addMessage('success', 'Страница уже существует');
        return $response->withHeader('Location', '/urls/' . $urlExist['id'])->withStatus(302);
    }


    try {
        $sql = 'INSERT INTO urls (name, created_at) VALUES (:url, :created_at)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'url' => $url,
            'created_at' => Carbon::now()
        ]);
        $newId = $pdo->lastInsertId();
        $flash->addMessage('success', 'Страница успешно добавлена');
        return $response->withHeader('Location', '/urls/' . $newId)->withStatus(302);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23505') {
            $stmt = $pdo->prepare('SELECT id FROM urls WHERE name = :url');
            $stmt->execute(['url' => $url]);
            $existing = $stmt->fetch();
            $flash->addMessage('warning', 'Страница уже существует');
            return $response->withHeader('Location', '/urls/' . $existing['id'])->withStatus(302);
        }
        $flash->addMessage('danger', 'Произошла ошибка при добавлении');
        $flash->addMessage('old_input', $url);
        return $response->withHeader('Location', '/')->withStatus(302);
    }
});


$app->get('/urls/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container) {
    $id = $args['id'];
    $pdo = $container->get(PDO::class);
    $flash = $container->get('flash');
    $messages = $flash->getMessages();

    $sql = 'SELECT * FROM urls WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $url = $stmt->fetch();

    if (!$url) {
        throw new \Slim\Exception\HttpNotFoundException($request);
    }

    $url['created_at'] = formatDate($url['created_at']);

    $sql = 'SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['url_id' => $id]);
    $checks = $stmt->fetchAll();

    foreach ($checks as $key => $check) {
        $checks[$key]['created_at'] = formatDate($check['created_at']);
        $checks[$key]['h1'] = truncate($check['h1']);
        $checks[$key]['title'] = truncate($check['title']);
        $checks[$key]['description'] = truncate($check['description']);
    }

    return $container->get(PhpRenderer::class)->render($response, 'show.php', [
        'url' => $url,
        'checks' => $checks,
        'flash' => $messages
    ]);
})->setName('urls.show');


$app->post('/urls/{url_id:[0-9]+}/checks', function (Request $request, Response $response, array $args) use ($container) {
    $id = $args['url_id'];
    $pdo = $container->get(PDO::class);
    $flash = $container->get('flash');
    $client = $container->get(Client::class);

    $sql = 'SELECT id, name FROM urls WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $urlData = $stmt->fetch();

    if (!$urlData) {
        throw new \Slim\Exception\HttpNotFoundException($request);
    }

    $url = $urlData['name'];

    try {
        $httpResponse = $client->get($url, ['stream' => true,'read_timeout' => 5]);
        $statusCode = $httpResponse->getStatusCode();

        $body = $httpResponse->getBody();
        $html = $body->read(500000);
        $body->close();

        $crawler = new Crawler();
        $crawler->addHtmlContent($html, 'UTF-8');

        $title = trim($crawler->filter('title')->text(''));
        $h1 = trim($crawler->filter('h1')->text(''));
        $descNode = $crawler->filterXPath('//meta[translate(@name, "DESCRIPTION", "description")="description"]');
        $description = $descNode->count() > 0 ? trim($descNode->attr('content') ?? '') : '';

        $sql = 'INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at)
                VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'url_id'      => $id,
            'status_code' => $statusCode,
            'h1'          => truncate($h1),
            'title'       => truncate($title),
            'description' => truncate($description),
            'created_at'  => Carbon::now(),
        ]);

        $flash->addMessage('success', 'Страница успешно проверена');
    } catch (GuzzleException $e) {
        $flash->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    return $response->withHeader('Location', '/urls/' . $id)->withStatus(302);
})->setName('urls.checks');


function formatDate(string $date)
{
    return $date ? Carbon::parse($date)->format('Y-m-d') : null;
}

function truncate(string|null $text, int $limit = 200): string
{
    $text ??= '';
    return mb_strlen($text) <= $limit ? $text : mb_substr($text, 0, $limit) . '...';
}

$app->run();
