<!DOCTYPE html>
<html lang="ru">

<head>
    <title>Страница</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>

<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid justify-content-start">
            <a class="navbar-brand" href="/">Анализатор страниц</a>
            <a class="nav-link link-light link-opacity-75" href="/urls">Сайты</a>
        </div>
    </nav>


    <main class="flex-grow-1">
        <?php if (isset($flash) && $flash instanceof \Slim\Flash\Messages): ?>
            <?php $messages = $flash->getMessages(); ?>
            <?php if (!empty($messages)): ?>
                <div class="container mt-3">
                    <?php foreach ($messages as $type => $msgs): ?>
                        <?php foreach ($msgs as $msg): ?>
                            <div class="alert alert-<?= htmlspecialchars($type) ?> fade show" role="alert">
                                <?= htmlspecialchars($msg) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>


        <div class="container-lg mt-3">
            <?= $content ?>
        </div>
    </main>


    <hr class="border-secondary-subtle mb-0">
    <footer class="py-3">
        <div class="container-lg text-center">
            <a href="https://ru.hexlet.io" class="link-primary text-decoration-none">Hexlet</a>
        </div>
    </footer>
</body>

</html>