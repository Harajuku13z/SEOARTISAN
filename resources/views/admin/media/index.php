<?php
/** @var array<int,\App\Models\Media> $media */
$errors = flash_errors();
?>
<div class="admin-topbar"><h1>Mediatheque</h1></div>
<?php if (!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<div class="card">
  <form method="post" action="/admin/media" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:end">
    <?= csrf_field() ?>
    <div style="flex:1"><label>Ajouter un fichier</label><input type="file" name="file" accept="image/*" required></div>
    <button type="submit" style="margin-bottom:14px">Televerser</button>
  </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px">
  <?php foreach ($media as $item): ?>
    <div class="card" style="padding:10px">
      <img src="<?= e($item->getAttribute('url')) ?>" alt="<?= e($item->getAttribute('alt_text')) ?>" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:6px;margin-bottom:8px">
      <div style="font-size:11px;color:var(--muted);word-break:break-all"><?= e($item->getAttribute('type')) ?></div>
      <form method="post" action="/admin/media/<?= (int) $item->id() ?>/delete" onsubmit="return confirm('Supprimer ce fichier ?')">
        <?= csrf_field() ?>
        <button type="submit" class="btn danger sm" style="width:100%;margin-top:6px">Supprimer</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>
