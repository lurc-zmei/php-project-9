<html>
  <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </head>
 <body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid d-flex flex-wrap align-items-center">
        <a class="navbar-brand mb-0" href="/">Анализатор страниц</a>
        <a class="nav-link mb-0 ms-3 link-light link-opacity-75" href="/urls">Сайты</a>
    </div>
</nav>
<main class="flex-grow-1">
    <div class="container-lg mt-3">
        <div class="row">
            <div class="col-12 col-md-10 col-lg-8 mx-auto border rounded-3 bg-light p-5">
                <h1 class="display-3">Анализатор страниц</h1>
                <p class="lead">Бесплатно проверяйте сайты на SEO-пригодность</p>
                <form action="/urls" method="post" class="row">
                    <div class="col-8">
                        <label for="url" class="visually-hidden">Url для проверки</label>
                        <input
                            class="form-control form-control-lg"
                            type="text"
                            id="url"
                            name="url"
                            value=""
                            placeholder="https://www.example.com">
                    </div>
                    <div class="col-2">
                        <input class="btn btn-primary btn-lg ms-3 px-5 text-uppercase mx-3" type="submit" value="Проверить">
                    </div>
                </form>
            </div>
        </div>
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