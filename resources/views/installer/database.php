<?php
/** @var array<string,mixed> $values */
$errors = flash_errors();
?>
<h1>Base de donnees</h1>
<p class="subtitle">Renseignez les informations de connexion a votre base MySQL/MariaDB.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>
<div id="test-result"></div>

<form method="post" action="/install/database" id="db-form">
  <?= csrf_field() ?>
  <div class="row">
    <div>
      <label for="host">Hote</label>
      <input type="text" id="host" name="host" value="<?= e($values['host']) ?>" required>
    </div>
    <div>
      <label for="port">Port</label>
      <input type="number" id="port" name="port" value="<?= e($values['port']) ?>" required>
    </div>
  </div>
  <label for="database">Nom de la base de donnees</label>
  <input type="text" id="database" name="database" value="<?= e($values['database']) ?>" required>
  <div class="row">
    <div>
      <label for="username">Utilisateur</label>
      <input type="text" id="username" name="username" value="<?= e($values['username']) ?>" required>
    </div>
    <div>
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password">
    </div>
  </div>
  <label for="prefix">Prefixe des tables (facultatif)</label>
  <input type="text" id="prefix" name="prefix" value="<?= e($values['prefix']) ?>" placeholder="ex: aip_">

  <div class="reset-panel">
    <div class="checkbox-row">
      <input type="checkbox" id="reset_database" name="reset_database" value="1">
      <label for="reset_database">Reinitialiser les tables de cette application avant l'installation</label>
    </div>
    <p class="small">Utilisez cette option apres une installation incomplete. Elle supprime uniquement les tables declarees par Artisan IA Pro, avec et sans le prefixe saisi.</p>
    <div id="reset-confirmation" hidden>
      <label for="reset_confirmation">Saisissez <strong>SUPPRIMER</strong> pour confirmer</label>
      <input type="text" id="reset_confirmation" name="reset_confirmation" autocomplete="off">
    </div>
  </div>

  <div class="actions">
    <button type="button" class="btn secondary" id="test-btn">Tester la connexion</button>
    <button type="submit">Creer les tables et continuer</button>
  </div>
</form>

<script>
document.getElementById('test-btn').addEventListener('click', async function () {
  const form = document.getElementById('db-form');
  const data = new FormData(form);
  const resultBox = document.getElementById('test-result');
  resultBox.innerHTML = '<div class="alert">Test en cours...</div>';
  try {
    const res = await fetch('/install/database/test', { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    resultBox.innerHTML = '<div class="alert ' + (json.ok ? 'ok' : 'error') + '">' + json.message + '</div>';
  } catch (e) {
    resultBox.innerHTML = '<div class="alert error">Erreur reseau lors du test.</div>';
  }
});
document.getElementById('reset_database').addEventListener('change', function () {
  const confirmation = document.getElementById('reset-confirmation');
  confirmation.hidden = !this.checked;
  document.getElementById('reset_confirmation').required = this.checked;
});
</script>
