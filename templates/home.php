    <div class="row">
        <div class="col-12 col-md-10 col-lg-8 mx-auto border rounded-3 bg-light p-5">
            <h1 class="display-3"><?= htmlspecialchars($title ?? 'Анализатор страниц') ?></h1>
            <p class="lead">Бесплатно проверяйте сайты на SEO-пригодность</p>

            <form action="/urls" method="post" class="row">
                <div class="col-8">
                    <label for="url" class="visually-hidden">Url для проверки</label>
                    <input class="form-control form-control-lg"
                           type="text"
                           id="url"
                           name="url"
                           value="<?= htmlspecialchars($oldInput['url'] ?? '') ?>"
                           placeholder="https://www.example.com">
                </div>
                <div class="col-2">
                    <input class="btn btn-primary btn-lg ms-3 px-5 text-uppercase mx-3"
                           type="submit"
                           value="Проверить">
                </div>
            </form>
        </div>
    </div>