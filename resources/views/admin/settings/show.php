<?php
/**
 * @var \App\Models\SeoMetadata|null $seo
 * @var array<int,\App\Models\Redirect> $redirects
 */
$success = flash_message('success');
?>
<div class="admin-topbar"><h1>Reglages SEO</h1></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>

<div class="card">
  <form method="post" action="/admin/settings">
    <?= csrf_field() ?><input type="hidden" name="settings_section" value="seo">
    <label>Nom du site</label>
    <input type="text" name="site_name" value="<?= e($seo?->getAttribute('site_name')) ?>">
    <label>Modele de titre par defaut</label>
    <input type="text" name="default_title_pattern" value="<?= e($seo?->getAttribute('default_title_pattern')) ?>" placeholder="ex : %page_title% - %site_name%">
    <label>Meta description par defaut</label>
    <textarea name="default_meta_description" rows="2"><?= e($seo?->getAttribute('default_meta_description')) ?></textarea>
    <label>Code de verification Google Search Console</label>
    <input type="text" name="gsc_verification_code" value="<?= e($seo?->getAttribute('gsc_verification_code')) ?>">
    <button type="submit">Enregistrer</button>
  </form>
</div>

<div class="card">
  <h2>Conversions Google &amp; protection anti-bot</h2>
  <p class="hint">Les conversions ne sont envoyées qu’après validation de signaux humains. Pour mesurer la durée réelle des appels, configurez également une conversion « Appels depuis le site » et un numéro de transfert dans Google Ads.</p>
  <form method="post" action="/admin/settings">
    <?= csrf_field() ?><input type="hidden" name="settings_section" value="tracking">
    <label class="checkbox-row"><input type="checkbox" name="tracking_enabled" value="1" <?= !empty($tracking['enabled'])?'checked':'' ?>> Activer le tracker de conversions</label>
    <div class="row"><label>Identifiant Google tag<input name="google_tag_id" value="<?= e($tracking['google_tag_id']) ?>" placeholder="AW-123456789 ou G-XXXXXXXX"></label><label>Label conversion appels<input name="google_call_label" value="<?= e($tracking['google_call_label']) ?>" placeholder="AbCdEFghijk"></label></div>
    <label>Label conversion formulaires<input name="google_form_label" value="<?= e($tracking['google_form_label']) ?>" placeholder="XyZ123abc"></label>
    <div class="row"><label>Délai humain minimum (secondes)<input type="number" min="2" max="60" name="human_min_seconds" value="<?= (int)$tracking['human_min_seconds'] ?>"></label><label>Interactions minimum<input type="number" min="1" max="20" name="human_min_interactions" value="<?= (int)$tracking['human_min_interactions'] ?>"></label></div>
    <button type="submit">Enregistrer le suivi</button>
  </form>
</div>

<div class="card">
  <h3 style="margin-top:0">Redirections 301</h3>
  <table>
    <tr><th>De</th><th>Vers</th><th>Code</th><th></th></tr>
    <?php foreach ($redirects as $r): ?>
      <tr>
        <td><?= e($r->getAttribute('from_path')) ?></td>
        <td><?= e($r->getAttribute('to_path')) ?></td>
        <td><?= (int) $r->getAttribute('status_code') ?></td>
        <td>
          <form method="post" action="/admin/settings/redirects/<?= (int) $r->id() ?>/delete">
            <?= csrf_field() ?><button type="submit" class="btn danger sm">Supprimer</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <form method="post" action="/admin/settings/redirects" style="margin-top:14px">
    <?= csrf_field() ?>
    <div class="row3">
      <input type="text" name="from_path" placeholder="/ancienne-url">
      <input type="text" name="to_path" placeholder="/nouvelle-url">
      <select name="status_code"><option value="301">301</option><option value="302">302</option></select>
    </div>
    <button type="submit" class="btn secondary">Ajouter</button>
  </form>
</div>

<div class="card">
  <h3 style="margin-top:0">Cache du site</h3>
  <p style="font-size:13px;color:var(--muted)">Les pages publiques sont mises en cache une heure. Videz le cache apres une modification importante pour la voir apparaitre immediatement.</p>
  <form method="post" action="/admin/settings/purge-cache">
    <?= csrf_field() ?>
    <button type="submit" class="btn secondary">Vider le cache</button>
  </form>
</div>
