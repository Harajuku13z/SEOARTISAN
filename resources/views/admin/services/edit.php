<?php
/**
 * @var \App\Models\CompanyService $service
 * @var \App\Models\Page|null $page
 * @var array<int,\App\Models\Media> $partnerLogos
 * @var array<int,int> $selectedPartnerIds
 * @var array<string,string> $groups
 * @var string $currentGroup
 * @var array<string,string> $serviceCopy
 */
use App\Models\Faq;
use App\Models\Media;

$errors = flash_errors();
$success = flash_message('success');
$faqs = Faq::where(['faqable_type' => 'CompanyService', 'faqable_id' => $service->id()], 'sort_order ASC');
?>
<div class="admin-topbar">
  <h1><?= e($service->getAttribute('public_name')) ?></h1>
  <div style="display:flex;gap:8px">
    <a class="btn secondary" href="/<?= e($service->getAttribute('slug')) ?>" target="_blank">Voir la page</a>
    <form method="post" action="/admin/services/<?= (int) $service->id() ?>/regenerate">
      <?= csrf_field() ?>
      <button type="submit">Regenerer avec l'IA</button>
    </form>
  </div>
</div>

<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert warn"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($page?->getAttribute('content_is_placeholder')): ?>
  <div class="alert warn">Cette page contient un contenu temporaire (non genere ou echec IA). Cliquez sur "Regenerer avec l'IA" ci-dessus.</div>
<?php endif; ?>

<form method="post" action="/admin/services/<?= (int) $service->id() ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card">
    <fieldset class="fieldset">
      <legend>Informations</legend>
      <label>Nom public</label>
      <input type="text" name="public_name" value="<?= e($service->getAttribute('public_name')) ?>" required>
      <label>URL (slug)</label>
      <input type="text" name="slug" value="<?= e($service->getAttribute('slug')) ?>">
      <label>Description courte</label>
      <textarea name="description" rows="2"><?= e($service->getAttribute('description')) ?></textarea>
      <label>Groupe de services</label>
      <select name="service_group"><option value="">Non classé</option><?php foreach(($groups??[]) as $slug=>$label): ?><option value="<?= e($slug) ?>" <?= ($currentGroup??'')===$slug?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select>
      <div class="admin-public-copy">
        <h3>Textes affichés sur la page publique</h3>
        <p class="hint">Ces textes apparaissent sur la page du service, en haut (hero) puis dans la section « À propos du service ».</p>
        <div class="admin-public-copy-group">
          <label>Petit texte au-dessus du titre (ex. « Installation · Votre ville »)</label>
          <input type="text" name="hero_eyebrow" value="<?= e($serviceCopy['hero_eyebrow']??'') ?>" required>
          <label>Titre principal du hero</label>
          <input type="text" name="page_h1" value="<?= e($page?->getAttribute('h1') ?: $service->getAttribute('public_name')) ?>" required>
          <label>Accroche sous le titre du hero</label>
          <textarea name="hero_intro" rows="2" required><?= e($serviceCopy['hero_intro']??'') ?></textarea>
        </div>
        <div class="admin-public-copy-group">
          <label>Titre de la section « À propos du service »</label>
          <input type="text" name="about_title" value="<?= e($serviceCopy['about_title']??'') ?>" required>
          <label>Texte détaillé de la section</label>
          <textarea name="about_text" rows="12" required class="admin-copy-textarea"><?= e($serviceCopy['about_text']??'') ?></textarea>
          <p class="hint">Ce champ accepte du HTML : &lt;h2&gt;, &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;, &lt;strong&gt;. Le style (couleurs, tailles) est appliqué automatiquement.</p>
        </div>
      </div>
      <label>Photo du sous-service</label>
      <?php $serviceImage=$service->getAttribute('image_media_id')?Media::find((int)$service->getAttribute('image_media_id')):null;if($serviceImage): ?><img src="<?= e($serviceImage->getAttribute('url')) ?>" alt="Photo actuelle" style="display:block;width:220px;height:130px;object-fit:cover;border-radius:12px;margin:8px 0 12px"><?php endif; ?>
      <input type="file" name="image" accept="image/*" <?= $serviceImage?'':'required' ?>>
      <p style="margin:-8px 0 18px;color:var(--muted);font-size:12px">Cette photo appartient uniquement à ce sous-service et sera affichée sur sa carte et sa page.</p>
      <div class="row3">
        <div class="checkbox-row"><input type="checkbox" id="show_in_menu" name="show_in_menu" value="1" <?= $service->getAttribute('show_in_menu') ? 'checked' : '' ?>><label for="show_in_menu">Dans le menu</label></div>
        <div class="checkbox-row"><input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $service->getAttribute('is_featured') ? 'checked' : '' ?>><label for="is_featured">Mis en avant</label></div>
        <div class="checkbox-row"><input type="checkbox" id="is_emergency" name="is_emergency" value="1" <?= $service->getAttribute('is_emergency') ? 'checked' : '' ?>><label for="is_emergency">Urgence</label></div>
      </div>
      <div class="row">
        <div class="checkbox-row"><input type="checkbox" id="show_starting_price" name="show_starting_price" value="1" <?= $service->getAttribute('show_starting_price') ? 'checked' : '' ?>><label for="show_starting_price">Afficher un prix de depart</label></div>
        <input type="number" step="0.01" name="starting_price" value="<?= e($service->getAttribute('starting_price')) ?>">
      </div>
      <label>Ordre d'affichage</label>
      <input type="number" name="sort_order" value="<?= (int) $service->getAttribute('sort_order') ?>">
      <div class="checkbox-row"><input type="checkbox" id="is_active" name="is_active" value="1" <?= $service->getAttribute('is_active') ? 'checked' : '' ?>><label for="is_active">Service actif</label></div>
    </fieldset>
    <fieldset class="fieldset">
      <legend>SEO</legend>
      <label>Titre SEO</label>
      <input type="text" name="meta_title" value="<?= e($service->getAttribute('meta_title')) ?>">
      <label>Description SEO</label>
      <textarea name="meta_description" rows="2"><?= e($service->getAttribute('meta_description')) ?></textarea>
    </fieldset>
    <fieldset class="fieldset">
      <legend>Partenaires affichés sur cette page</legend>
      <p>Sélectionnez les marques à afficher dans la section « Marques &amp; partenaires » de ce service.</p>
      <div class="row3"><?php foreach(($partnerLogos??[]) as $logo): $logoId=(int)$logo->id(); ?><label style="padding:12px;border:1px solid var(--border);border-radius:10px"><img src="<?= e($logo->getAttribute('url')) ?>" alt="Partenaire" style="display:block;width:100%;height:55px;object-fit:contain;margin-bottom:8px"><span class="checkbox-row"><input type="checkbox" name="partner_logo_ids[]" value="<?= $logoId ?>" <?= in_array($logoId,$selectedPartnerIds??[],true)?'checked':'' ?>> Afficher</span></label><?php endforeach; ?></div>
      <?php if(empty($partnerLogos)): ?><p class="hint">Ajoutez d’abord vos logos dans Entreprise → Design &amp; images.</p><?php endif; ?>
    </fieldset>
  </div>
  <div class="actions-bar"><button type="submit">Enregistrer</button></div>
</form>

<div class="card">
  <h3 style="margin-top:0">FAQ du service</h3>
  <?php foreach ($faqs as $faq): ?>
    <div style="border-bottom:1px solid var(--border);padding:10px 0">
      <strong><?= e($faq->getAttribute('question')) ?></strong>
      <p style="color:var(--muted);font-size:13px;margin:4px 0"><?= e($faq->getAttribute('answer')) ?></p>
      <form method="post" action="/admin/services/faq/<?= (int) $faq->id() ?>/delete" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn danger sm">Supprimer</button>
      </form>
    </div>
  <?php endforeach; ?>
  <form method="post" action="/admin/services/<?= (int) $service->id() ?>/faq" style="margin-top:14px">
    <?= csrf_field() ?>
    <p class="hint">Renseignez une ou plusieurs questions ci-dessous, les lignes vides sont ignorées à l’enregistrement.</p>
    <div id="faq-new-rows">
      <?php for ($i = 0; $i < 3; $i++): ?>
      <div class="faq-new-row" style="padding:14px;border:1px solid var(--border);border-radius:10px;margin-bottom:12px">
        <label>Question</label>
        <input type="text" name="questions[]">
        <label>Reponse</label>
        <textarea name="answers[]" rows="2"></textarea>
      </div>
      <?php endfor; ?>
    </div>
    <button type="button" class="btn secondary sm" id="faq-add-row">+ Ajouter une autre question</button>
    <div class="actions-bar"><button type="submit" class="btn secondary">Enregistrer ces questions</button></div>
  </form>
</div>
<script>
(function () {
  var addBtn = document.getElementById('faq-add-row');
  var container = document.getElementById('faq-new-rows');
  if (!addBtn || !container) { return; }
  addBtn.addEventListener('click', function () {
    var row = container.children[0].cloneNode(true);
    row.querySelectorAll('input, textarea').forEach(function (field) { field.value = ''; });
    container.appendChild(row);
  });
})();
</script>
