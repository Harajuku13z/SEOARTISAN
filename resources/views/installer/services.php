<?php
/**
 * @var array<int,array<string,mixed>> $suggested
 * @var array<int,array<string,mixed>> $existing
 */
$errors = flash_errors();
?>
<h1>Services proposes</h1>
<p class="subtitle">Cochez les services proposés puis choisissez, pour chacun, si le texte doit être généré par l’IA ou rédigé manuellement.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="/install/services">
  <?= csrf_field() ?>

  <?php foreach ($suggested as $service): $sid = (int) $service['id']; $ex = $existing[$sid] ?? null; $checked = $ex !== null; ?>
    <div class="service-card <?= $checked ? 'checked' : '' ?>">
      <div class="checkbox-row">
        <input type="checkbox" id="svc_<?= $sid ?>_selected" name="services[<?= $sid ?>][selected]" value="1" <?= $checked ? 'checked' : '' ?>>
        <label for="svc_<?= $sid ?>_selected"><strong><?= e($service['name']) ?></strong></label>
      </div>
      <label>Nom public</label>
      <input type="text" name="services[<?= $sid ?>][public_name]" value="<?= e($ex['public_name'] ?? $service['name']) ?>">
      <label>Description courte (facultatif)</label>
      <textarea name="services[<?= $sid ?>][description]" rows="2"><?= e($ex['description'] ?? $service['default_description'] ?? '') ?></textarea>
      <?php $mode = ($ex['content_mode'] ?? 'ai') === 'manual' ? 'manual' : 'ai'; ?>
      <fieldset class="content-choice" data-content-choice>
        <legend>Création du contenu de la page</legend>
        <div class="content-mode-options">
          <label class="mode-option"><input type="radio" name="services[<?= $sid ?>][content_mode]" value="ai" <?= $mode === 'ai' ? 'checked' : '' ?>> <span><strong>Générer avec l’IA</strong><small>L’IA prépare le titre, le texte, le SEO et les sections de la page.</small></span></label>
          <label class="mode-option"><input type="radio" name="services[<?= $sid ?>][content_mode]" value="manual" <?= $mode === 'manual' ? 'checked' : '' ?>> <span><strong>Écrire moi-même</strong><small>Votre texte est conservé tel quel et ne sera pas remplacé par l’IA.</small></span></label>
        </div>
        <div class="manual-content" data-manual-content <?= $mode === 'manual' ? '' : 'hidden' ?>>
          <label for="svc_<?= $sid ?>_manual">Texte complet de la page</label>
          <textarea id="svc_<?= $sid ?>_manual" name="services[<?= $sid ?>][manual_content]" rows="8" placeholder="Présentez la prestation, votre méthode, les bénéfices pour le client et votre zone d’intervention…"><?= e($ex['manual_content'] ?? '') ?></textarea>
        </div>
      </fieldset>
      <div class="row3">
        <div class="checkbox-row"><input type="checkbox" id="svc_<?= $sid ?>_menu" name="services[<?= $sid ?>][show_in_menu]" value="1" <?= ($ex === null || $ex['show_in_menu']) ? 'checked' : '' ?>><label for="svc_<?= $sid ?>_menu">Dans le menu</label></div>
        <div class="checkbox-row"><input type="checkbox" id="svc_<?= $sid ?>_featured" name="services[<?= $sid ?>][is_featured]" value="1" <?= !empty($ex['is_featured']) ? 'checked' : '' ?>><label for="svc_<?= $sid ?>_featured">Mis en avant</label></div>
        <div class="checkbox-row"><input type="checkbox" id="svc_<?= $sid ?>_urgent" name="services[<?= $sid ?>][is_emergency]" value="1" <?= !empty($ex['is_emergency']) ? 'checked' : '' ?>><label for="svc_<?= $sid ?>_urgent">Disponible en urgence</label></div>
      </div>
      <div class="row">
        <div class="checkbox-row"><input type="checkbox" id="svc_<?= $sid ?>_price" name="services[<?= $sid ?>][show_starting_price]" value="1" <?= !empty($ex['show_starting_price']) ? 'checked' : '' ?>><label for="svc_<?= $sid ?>_price">Afficher un prix de depart</label></div>
        <input type="number" step="0.01" name="services[<?= $sid ?>][starting_price]" value="<?= e($ex['starting_price'] ?? '') ?>" placeholder="Prix a partir de (EUR)">
      </div>
    </div>
  <?php endforeach; ?>

  <fieldset>
    <legend>Services personnalises</legend>
    <div id="custom-services"></div>
    <button type="button" class="btn secondary" id="add-custom">+ Ajouter un service personnalise</button>
  </fieldset>

  <div class="actions">
    <a class="btn secondary" href="/install/business">Retour</a>
    <button type="submit">Continuer</button>
  </div>
</form>

<template id="custom-service-template">
  <div class="service-card">
    <label>Nom du service</label>
    <input type="text" name="custom_services[__INDEX__][name]">
    <label>Description courte (facultatif)</label>
    <textarea name="custom_services[__INDEX__][description]" rows="2"></textarea>
    <fieldset class="content-choice" data-content-choice>
      <legend>Création du contenu de la page</legend>
      <div class="content-mode-options">
        <label class="mode-option"><input type="radio" name="custom_services[__INDEX__][content_mode]" value="ai" checked> <span><strong>Générer avec l’IA</strong><small>L’IA rédige la page du service.</small></span></label>
        <label class="mode-option"><input type="radio" name="custom_services[__INDEX__][content_mode]" value="manual"> <span><strong>Écrire moi-même</strong><small>Le texte saisi ne sera pas remplacé.</small></span></label>
      </div>
      <div class="manual-content" data-manual-content hidden><label>Texte complet de la page</label><textarea name="custom_services[__INDEX__][manual_content]" rows="8"></textarea></div>
    </fieldset>
  </div>
</template>
<script>
(function () {
  let index = 0;
  const container = document.getElementById('custom-services');
  const template = document.getElementById('custom-service-template');
  document.getElementById('add-custom').addEventListener('click', function () {
    const html = template.innerHTML.replaceAll('__INDEX__', String(index++));
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html.trim();
    container.appendChild(wrapper.firstElementChild);
    bindContentChoices(wrapper.firstElementChild);
  });

  function bindContentChoices(root) {
    root.querySelectorAll('[data-content-choice]').forEach(function (choice) {
      const manual = choice.querySelector('[data-manual-content]');
      const radios = choice.querySelectorAll('input[type="radio"]');
      const refresh = function () {
        const selected = choice.querySelector('input[type="radio"]:checked');
        manual.hidden = !selected || selected.value !== 'manual';
        const textarea = manual.querySelector('textarea');
        const serviceToggle = choice.closest('.service-card')?.querySelector('input[name$="[selected]"]');
        if (textarea) textarea.required = !manual.hidden && (!serviceToggle || serviceToggle.checked);
      };
      radios.forEach(function (radio) { radio.addEventListener('change', refresh); });
      const serviceToggle = choice.closest('.service-card')?.querySelector('input[name$="[selected]"]');
      if (serviceToggle) serviceToggle.addEventListener('change', refresh);
      refresh();
    });
  }

  bindContentChoices(document);
})();
</script>
<style>
.content-choice{margin-top:18px;padding:16px;border:1px solid #d8dee6;border-radius:10px}.content-mode-options{display:grid;grid-template-columns:1fr 1fr;gap:12px}.mode-option{display:flex;gap:10px;padding:14px;border:1px solid #d8dee6;border-radius:9px;cursor:pointer}.mode-option input{width:auto}.mode-option span,.mode-option small{display:block}.mode-option small{margin-top:4px;color:#667085}.manual-content{margin-top:14px}@media(max-width:700px){.content-mode-options{grid-template-columns:1fr}}
</style>
