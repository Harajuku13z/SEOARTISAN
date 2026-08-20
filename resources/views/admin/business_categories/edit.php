<?php
/**
 * @var \App\Models\BusinessCategory $category
 * @var array<int,\App\Models\Service> $allServices
 * @var array<int,int> $linkedServiceIds
 */
$success = flash_message('success');
?>
<div class="admin-topbar"><h1>Metier : <?= e($category->getAttribute('name')) ?></h1></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>

<form method="post" action="/admin/business-categories/<?= (int) $category->id() ?>">
  <?= csrf_field() ?>
  <div class="card">
    <fieldset class="fieldset">
      <legend>Informations</legend>
      <label>Nom</label>
      <input type="text" name="name" value="<?= e($category->getAttribute('name')) ?>" required>
      <label>Slug (URL)</label>
      <input type="text" name="slug" value="<?= e($category->getAttribute('slug')) ?>">
      <label>Type Schema.org</label>
      <input type="text" name="schema_org_type" value="<?= e($category->getAttribute('schema_org_type')) ?>" placeholder="ex : RoofingContractor, Plumber, Electrician, HVACBusiness, GeneralContractor">
      <label>Description</label>
      <textarea name="description" rows="2"><?= e($category->getAttribute('description')) ?></textarea>
      <div class="checkbox-row">
        <input type="checkbox" id="is_active" name="is_active" value="1" <?= $category->getAttribute('is_active') ? 'checked' : '' ?>>
        <label for="is_active">Metier actif</label>
      </div>
    </fieldset>
  </div>

  <div class="card">
    <fieldset class="fieldset">
      <legend>Services associes (suggeres pour ce metier)</legend>
      <?php foreach ($allServices as $service): ?>
        <div class="checkbox-row">
          <input type="checkbox" id="svc_<?= (int) $service->id() ?>" name="service_ids[]" value="<?= (int) $service->id() ?>" <?= in_array((int) $service->id(), $linkedServiceIds, true) ? 'checked' : '' ?>>
          <label for="svc_<?= (int) $service->id() ?>"><?= e($service->getAttribute('name')) ?></label>
        </div>
      <?php endforeach; ?>
    </fieldset>
  </div>

  <div class="actions-bar">
    <a class="btn secondary" href="/admin/business-categories">Retour</a>
    <button type="submit">Enregistrer</button>
  </div>
</form>
