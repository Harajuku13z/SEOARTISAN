<?php
/** @var array<int,\App\Models\Page> $pages */
$success = flash_message('success');
?>
<div class="admin-topbar"><h1>Pages</h1></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>

<table>
  <tr><th>Titre</th><th>Type</th><th>URL</th><th>Statut</th><th>Contenu</th><th></th></tr>
  <?php foreach ($pages as $page): ?>
    <tr>
      <td><?= e($page->getAttribute('title') ?: $page->getAttribute('h1')) ?></td>
      <td><?= e($page->getAttribute('type')) ?></td>
      <td><a href="/<?= e($page->getAttribute('slug')) ?>" target="_blank">/<?= e($page->getAttribute('slug')) ?></a></td>
      <td><span class="badge <?= e($page->getAttribute('status')) ?>"><?= e($page->getAttribute('status')) ?></span></td>
      <td><?= $page->getAttribute('content_is_placeholder') ? '<span class="badge draft">a generer</span>' : '<span class="badge published">ok</span>' ?></td>
      <td><a class="btn secondary sm" href="/admin/pages/<?= (int) $page->id() ?>">Modifier</a></td>
    </tr>
  <?php endforeach; ?>
</table>
