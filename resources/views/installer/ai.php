<?php
/**
 * @var array<string,array<string,string>> $providers
 * @var \App\Models\AiProvider|null $aiProvider
 */
$errors = flash_errors();
$current = static fn (string $key, string $default = '') => e($aiProvider?->getAttribute($key) ?? $default);
?>
<h1>Intelligence artificielle</h1>
<p class="subtitle">Choisissez un fournisseur pour la generation automatique des contenus, ou la redaction manuelle.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>
<div id="test-result"></div>

<form method="post" action="/install/ai" id="ai-form">
  <?= csrf_field() ?>

  <label for="provider">Fournisseur</label>
  <select id="provider" name="provider">
    <?php foreach ($providers as $key => $def): ?>
      <option value="<?= e($key) ?>" <?= ($aiProvider?->getAttribute('provider') ?? 'none') === $key ? 'selected' : '' ?>><?= e($def['label']) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="api_key">Clé API <span id="api-key-provider"></span></label>
  <input type="password" id="api_key" name="api_key" placeholder="<?= $aiProvider?->getAttribute('api_key_encrypted') ? 'Deja enregistree - laisser vide pour conserver' : '' ?>" autocomplete="off">
  <p class="hint">La cle est chiffree avant enregistrement et n'est jamais affichee en clair.</p>

  <div class="row">
    <div>
      <label for="model">Modele</label>
      <input type="text" id="model" name="model" value="<?= $current('model') ?>" placeholder="ex : gpt-4.1-mini, claude-sonnet-5, gemini-2.5-flash">
    </div>
    <div>
      <label for="base_url">URL personnalisee de l'API (facultatif)</label>
      <input type="text" id="base_url" name="base_url" value="<?= $current('base_url') ?>">
    </div>
  </div>

  <div class="row">
    <div>
      <label for="temperature">Temperature</label>
      <input type="number" step="0.1" min="0" max="2" id="temperature" name="temperature" value="<?= $current('temperature', '0.6') ?>">
    </div>
    <div>
      <label for="max_tokens">Nombre maximal de tokens</label>
      <input type="number" id="max_tokens" name="max_tokens" value="<?= $current('max_tokens', '2000') ?>">
    </div>
  </div>

  <div class="row">
    <div>
      <label for="language">Langue de generation</label>
      <input type="text" id="language" name="language" value="<?= $current('language', 'fr') ?>">
    </div>
    <div>
      <label for="tone">Ton redactionnel</label>
      <input type="text" id="tone" name="tone" value="<?= $current('tone', 'professionnel et rassurant') ?>">
    </div>
  </div>

  <div class="actions">
    <a class="btn secondary" href="/install/locations">Retour</a>
    <div style="display:flex;gap:10px">
      <button type="button" class="btn secondary" id="test-btn">Tester la connexion</button>
      <button type="submit">Continuer</button>
    </div>
  </div>
</form>

<script>
const providerDefaults = <?= json_encode($providers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const providerSelect = document.getElementById('provider');
const modelInput = document.getElementById('model');
const baseUrlInput = document.getElementById('base_url');
const apiKeyProvider = document.getElementById('api-key-provider');
if (modelInput.value.toLowerCase().startsWith('gemini-') || baseUrlInput.value.includes('generativelanguage.googleapis.com')) {
  providerSelect.value = 'gemini';
}
function updateProviderFields(fillDefaults) {
  const selected = providerSelect.value;
  const definition = providerDefaults[selected] || {};
  apiKeyProvider.textContent = selected === 'gemini' ? '(clé gratuite créée dans Google AI Studio)' : '';
  if (fillDefaults) {
    modelInput.value = definition.default_model || '';
    baseUrlInput.value = definition.default_base_url || '';
  }
}
providerSelect.addEventListener('change', function () { updateProviderFields(true); });
updateProviderFields(false);

document.getElementById('test-btn').addEventListener('click', async function () {
  const form = document.getElementById('ai-form');
  const data = new FormData(form);
  const resultBox = document.getElementById('test-result');
  resultBox.innerHTML = '<div class="alert">Test en cours...</div>';
  try {
    const res = await fetch('/install/ai/test', { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    resultBox.innerHTML = '<div class="alert ' + (json.ok ? 'ok' : 'error') + '">' + json.message + '</div>';
  } catch (e) {
    resultBox.innerHTML = '<div class="alert error">Erreur reseau lors du test.</div>';
  }
});
</script>
