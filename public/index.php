<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;
use FastRoute\Route;
use Valitron\Validator;

use function DI\get;

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
    return $container->get(PhpRenderer::class)->render($response,'home.php', ['flash' => $messages, 'oldInput' => $oldInput]);
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
        $flash->addMessage('warning', 'Страница уже существует');
        $_SESSION['old_input'] = ['url' => $url];
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    $sql = 'INSERT INTO urls (name) VALUES (:url)';
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute(['url' => $url]);
        $flash->addMessage('success', 'Страница успешно добавлена');
        unset($_SESSION['old_input']);
    } catch (\PDOException $e) {
        if ($e->getCode() == 23505) {
            $flash->addMessage('warning', 'Страница уже существует');
            $_SESSION['old_input'] = ['url' => $url];
        } else {
            $flash->addMessage('danger', 'Произошла ошибка при добавлении');
        }
    }
    
    return $response->withHeader('Location', '/')->withStatus(302);
});


$app->get('/urls/{id}', function(Request $request, Response $response, array $args) use($container) {
    $id = $args['id'];
    $pdo = $container->get(PDO::class);

    $sql = 'SELECT * FROM urls WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $url = $stmt->fetch();

    if (!$url) {
        throw new \Slim\Exception\HttpNotFoundException($request);
    }

    return $container->get(PhpRenderer::class)->render($response, 'show.php', compact('url'));

})->setName('urls.show');

$app->run();
