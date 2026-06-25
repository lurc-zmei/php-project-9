<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;
use Valitron\Validator;
use Carbon\Carbon;

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


AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, false, false);


$app->get('/', function (Request $request, Response $response) use ($container) {
    $flash = $container->get('flash');
    $messages = $flash->getMessages();
    $oldInput = $_SESSION['old_input'] ?? [];
    unset($_SESSION['old_input']);
    return $container->get(PhpRenderer::class)->render($response, 'home.php', ['flash' => $messages, 'oldInput' => $oldInput]);
})->setName('home');


$app->get('/urls', function (Request $request, Response $response) use ($container) {
    $pdo = $container->get(PDO::class);

    $sql = 'SELECT * FROM urls ORDER BY id DESC';
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();

    foreach ($rows as $key => $row) {
        $rows[$key]['created_at'] = formatDate($row['created_at']);
    }

    return $container->get(PhpRenderer::class)->render($response, 'index.php', compact('rows'));
})->setName('urls.index');


$app->post('/', function (Request $request, Response $response) use ($container) {
    $data = $request->getParsedBody();
    $url = trim($data['url'] ?? '');
    $pdo = $container->get(PDO::class);
    $flash = $container->get('flash');

    $validator = new Validator(['url' => $url]);
    $validator->rule('required', 'url')->message('URL не должен быть пустым');
    $validator->rule('lengthMax', 'url', 255)->message('URL превышает 255 символов');
    $validator->rule('url', 'url')->message('Некорректный URL');

    if (!$validator->validate()) {
        $errors = $validator->errors();
        $_SESSION['old_input'] = ['url' => $url];

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
        $isDuplicate = true;
    } else {
        $sql = 'INSERT INTO urls (name, created_at) VALUES (:url, :created_at)';
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([
                'url' => $url,
                'created_at' => Carbon::now()
            ]);
            $flash->addMessage('success', 'Страница успешно добавлена');
            unset($_SESSION['old_input']);
            $isDuplicate = false;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $isDuplicate = true;
            } else {
                $flash->addMessage('danger', 'Произошла ошибка при добавлении');
                $isDuplicate = false;
            }
        }
    }

    if ($isDuplicate) {
        $flash->addMessage('warning', 'Страница уже существует');
        $_SESSION['old_input'] = ['url' => $url];
    }

    return $response->withHeader('Location', '/')->withStatus(302);
});


$app->get('/urls/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($container) {
    $id = $args['id'];
    $pdo = $container->get(PDO::class);

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

    return $container->get(PhpRenderer::class)->render($response, 'show.php', compact('url', 'checks'));
})->setName('urls.show');


$app->post('/urls/{url_id:[0-9]+}/checks', function (Request $request, Response $response, array $args) use ($container) {
    $id = $args['url_id'];
    $pdo = $container->get(PDO::class);
    $flash = $container->get('flash');

    $sql = 'SELECT id FROM urls WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $url = $stmt->fetch();

    if (!$url) {
        throw new \Slim\Exception\HttpNotFoundException($request);
    }

    try {
        $sql = 'INSERT INTO url_checks (url_id, created_at) VALUES (:url_id, :created_at)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $id,
            'created_at' => Carbon::now()
        ]);

        $flash->addMessage('success', 'Страница успешно проверена');
    } catch (\PDOException $e) {
        $flash->addMessage('danger', 'Произошла ошибка при проверке, не удалось подключиться');
    }

    return $response->withHeader('Location', '/urls/' . $id)->withStatus(302);
})->setName('urls.checks');


function formatDate($date)
{
    return $date ? Carbon::parse($date)->format('Y-m-d') : null;
}

function truncate(string|null $text, int $limit = 200): string
{
    $text ??= '';
    return mb_strlen($text) <= $limit ? $text : mb_substr($text, 0, $limit) . '...';
}

$app->run();