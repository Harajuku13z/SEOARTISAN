<?php
/** @var array<int,\App\Models\Testimonial> $testimonials */
/** @var bool $serpApiConfigured */
/** @var string|null $googleMapsDataId */
$errors = flash_errors();
$success = flash_message('success');
?>
<div class="admin-topbar"><h1>Avis clients</h1></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<div class="alert warn">N'ajoutez ici que des avis reellement recus. Ils s'affichent uniquement s'ils sont fournis - aucun avis n'est genere ou invente automatiquement.</div>

<div class="card">
  <h3 style="margin-top:0">Importer les avis Google</h3>
  <p>SerpApi recherche la fiche Google Maps de l'entreprise puis importe uniquement les avis reels. Les avis deja importes sont actualises sans etre dupliques.</p>
  <form method="post" action="/admin/testimonials/serpapi" id="serpapi-form">
    <?= csrf_field() ?>
    <label>Cle API SerpApi</label>
    <div class="row">
      <input type="password" name="api_key" id="serpapi-key" placeholder="<?= $serpApiConfigured ? 'Cle deja enregistree (laisser vide pour la conserver)' : 'Votre cle SerpApi' ?>" autocomplete="new-password">
      <div style="display:flex;gap:.5rem;align-items:end">
        <button type="button" class="btn secondary" id="test-serpapi">Tester</button>
        <button type="submit">Enregistrer</button>
      </div>
    </div>
    <div id="serpapi-result" style="margin-top:.75rem"></div>
  </form>
  <form method="post" action="/admin/testimonials/google-sync" style="margin-top:1rem">
    <?= csrf_field() ?>
    <button type="submit" <?= $serpApiConfigured ? '' : 'disabled' ?>>Synchroniser maintenant</button>
    <?php if ($googleMapsDataId): ?><small>Fiche Google Maps associee.</small><?php endif; ?>
  </form>
</div>

<table>
  <tr><th>Auteur</th><th>Note</th><th>Extrait</th><th>Visible</th><th></th></tr>
  <?php foreach ($testimonials as $t): ?>
    <tr>
      <td><?= e($t->getAttribute('author_name')) ?><?= $t->getAttribute('role_or_service') ? ' - ' . e($t->getAttribute('role_or_service')) : '' ?></td>
      <td><?= $t->getAttribute('rating') ? str_repeat('*', (int) $t->getAttribute('rating')) : '-' ?></td>
      <td><?= e(mb_substr((string) $t->getAttribute('content'), 0, 60)) ?>&hellip;</td>
      <td><span class="badge <?= $t->getAttribute('is_visible') ? 'published' : 'archived' ?>"><?= $t->getAttribute('is_visible') ? 'oui' : 'non' ?></span></td>
      <td>
        <form method="post" action="/admin/testimonials/<?= (int) $t->id() ?>/delete" onsubmit="return confirm('Supprimer cet avis ?')">
          <?= csrf_field() ?><button type="submit" class="btn danger sm">Supprimer</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<div class="card">
  <h3 style="margin-top:0">Ajouter un avis</h3>
  <form method="post" action="/admin/testimonials" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row">
      <div><label>Nom de l'auteur</label><input type="text" name="author_name" required></div>
      <div><label>Role / service concerne</label><input type="text" name="role_or_service"></div>
    </div>
    <label>Avis</label>
    <textarea name="content" rows="3" required></textarea>
    <div class="row3">
      <div>
        <label>Note (1-5)</label>
        <select name="rating">
          <option value="">Non precisee</option>
          <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
        </select>
      </div>
      <div>
        <label>Source</label>
        <select name="source">
          <option value="manual">Saisie manuelle</option>
          <option value="google">Avis Google</option>
        </select>
      </div>
      <div><label>Photo (facultatif)</label><input type="file" name="avatar" accept="image/*"></div>
    </div>
    <div class="checkbox-row">
      <input type="checkbox" id="is_visible" name="is_visible" value="1" checked>
      <label for="is_visible">Visible sur le site</label>
    </div>
    <button type="submit">Ajouter</button>
  </form>
</div>

<script>
document.getElementById('test-serpapi')?.addEventListener('click', async () => {
  const button = document.getElementById('test-serpapi');
  const result = document.getElementById('serpapi-result');
  const body = new FormData(document.getElementById('serpapi-form'));
  button.disabled = true;
  result.textContent = 'Test en cours...';
  try {
    const response = await fetch('/admin/testimonials/serpapi/test', {method: 'POST', body});
    const data = await response.json();
    result.className = 'alert ' + (data.ok ? 'ok' : 'error');
    result.textContent = data.message || 'Reponse inattendue.';
  } catch (error) {
    result.className = 'alert error';
    result.textContent = 'Impossible de contacter le serveur.';
  } finally {
    button.disabled = false;
  }
});
</script>
