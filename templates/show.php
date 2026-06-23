<h1 class="display-6 mb-4">Сайт: <?= htmlspecialchars($url['name']) ?></h1>

<table class="table table-bordered" data-test="url">
    <thead>
        <tr>
            <th scope="col">Поле</th>
            <th scope="col">Значение</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th scope="row">ID</th>
            <td><?= $url['id'] ?></td>
        </tr>
        <tr>
            <th scope="row">Имя</th>
            <td><?= htmlspecialchars($url['name']) ?></td>
        </tr>
        <tr>
            <th scope="row">Дата создания</th>
            <td><?= htmlspecialchars($url['created_at']) ?></td>
        </tr>
    </tbody>
</table>

<h2 class="display-6 mt-5 mb-3">Проверки</h2>

<form method="post" action="/urls/<?= $url['id'] ?>/checks" class="mb-4">
    <input class="btn btn-primary btn-lg px-4 text-uppercase" type="submit" value="Запустить проверку">
</form>

<table class="table table-bordered" data-test="checks">
    <thead>
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Код ответа</th>
            <th scope="col">h1</th>
            <th scope="col">title</th>
            <th scope="col">description</th>
            <th scope="col">Дата создания</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>