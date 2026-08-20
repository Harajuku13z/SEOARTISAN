<?php
/**
 * @var \App\Models\Project|null $project
 * @var array<int,array<string,mixed>> $cities
 * @var array<int,array<string,mixed>> $services
 * @var array<string,string> $serviceGroups
 * @var array<int,string> $serviceGroupMap
 */
$errors = flash_errors();
$isEdit = $project !== null;
$action = $isEdit ? '/admin/projects/' . (int) $project->id() : '/admin/projects';
?>
<div class="admin-topbar"><h1><?= $isEdit ? 'Modifier la realisation' : 'Nouvelle realisation' ?></h1></div>
<?php if (!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card">
    <label>Titre</label>
    <input type="text" name="title" value="<?= e($project?->getAttribute('title')) ?>" required>
    <label>Description</label>
    <textarea name="description" rows="3"><?= e($project?->getAttribute('description')) ?></textarea>
    <div class="row">
      <div>
        <label>Groupe de services</label>
        <select name="category" id="project-service-group"><option value="">Autre / non classée</option><?php foreach($serviceGroups as $slug=>$label): ?><option value="<?= e($slug) ?>" <?= $project?->getAttribute('category')===$slug?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select>
      </div>
      <div>
        <label>Date</label>
        <input type="date" name="project_date" value="<?= e($project?->getAttribute('project_date')) ?>">
      </div>
    </div>
    <div class="row">
      <div>
        <label>Ville</label>
        <select name="city_id">
          <option value="">Non specifiee</option>
          <?php foreach ($cities as $city): ?>
            <option value="<?= (int) $city['id'] ?>" <?= (int) $project?->getAttribute('city_id') === (int) $city['id'] ? 'selected' : '' ?>><?= e($city['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Sous-service lié</label>
        <select name="company_service_id">
          <option value="">Autre / aucun sous-service précis</option>
          <?php foreach ($services as $service): ?>
            <option value="<?= (int) $service['id'] ?>" data-group="<?= e($serviceGroupMap[(int)$service['id']] ?? '') ?>" <?= (int) $project?->getAttribute('company_service_id') === (int) $service['id'] ? 'selected' : '' ?>><?= e($service['public_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>Texte alternatif (accessibilite / SEO)</label>
    <input type="text" name="alt_text" value="<?= e($project?->getAttribute('alt_text')) ?>">
    <div class="row">
      <div><label>Photo "avant" (facultatif)</label><input type="file" name="before_image" accept="image/*"></div>
      <div><label>Photo "apres"</label><input type="file" name="after_image" accept="image/*"></div>
    </div>
    <p class="hint" style="font-size:12px;color:var(--muted);margin:-10px 0 14px">Seule "apres" suffit pour une photo unique. Les deux ensemble affichent un comparatif avant/apres.</p>
    <label>Ordre d'affichage</label>
    <input type="number" name="sort_order" value="<?= (int) $project?->getAttribute('sort_order') ?>">
    <div class="checkbox-row">
      <input type="checkbox" id="is_visible" name="is_visible" value="1" <?= ($project === null || $project->getAttribute('is_visible')) ? 'checked' : '' ?>>
      <label for="is_visible">Visible sur le site</label>
    </div>
  </div>
  <div class="actions-bar">
    <a class="btn secondary" href="/admin/projects">Retour</a>
    <button type="submit">Enregistrer</button>
  </div>
</form>
<script>document.addEventListener('DOMContentLoaded',function(){var group=document.getElementById('project-service-group'),service=document.querySelector('select[name="company_service_id"]');if(!group||!service)return;function filter(){var value=group.value;[...service.options].forEach(function(option,index){if(index===0)return;option.hidden=!!value&&option.dataset.group!==value});if(service.selectedOptions[0]&&service.selectedOptions[0].hidden)service.value=''}group.addEventListener('change',filter);service.addEventListener('change',function(){var selected=service.selectedOptions[0];if(selected&&selected.dataset.group)group.value=selected.dataset.group;filter()});filter()});</script>
