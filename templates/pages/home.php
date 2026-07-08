<div class="row">
    <div class="col-12 col-md-10 col-lg-8 mx-auto border rounded-3 bg-light p-5">
        <h1 class="display-3"><?= htmlspecialchars($title ?? 'Анализатор страниц') ?></h1>
        <p class="lead">Бесплатно проверяйте сайты на SEO-пригодность</p>

        <form action="<?= $router->urlFor('urls.index') ?>" method="post" class="row">
            <div class="col-8">
                <label for="url" class="visually-hidden">Url для проверки</label>

                <input class="form-control form-control-lg <?= isset($errors['url']) ? 'is-invalid' : '' ?>"
                    type="text"
                    id="url"
                    name="url"
                    value="<?= htmlspecialchars($oldInput ?? '') ?>"
                    placeholder="https://www.example.com">

                <?php if (isset($errors['url'])): ?>
                    <div class="text-danger mt-1">
                        <?php foreach ($errors['url'] as $error): ?>
                            <?= htmlspecialchars($error) ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-2">
                <input class="btn btn-primary btn-lg ms-3 px-5 text-uppercase"
                    type="submit"
                    value="Проверить">
            </div>
        </form>
    </div>
</div>