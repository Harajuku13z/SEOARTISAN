<?php
/** @var array<int,array<string,mixed>> $suggested @var array<string,string> $groups */
$errors = flash_errors();
?>
<div class="admin-topbar"><h1>Ajouter un service</h1></div>
<?php if (!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<?php if ($suggested !== []): ?>
<div class="card">
  <h3 style="margin-top:0">Services suggeres pour votre metier</h3>
  <div class="row3">
    <?php foreach ($suggested as $s): ?>
      <form method="post" action="/admin/services">
        <?= csrf_field() ?>
        <input type="hidden" name="service_id" value="<?= (int) $s['id'] ?>">
        <input type="hidden" name="public_name" value="<?= e($s['name']) ?>">
        <input type="hidden" name="description" value="<?= e($s['default_description'] ?? '') ?>">
        <select name="service_group"><option value="">Choisir le groupe</option><?php foreach($groups as $slug=>$label): ?><option value="<?= e($slug) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
        <button type="submit" class="btn secondary sm" style="width:100%;margin-bottom:10px"><?= e($s['name']) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0">Service personnalise</h3>
  <form method="post" action="/admin/services">
    <?= csrf_field() ?>
    <label>Nom du service</label>
    <input type="text" name="public_name" required>
    <label>Description courte</label>
    <textarea name="description" rows="2"></textarea>
    <label>Groupe de services</label><select name="service_group"><option value="">Non classé</option><?php foreach($groups as $slug=>$label): ?><option value="<?= e($slug) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
    <button type="submit">Ajouter</button>
  </form>
</div>
