<?php
/**
 * @var array<string,array<string,string>> $providers
 * @var \App\Models\AiProvider|null $aiProvider
 * @var array<int,\App\Models\AiGeneration> $generations
 * @var float $totalEstimatedCost
 * @var int $failedCount
 */
$success = flash_message('success');
$errors = flash_errors();
$current = static fn (string $key, string $default = '') => e($aiProvider?->getAttribute($key) ?? $default);
?>
<div class="admin-topbar"><h1>Intelligence artificielle</h1></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><b><?= e($aiProvider?->getAttribute('provider') ?? 'aucun') ?></b><span>Fournisseur actif</span></div>
  <div class="stat-card"><b><?= count($generations) ?></b><span>Generations recentes</span></div>
  <div class="stat-card"><b><?= (int) $failedCount ?></b><span>Echecs au total</span></div>
  <div class="stat-card"><b><?= number_format($totalEstimatedCost, 2) ?> &euro;</b><span>Cout estime total</span></div>
</div>

<div class="card">
  <div id="test-result"></div>
  <form method="post" action="/admin/ai" id="ai-form">
    <?= csrf_field() ?>
    <label>Fournisseur</label>
    <select id="provider" name="provider">
      <?php foreach ($providers as $key => $def): ?>
        <option value="<?= e($key) ?>" <?= ($aiProvider?->getAttribute('provider') ?? 'none') === $key ? 'selected' : '' ?>><?= e($def['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Clé API <span id="api-key-provider"></span></label>
    <input type="password" name="api_key" placeholder="<?= $aiProvider?->getAttribute('api_key_encrypted') ? 'Deja enregistree - laisser vide pour conserver' : '' ?>" autocomplete="off">
    <div class="row">
      <div><label>Modele</label><input type="text" name="model" value="<?= $current('model') ?>"></div>
      <div><label>URL personnalisee</label><input type="text" name="base_url" value="<?= $current('base_url') ?>"></div>
    </div>
    <div class="row">
      <div><label>Temperature</label><input type="number" step="0.1" name="temperature" value="<?= $current('temperature', '0.6') ?>"></div>
      <div><label>Tokens max</label><input type="number" name="max_tokens" value="<?= $current('max_tokens', '2000') ?>"></div>
    </div>
    <div class="row">
      <div><label>Langue</label><input type="text" name="language" value="<?= $current('language', 'fr') ?>"></div>
      <div><label>Ton</label><input type="text" name="tone" value="<?= $current('tone', 'professionnel et rassurant') ?>"></div>
    </div>
    <div class="actions-bar">
      <button type="button" class="btn secondary" id="test-btn">Tester la connexion</button>
      <button type="submit">Enregistrer</button>
    </div>
  </form>
</div>

<div class="card">
  <div class="actions-bar" style="margin-bottom:18px">
    <h3 style="margin:0">Historique des generations</h3>
    <?php if ($failedCount > 0): ?>
      <form method="post" action="/admin/ai/retry-failed" onsubmit="return confirm('Relancer les derniers échecs avec le fournisseur actif ?');">
        <?= csrf_field() ?>
        <button type="submit">Tout générer (<?= (int) $failedCount ?>)</button>
      </form>
    <?php endif; ?>
  </div>
  <table>
    <tr><th>Type</th><th>Fournisseur</th><th>Statut</th><th>Tokens</th><th>Cout est.</th><th>Date</th><th>Action</th></tr>
    <?php foreach ($generations as $g): ?>
      <tr>
        <td><?= e($g->getAttribute('prompt_type')) ?></td>
        <td><?= e($g->getAttribute('provider')) ?></td>
        <td><span class="badge <?= $g->getAttribute('status') === 'success' ? 'published' : 'draft' ?>"><?= e($g->getAttribute('status')) ?></span></td>
        <td><?= e($g->getAttribute('tokens_used') ?? '-') ?></td>
        <td><?= $g->getAttribute('estimated_cost') ? number_format((float) $g->getAttribute('estimated_cost'), 4) . ' EUR' : '-' ?></td>
        <td><?= e($g->getAttribute('created_at')) ?></td>
        <td>
          <?php if ($g->getAttribute('status') === 'failed'): ?>
            <form method="post" action="/admin/ai/retry/<?= (int) $g->id() ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn secondary">Générer</button>
            </form>
          <?php else: ?>
            <span class="muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<script>
const providerDefaults = <?= json_encode($providers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const providerSelect = document.getElementById('provider');
const modelInput = document.querySelector('input[name="model"]');
const baseUrlInput = document.querySelector('input[name="base_url"]');
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
    const res = await fetch('/admin/ai/test', { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    resultBox.innerHTML = '<div class="alert ' + (json.ok ? 'ok' : 'error') + '">' + json.message + '</div>';
  } catch (e) {
    resultBox.innerHTML = '<div class="alert error">Erreur reseau.</div>';
  }
});
</script>
