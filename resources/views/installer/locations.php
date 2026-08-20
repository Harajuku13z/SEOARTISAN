<?php
/**
 * @var array<int,\App\Models\Department> $departments
 * @var array<int,array<string,mixed>> $existingLocations
 */
$errors = flash_errors();
?>
<h1>Zones d'intervention</h1>
<p class="subtitle">Deux modes possibles : importer les communes d'un departement, ou chercher un rayon autour de votre ville principale.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>

<?php if ($existingLocations !== []): ?>
  <div class="alert ok"><?= count($existingLocations) ?> ville(s) deja enregistree(s). Une nouvelle recherche ci-dessous permet d'en ajouter d'autres.</div>
<?php endif; ?>

<fieldset>
  <legend>Mode A — par departement</legend>
  <div class="row">
    <select id="department_id">
      <option value="">Choisir un departement</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?= e($dept->id()) ?>"><?= e($dept->getAttribute('name')) ?> (<?= e($dept->getAttribute('code')) ?>)</option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="btn secondary" id="import-department">Importer les communes</button>
  </div>
</fieldset>

<fieldset>
  <legend>Mode B — rayon autour d'une ville</legend>
  <div class="location-step">
    <strong>1. Choisir la ville principale</strong>
    <div class="row">
      <input type="text" id="postal_code" placeholder="Code postal (ex: 71100)">
      <button type="button" class="btn secondary" id="find-postal">Trouver les villes</button>
    </div>
    <div id="center-choice" hidden>
      <label for="center-insee">Ville principale correspondant au code postal</label>
      <select id="center-insee"></select>
    </div>
  </div>
  <div class="location-step" id="radius-step" hidden>
    <strong>2. Choisir le rayon autour de cette ville</strong>
    <div class="row">
    <select id="radius_km">
      <option value="20">20 km</option>
      <option value="30" selected>30 km</option>
      <option value="50">50 km</option>
      <option value="100">100 km</option>
    </select>
    <button type="button" class="btn secondary" id="search-radius">Rechercher</button>
    </div>
  </div>
</fieldset>

<div id="search-status" class="hint"></div>

<form method="post" action="/install/locations" id="locations-form">
  <?= csrf_field() ?>
  <input type="hidden" name="cities_payload" id="cities-payload">
  <input type="hidden" name="primary_insee" id="primary-insee">
  <div id="cities-tools" hidden>
    <label for="city-filter">Rechercher une commune dans le rayon</label><input type="search" id="city-filter" placeholder="Ex : Beaune">
    <p class="hint">Ville principale : <strong id="primary-city-label"></strong></p>
  </div>
  <div id="cities-results"></div>
  <div class="actions">
    <a class="btn secondary" href="/install/services">Retour</a>
    <button type="submit">Continuer</button>
  </div>
</form>

<template id="city-row-template">
  <div class="checkbox-row city-result" data-city-name="__SEARCH_NAME__">
    <input type="checkbox" name="selected_insee[]" value="__INSEE__" checked>
    <label>__NAME__ (__POSTAL__) — dept. __DEPT__ __DISTANCE__</label>
  </div>
</template>

<script>
(function () {
  let index = 0;
  const results = document.getElementById('cities-results');
  const template = document.getElementById('city-row-template');
  const status = document.getElementById('search-status');
  const payload = document.getElementById('cities-payload');
  const primary = document.getElementById('primary-insee');
  const primaryLabel = document.getElementById('primary-city-label');
  const tools = document.getElementById('cities-tools');

  function renderCities(cities) {
    results.innerHTML = '';
    payload.value = JSON.stringify(cities);
    tools.hidden = cities.length === 0;
    index = 0;
    const departmentGroups = new Map();
    cities.forEach(function (c) {
      const departmentCode = c.department_code || 'Non renseigne';
      if (!departmentGroups.has(departmentCode)) {
        const group = document.createElement('section');
        group.className = 'department-group';
        group.dataset.department = departmentCode;
        group.innerHTML = '<div class="department-group-head"><strong>Departement ' + departmentCode + '</strong><span class="department-count"></span></div><div class="department-cities"></div>';
        results.appendChild(group);
        departmentGroups.set(departmentCode, { element: group, count: 0 });
      }
      const html = template.innerHTML
        .replaceAll('__NAME__', c.name || '')
        .replaceAll('__SEARCH_NAME__', (c.name || '').toLocaleLowerCase('fr'))
        .replaceAll('__POSTAL__', c.postal_code || '')
        .replaceAll('__INSEE__', c.insee_code || '')
        .replaceAll('__DEPT__', c.department_code || '')
        .replaceAll('__DISTANCE__', c.distance_km ? ('— ' + c.distance_km + ' km') : '');
      const wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      const group = departmentGroups.get(departmentCode);
      group.element.querySelector('.department-cities').appendChild(wrapper.firstElementChild);
      group.count++;
      group.element.querySelector('.department-count').textContent = group.count + ' commune' + (group.count > 1 ? 's' : '');
      index++;
    });
  }

  document.getElementById('city-filter').addEventListener('input', function () {
    const query = this.value.trim().toLocaleLowerCase('fr');
    document.querySelectorAll('.city-result').forEach(function (row) {
      row.hidden = query !== '' && !row.dataset.cityName.includes(query);
    });
    document.querySelectorAll('.department-group').forEach(function (group) {
      group.hidden = !Array.from(group.querySelectorAll('.city-result')).some(function (row) { return !row.hidden; });
    });
  });

  document.getElementById('find-postal').addEventListener('click', async function () {
    const postalCode = document.getElementById('postal_code').value.trim();
    if (!postalCode) { status.textContent = 'Saisissez un code postal.'; return; }
    status.textContent = 'Recherche de la ville principale...';
    const res = await fetch('/install/locations/postal-search', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
      body: 'postal_code=' + encodeURIComponent(postalCode) + '&_csrf_token=<?= e(csrf_token()) ?>'
    });
    const json = await res.json();
    if (!json.ok) { status.textContent = json.message; return; }
    const select = document.getElementById('center-insee');
    select.innerHTML = '';
    json.cities.forEach(function (city) {
      const option = document.createElement('option');
      option.value = city.insee_code;
      option.textContent = city.name + ' (' + (city.postal_code || postalCode) + ')';
      option.dataset.name = city.name;
      select.appendChild(option);
    });
    document.getElementById('center-choice').hidden = false;
    document.getElementById('radius-step').hidden = false;
    status.textContent = json.cities.length + ' ville(s) trouvee(s). Choisissez la ville principale puis le rayon.';
  });

  document.getElementById('import-department').addEventListener('click', async function () {
    const departmentId = document.getElementById('department_id').value;
    if (!departmentId) { return; }
    status.textContent = 'Import en cours...';
    const res = await fetch('/install/locations/department-cities', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
      body: 'department_id=' + encodeURIComponent(departmentId) + '&_csrf_token=<?= e(csrf_token()) ?>'
    });
    const json = await res.json();
    status.textContent = json.ok ? (json.cities.length + ' communes trouvees.') : json.message;
    if (json.ok) { renderCities(json.cities); }
  });

  document.getElementById('search-radius').addEventListener('click', async function () {
    const button = this;
    const postalCode = document.getElementById('postal_code').value;
    const centerSelect = document.getElementById('center-insee');
    const centerInsee = centerSelect.value;
    const radiusKm = document.getElementById('radius_km').value;
    if (!postalCode) { return; }
    status.textContent = 'Recherche en cours...';
    button.disabled = true;
    const controller = new AbortController();
    const timeout = setTimeout(function () { controller.abort(); }, 45000);
    try {
      const res = await fetch('/install/locations/radius-search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: 'postal_code=' + encodeURIComponent(postalCode) + '&center_insee=' + encodeURIComponent(centerInsee) + '&radius_km=' + encodeURIComponent(radiusKm) + '&_csrf_token=<?= e(csrf_token()) ?>',
        signal: controller.signal
      });
      const json = await res.json();
      status.textContent = json.ok
        ? (json.total_count + ' communes trouvees dans un rayon de ' + radiusKm + ' km, departements ' + json.department_codes.join(', ') + '. Toutes les communes sont affichees ci-dessous.')
        : (json.message || 'La recherche a echoue.');
      if (json.ok) {
        primary.value = json.center.insee_code;
        primaryLabel.textContent = json.center.name + ' (' + (json.center.postal_code || postalCode) + ')';
        renderCities(json.cities);
      }
    } catch (error) {
      status.textContent = error.name === 'AbortError'
        ? 'La recherche a depasse 45 secondes. Veuillez reessayer.'
        : 'Erreur reseau pendant la recherche. Veuillez reessayer.';
    } finally {
      clearTimeout(timeout);
      button.disabled = false;
    }
  });
})();
</script>
