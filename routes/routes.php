<?php

namespace App\Routes;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Valitron\Validator;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use Slim\Exception\HttpNotFoundException;

return function ($app, $container) {
    $getRouter = function () use ($container) {
        return $container->get('renderer')->getAttribute('router');
    };

    $getFlashData = function ($container) {
        $flash = $container->get('flash');
        return $flash->getMessages();
    };

    $app->get('/', function (Request $request, Response $response) use ($container, $getFlashData) {
        $flash = $getFlashData($container);

        return $container->get('renderer')->render($response, 'pages/home.php', [
            'flash' => $flash,
            'oldInput' => '',
            'errors' => []
        ]);
    })->setName('home');


    $app->get('/urls', function (Request $request, Response $response) use ($container, $getFlashData) {
        $flash = $getFlashData($container);
        $pdo = $container->get('pdo');

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

        return $container->get('renderer')
            ->render($response, 'pages/index.php', ['rows' => $rows, 'flash' => $flash]);
    })->setName('urls.index');


    $app->post('/urls', function (Request $request, Response $response) use ($container, $getRouter) {
        $data = $request->getParsedBody();
        $url = trim($data['url'] ?? '');

        $pdo = $container->get('pdo');
        $flash = $container->get('flash');

        if (empty($url)) {
            return $container->get('renderer')
                ->render($response->withStatus(422), 'pages/home.php', [
                    'errors' => ['url' => ['URL не должен быть пустым']],
                    'oldInput' => $url
                ]);
        }

        $validator = new Validator(['url' => $url]);
        $validator->rule('lengthMax', 'url', 255)->message('URL превышает 255 символов');
        $validator->rule('regex', 'url', '/^https?:\/\/[^\s]+$/i')->message('Некорректный URL');

        if (!$validator->validate()) {
            return $container->get('renderer')
                ->render($response->withStatus(422), 'pages/home.php', [
                    'errors' => $validator->errors(),
                    'oldInput' => $url
                ]);
        }

        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? '';
        $normalizedUrl = strtolower("{$scheme}://{$host}");

        $sql = 'SELECT id FROM urls WHERE name = :url';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['url' => $normalizedUrl]);
        $urlExist = $stmt->fetch();

        if ($urlExist) {
            $flash->addMessage('success', 'Страница уже существует');
            return $response->withHeader('Location', $getRouter()
                ->urlFor('urls.show', ['id' => $urlExist['id']]))->withStatus(302);
        }

        $sql = 'INSERT INTO urls (name, created_at) VALUES (:url, :created_at)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'url' => $normalizedUrl,
            'created_at' => Carbon::now()
        ]);

        $newId = $pdo->lastInsertId();
        $flash->addMessage('success', 'Страница успешно добавлена');

        return $response->withHeader('Location', $getRouter()
            ->urlFor('urls.show', ['id' => $newId]))->withStatus(302);
    })->setName('urls.store');


    $app->get('/urls/{id:[0-9]+}', function (
        Request $request,
        Response $response,
        array $args
    ) use (
        $container,
        $getFlashData
    ) {
        $flash = $getFlashData($container);
        $id = $args['id'];
        $pdo = $container->get('pdo');

        $sql = 'SELECT * FROM urls WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $url = $stmt->fetch();

        if (!$url) {
            throw new HttpNotFoundException($request);
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

        return $container->get('renderer')->render($response, 'pages/show.php', [
            'url' => $url,
            'checks' => $checks,
            'flash' => $flash
        ]);
    })->setName('urls.show');


    $app->post('/urls/{url_id:[0-9]+}/checks', function (
        Request $request,
        Response $response,
        array $args
    ) use (
        $container,
        $getRouter
    ) {
        $id = $args['url_id'];
        $pdo = $container->get('pdo');
        $flash = $container->get('flash');
        $client = $container->get('client');

        $sql = 'SELECT id, name FROM urls WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $urlData = $stmt->fetch();

        if (!$urlData) {
            throw new \Slim\Exception\HttpNotFoundException($request);
        }

        $url = $urlData['name'];

        try {
            $httpResponse = $client->get($url, [
                'stream' => true,
                'read_timeout' => 5,
            ]);
            $statusCode = $httpResponse->getStatusCode();

            $body = $httpResponse->getBody();
            $html = $body->read(800000);
            $body->close();

            $crawler = new Crawler();
            $crawler->addHtmlContent($html, 'UTF-8');

            $title = trim($crawler->filter('title')->text(''));
            $h1 = trim($crawler->filter('h1')->text(''));
            $descNode = $crawler->filterXPath('//meta[
                translate(@name, "DESCRIPTION", "description")="description" 
                or @property="og:description"
            ]');
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

        return $response->withHeader('Location', $getRouter()->urlFor('urls.show', ['id' => $id]))->withStatus(302);
    })->setName('urls.checks');
};
