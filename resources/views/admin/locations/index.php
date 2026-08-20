<?php
/**
 * @var array<int,array<string,mixed>> $locations
 * @var array<int,\App\Models\Department> $departments
 */
$success = flash_message('success');
?>
<div class="admin-topbar"><h1>Zones d'intervention</h1></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>

<div class="card">
  <table>
    <tr><th>Ville</th><th>Code postal</th><th>Principale</th><th>Priorite SEO</th><th>Statut</th><th></th></tr>
    <?php foreach ($locations as $loc): ?>
      <tr>
        <td><?= e($loc['name']) ?></td>
        <td><?= e($loc['postal_code']) ?></td>
        <td>
          <?php if (!empty($loc['is_primary'])): ?>
            <span class="badge published">principale</span>
          <?php else: ?>
            <form method="post" action="/admin/locations/<?= (int) $loc['company_location_id'] ?>"><?= csrf_field() ?><input type="hidden" name="make_primary" value="1"><button type="submit" class="btn secondary sm">Definir principale</button></form>
          <?php endif; ?>
        </td>
        <td>
          <form method="post" action="/admin/locations/<?= (int) $loc['company_location_id'] ?>" style="display:flex;gap:6px">
            <?= csrf_field() ?>
            <input type="number" name="seo_priority" value="<?= (int) $loc['seo_priority'] ?>" style="width:70px;margin:0">
            <input type="hidden" name="is_active" value="<?= !empty($loc['location_is_active']) ? '1' : '' ?>">
            <button type="submit" class="btn secondary sm">Mettre a jour</button>
          </form>
        </td>
        <td><span class="badge <?= !empty($loc['location_is_active']) ? 'published' : 'archived' ?>"><?= !empty($loc['location_is_active']) ? 'active' : 'inactive' ?></span></td>
        <td>
          <form method="post" action="/admin/locations/<?= (int) $loc['company_location_id'] ?>/delete" onsubmit="return confirm('Retirer cette ville ?')">
            <?= csrf_field() ?><button type="submit" class="btn danger sm">Retirer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h3 style="margin-top:0">Ajouter des villes</h3>
  <fieldset class="fieldset">
    <legend>Mode A - par departement</legend>
    <div class="row">
      <select id="department_id">
        <option value="">Choisir un departement</option>
        <?php foreach ($departments as $dept): ?><option value="<?= (int) $dept->id() ?>"><?= e($dept->getAttribute('name')) ?> (<?= e($dept->getAttribute('code')) ?>)</option><?php endforeach; ?>
      </select>
      <button type="button" class="btn secondary" id="import-department">Importer les communes</button>
    </div>
  </fieldset>
  <fieldset class="fieldset">
    <legend>Mode B - rayon autour d'une ville</legend>
    <div class="row3">
      <input type="text" id="postal_code" placeholder="Code postal">
      <select id="radius_km"><option value="20">20 km</option><option value="30" selected>30 km</option><option value="50">50 km</option><option value="100">100 km</option></select>
      <button type="button" class="btn secondary" id="search-radius">Rechercher</button>
    </div>
  </fieldset>
  <div id="search-status" class="hint" style="font-size:12px;color:var(--muted);margin-bottom:10px"></div>

  <form method="post" action="/admin/locations">
    <?= csrf_field() ?>
    <div id="cities-results"></div>
    <button type="submit" class="btn secondary">Ajouter les villes cochees</button>
  </form>
</div>

<template id="city-row-template">
  <div class="checkbox-row">
    <input type="checkbox" name="cities[__INDEX__][selected]" value="1" checked>
    <label>__NAME__ (__POSTAL__) __DISTANCE__</label>
    <input type="hidden" name="cities[__INDEX__][name]" value="__NAME__">
    <input type="hidden" name="cities[__INDEX__][insee_code]" value="__INSEE__">
    <input type="hidden" name="cities[__INDEX__][postal_code]" value="__POSTAL__">
    <input type="hidden" name="cities[__INDEX__][latitude]" value="__LAT__">
    <input type="hidden" name="cities[__INDEX__][longitude]" value="__LON__">
    <input type="hidden" name="cities[__INDEX__][population]" value="__POP__">
    <input type="hidden" name="cities[__INDEX__][department_code]" value="__DEPT__">
    <input type="hidden" name="cities[__INDEX__][distance_km]" value="__DIST__">
  </div>
</template>
<script>
(function () {
  let index = 0;
  const results = document.getElementById('cities-results');
  const template = document.getElementById('city-row-template');
  const status = document.getElementById('search-status');
  const csrfToken = '<?= e(csrf_token()) ?>';

  function renderCities(cities) {
    results.innerHTML = '';
    index = 0;
    cities.forEach(function (c) {
      const html = template.innerHTML
        .replaceAll('__INDEX__', String(index))
        .replaceAll('__NAME__', c.name || '')
        .replaceAll('__POSTAL__', c.postal_code || '')
        .replaceAll('__INSEE__', c.insee_code || '')
        .replaceAll('__LAT__', c.latitude ?? '')
        .replaceAll('__LON__', c.longitude ?? '')
        .replaceAll('__POP__', c.population ?? '')
        .replaceAll('__DEPT__', c.department_code || '')
        .replaceAll('__DISTANCE__', c.distance_km ? ('- ' + c.distance_km + ' km') : '');
      const wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      results.appendChild(wrapper.firstElementChild);
      index++;
    });
  }

  document.getElementById('import-department').addEventListener('click', async function () {
    const departmentId = document.getElementById('department_id').value;
    if (!departmentId) { return; }
    status.textContent = 'Import en cours...';
    const res = await fetch('/admin/locations/department-cities', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
      body: 'department_id=' + encodeURIComponent(departmentId) + '&_csrf_token=' + encodeURIComponent(csrfToken)
    });
    const json = await res.json();
    status.textContent = json.ok ? (json.cities.length + ' communes trouvees.') : json.message;
    if (json.ok) { renderCities(json.cities); }
  });

  document.getElementById('search-radius').addEventListener('click', async function () {
    const postalCode = document.getElementById('postal_code').value;
    const radiusKm = document.getElementById('radius_km').value;
    if (!postalCode) { return; }
    status.textContent = 'Recherche en cours...';
    const res = await fetch('/admin/locations/radius-search', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
      body: 'postal_code=' + encodeURIComponent(postalCode) + '&radius_km=' + encodeURIComponent(radiusKm) + '&_csrf_token=' + encodeURIComponent(csrfToken)
    });
    const json = await res.json();
    status.textContent = json.ok ? (json.cities.length + ' communes trouvees.') : json.message;
    if (json.ok) { renderCities(json.cities); }
  });
})();
</script>
