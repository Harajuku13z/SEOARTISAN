<?php
/** @var array<int,\App\Models\BusinessCategory> $categories */
$success = flash_message('success');
?>
<div class="admin-topbar">
  <h1>Metiers</h1>
</div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>

<div class="card">
  <form method="post" action="/admin/business-categories" style="display:flex;gap:10px;align-items:end">
    <?= csrf_field() ?>
    <div style="flex:1"><label>Ajouter un metier</label><input type="text" name="name" placeholder="ex : Serrurier" required></div>
    <button type="submit" style="margin-bottom:14px">Ajouter</button>
  </form>
</div>

<table>
  <tr><th>Nom</th><th>Type Schema.org</th><th>Statut</th><th>Ordre</th><th></th></tr>
  <?php foreach ($categories as $cat): ?>
    <tr>
      <td><?= e($cat->getAttribute('name')) ?></td>
      <td><?= e($cat->getAttribute('schema_org_type') ?: '-') ?></td>
      <td><span class="badge <?= $cat->getAttribute('is_active') ? 'published' : 'archived' ?>"><?= $cat->getAttribute('is_active') ? 'actif' : 'inactif' ?></span></td>
      <td><?= (int) $cat->getAttribute('sort_order') ?></td>
      <td><a class="btn secondary sm" href="/admin/business-categories/<?= (int) $cat->id() ?>">Modifier</a></td>
    </tr>
  <?php endforeach; ?>
</table>
