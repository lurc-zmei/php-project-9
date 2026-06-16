<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($message) ?></p>

<?php if (isset($features) && is_array($features)): ?>
    <h3>Основная информация:</h3>
    <ul>
        <?php foreach ($features as $feature): ?>
            <li><?= htmlspecialchars($feature) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>



Ключевая разметка таблицы со списком URL
<table data-test="urls">
  <thead>
    <tr>
      <th>ID</th>
      <th>Имя</th>
      <th>Дата создания</th>
      <th>Код ответа</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>1</td>
      <td><a href="/urls/1">https://example.com</a></td>
      <td>2024-01-01</td>
      <td>200</td>
    </tr>
  </tbody>
</table>