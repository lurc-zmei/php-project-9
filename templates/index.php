<!--<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($message) ?></p>
-->

<?php if (isset($features) && is_array($features)): ?>
  <h3>Основная информация:</h3>
  <ul>
    <?php foreach ($features as $feature): ?>
      <li><?= htmlspecialchars($feature) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

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
    <tr>
      <td>1</td>
      <td>
        <a href="/urls/5" aria-label="Просмотр сайта https://example.com">https://example.com</a>
      </td>
      <td>2026-06-15</td>
      <td>200</td>
    </tr>
  </tbody>
</table>