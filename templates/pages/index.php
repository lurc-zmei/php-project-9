<h1 class="display-6 mb-4">Сайты</h1>

<table class="table table-bordered table-hover" data-test="urls">
  <thead>
    <tr>
      <th scope="col">ID</th>
      <th scope="col">Имя</th>
      <th scope="col">Дата создания</th>
      <th scope="col">Код ответа</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <p>Записей пока нет. Добавьте первый URL на главной странице!</p>
    <?php else: ?>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td>
            <a href="<?= $router->urlFor('urls.show', ['id' => $row['id']]) ?>" aria-label="Просмотр сайта <?= htmlspecialchars($row['name']) ?>"><?= htmlspecialchars($row['name']) ?></a>
          </td>
          <td><?= htmlspecialchars($row['created_at']) ?></td>
          <td><?= $row['last_status_code'] ?? '' ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>