<?php
/**
 * @var \App\Models\Company|null $company
 * @var array<int,string> $days
 * @var array<string,string> $editorialFields
 * @var array<int,\App\Models\Media> $partnerLogos
 * @var array<string,\App\Models\Media|null> $visualMedia
 */
$errors = flash_errors();
$success = flash_message('success');
$dayLabels = ['lundi' => 'Lundi', 'mardi' => 'Mardi', 'mercredi' => 'Mercredi', 'jeudi' => 'Jeudi', 'vendredi' => 'Vendredi', 'samedi' => 'Samedi', 'dimanche' => 'Dimanche'];
$hours = $company?->getAttribute('opening_hours') ?? [];
$social = $company?->getAttribute('social_links') ?? [];
$val = static fn (string $key) => e($company?->getAttribute($key));
$visualMedia = $visualMedia ?? [];
foreach (['logo_main_media_id','logo_light_media_id','logo_dark_media_id','favicon_media_id','hero_media_id','hero_mobile_media_id','og_media_id'] as $field) {
    if (!array_key_exists($field, $visualMedia)) {
        $mediaId = $company?->getAttribute($field);
        $visualMedia[$field] = $mediaId ? \App\Models\Media::find((int)$mediaId) : null;
    }
}
$preview = static function (?\App\Models\Media $media, string $alt, bool $wide = false): string {
    if ($media === null || !$media->getAttribute('url')) return '<div style="height:90px;border:1px dashed #cfd5df;border-radius:9px;display:grid;place-items:center;color:#7a8494;font-size:12px;margin-bottom:10px">Aucune image enregistrée</div>';
    $height = $wide ? 120 : 90;
    $url = (string)$media->getAttribute('url');
    return '<div style="height:'.$height.'px;border:1px solid #dfe4ec;border-radius:9px;background:#f7f8fa;padding:8px;margin-bottom:8px;overflow:hidden"><img src="'.e($url).'?v='.(int)$media->id().'" alt="'.e($alt).'" style="display:block!important;width:100%!important;height:100%!important;max-width:100%!important;object-fit:contain!important;opacity:1!important;visibility:visible!important"></div><a href="'.e($url).'" target="_blank" rel="noopener" style="display:inline-block;margin:0 0 10px;font-size:12px;font-weight:700">Ouvrir l’image ↗</a>';
};
?>
<div class="admin-topbar"><h1>Entreprise</h1></div>

<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<form class="company-settings-form" method="post" action="/admin/company" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <nav class="company-settings-tabs" aria-label="Rubriques de l’entreprise">
    <button type="button" data-company-tab="identity">Identité</button>
    <button type="button" data-company-tab="emails">E-mails &amp; Notifications</button>
    <button type="button" data-company-tab="contact">Contact &amp; horaires</button>
    <button type="button" data-company-tab="visual">Design &amp; images</button>
    <button type="button" data-company-tab="editorial">Contenu IA</button>
  </nav>

  <div class="card">
    <fieldset class="fieldset company-settings-panel" data-company-panel="identity">
      <legend>Identite</legend>
      <label for="trade_name">Nom commercial</label>
      <input type="text" id="trade_name" name="trade_name" value="<?= $val('trade_name') ?>" required>
      <label for="legal_name">Raison sociale</label>
      <input type="text" id="legal_name" name="legal_name" value="<?= $val('legal_name') ?>">
      <label for="slogan">Slogan</label>
      <input type="text" id="slogan" name="slogan" value="<?= $val('slogan') ?>">
      <label for="short_description">Description courte</label>
      <textarea id="short_description" name="short_description" rows="2"><?= $val('short_description') ?></textarea>
      <label for="long_description">Description detaillee</label>
      <textarea id="long_description" name="long_description" rows="4"><?= $val('long_description') ?></textarea>
    </fieldset>

    <fieldset class="fieldset company-settings-panel" data-company-panel="contact">
      <legend>Contact &amp; adresse</legend>
      <div class="row">
        <div><label for="phone">Telephone</label><input type="tel" id="phone" name="phone" value="<?= $val('phone') ?>"></div>
        <div><label for="whatsapp">WhatsApp</label><input type="tel" id="whatsapp" name="whatsapp" value="<?= $val('whatsapp') ?>"></div>
      </div>
      <label for="address">Adresse</label>
      <input type="text" id="address" name="address" value="<?= $val('address') ?>">
      <div class="row3">
        <div><label for="postal_code">Code postal</label><input type="text" id="postal_code" name="postal_code" value="<?= $val('postal_code') ?>"></div>
        <div><label for="city">Ville</label><input type="text" id="city" name="city" value="<?= $val('city') ?>"></div>
        <div><label for="department">Departement</label><input type="text" id="department" name="department" value="<?= $val('department') ?>"></div>
      </div>
      <label for="region">Region</label>
      <input type="text" id="region" name="region" value="<?= $val('region') ?>">
      <label for="siret">SIRET</label>
      <input type="text" id="siret" name="siret" value="<?= $val('siret') ?>">
      <label for="certifications">Certifications reelles (separees par des virgules)</label>
      <input type="text" id="certifications" name="certifications" value="<?= e(implode(', ', (array) ($company?->getAttribute('certifications') ?? []))) ?>" placeholder="ex : RGE, Qualibat">
      <label for="gbp_url">Google Business Profile</label>
      <input type="url" id="gbp_url" name="gbp_url" value="<?= $val('gbp_url') ?>">
    </fieldset>

    <fieldset class="fieldset company-settings-panel" data-company-panel="contact">
      <legend>Horaires</legend>
      <?php foreach ($dayLabels as $key => $label): ?>
        <div class="row">
          <label style="align-self:center;margin:0"><?= e($label) ?></label>
          <input type="text" name="hours_<?= e($key) ?>" value="<?= e($hours[$key] ?? '') ?>" placeholder="ex : 8h-12h, 14h-18h ou Ferme">
        </div>
      <?php endforeach; ?>
    </fieldset>

    <fieldset class="fieldset company-settings-panel" data-company-panel="contact">
      <legend>Reseaux sociaux</legend>
      <div class="row3">
        <div><label>Facebook</label><input type="url" name="social_facebook" value="<?= e($social['facebook'] ?? '') ?>"></div>
        <div><label>Instagram</label><input type="url" name="social_instagram" value="<?= e($social['instagram'] ?? '') ?>"></div>
        <div><label>LinkedIn</label><input type="url" name="social_linkedin" value="<?= e($social['linkedin'] ?? '') ?>"></div>
      </div>
    </fieldset>

    <fieldset class="fieldset company-settings-panel" data-company-panel="contact">
      <legend>Zone &amp; offre</legend>
      <label for="service_radius_km">Rayon de deplacement (km)</label>
      <input type="number" id="service_radius_km" name="service_radius_km" value="<?= $val('service_radius_km') ?>">
      <div class="checkbox-row"><input type="checkbox" id="offers_emergency" name="offers_emergency" value="1" <?= $company?->getAttribute('offers_emergency') ? 'checked' : '' ?>><label for="offers_emergency">Intervention urgente possible</label></div>
      <div class="checkbox-row"><input type="checkbox" id="offers_free_quote" name="offers_free_quote" value="1" <?= ($company === null || $company->getAttribute('offers_free_quote')) ? 'checked' : '' ?>><label for="offers_free_quote">Devis gratuit propose</label></div>
    </fieldset>

    <fieldset class="fieldset company-settings-panel" data-company-panel="emails">
      <legend>E-mails &amp; Notifications</legend>
      <div class="row">
        <div><label for="public_email">E-mail public (Contact)</label><input type="email" id="public_email" name="public_email" value="<?= $val('public_email') ?>"></div>
        <div><label for="leads_email">E-mail de reception des prospects</label><input type="email" id="leads_email" name="leads_email" value="<?= $val('leads_email') ?>"></div>
      </div>
      <div style="margin-bottom: 12px;">
        <label for="mail_notification_html">Code HTML de l'e-mail de notification (Variables: {{type}}, {{lead_id}}, {{table}}, {{admin_link}})</label>
        <textarea id="mail_notification_html" name="mail_notification_html" rows="15" style="font-family:monospace; font-size:12px; white-space:pre-wrap; width: 100%;"><?= e($mailHtml ?? '') ?></textarea>
      </div>
      <button type="submit" formaction="/admin/company/test-email" class="btn alt" style="background:#f3f6f8;color:#294352;border:1px solid #dde3e8;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:600;">Tester l'envoi d'e-mail (sauvegarde et envoie un test)</button>
    </fieldset>
  </div>

  <div class="card">
    <fieldset class="fieldset company-settings-panel" data-company-panel="visual">
      <legend>Identite visuelle</legend>
      <div class="row3">
        <div><?= $preview($visualMedia['logo_main_media_id']??null,'Logo principal') ?><label>Logo principal</label><input type="file" name="logo_main" accept="image/*"></div>
        <div style="background:#253f4d;padding:12px;border-radius:10px"><?= $preview($visualMedia['logo_light_media_id']??null,'Logo clair') ?><label style="color:#fff">Logo clair</label><input type="file" name="logo_light" accept="image/*"></div>
        <div><?= $preview($visualMedia['logo_dark_media_id']??null,'Logo sombre') ?><label>Logo sombre</label><input type="file" name="logo_dark" accept="image/*"></div>
      </div>
      <div class="row3">
        <div><?= $preview($visualMedia['favicon_media_id']??null,'Favicon') ?><label>Favicon</label><input type="file" name="favicon" accept="image/*"></div>
        <div><?= $preview($visualMedia['hero_media_id']??null,'Hero ordinateur',true) ?><label>Hero ordinateur</label><input type="file" name="hero_image" accept="image/*"><small>Format horizontal conseillé : 1920 × 1080 px.</small></div>
        <div><?= $preview($visualMedia['hero_mobile_media_id']??null,'Hero mobile',true) ?><label>Hero mobile</label><input type="file" name="hero_mobile_image" accept="image/*"><small>Format vertical conseillé : 900 × 1200 px. L’image ordinateur reste utilisée si ce champ est vide.</small></div>
      </div>
      <div class="row3">
        <div><?= $preview($visualMedia['og_media_id']??null,'Image de partage',true) ?><label>Image de partage</label><input type="file" name="og_image" accept="image/*"></div>
      </div>
      <div class="row3">
        <div><label>Couleur principale du site</label><input type="color" name="primary_color" value="<?= $val('primary_color') ?: '#294352' ?>"><small>Header, footer, titres et boutons principaux.</small></div>
        <div><label>Couleur secondaire du site</label><input type="color" name="secondary_color" value="<?= $val('secondary_color') ?: '#38b6ff' ?>"><small>Surtitres, liens et détails visuels.</small></div>
        <div><label>Couleur d’accentuation</label><input type="color" name="accent_color" value="<?= $val('accent_color') ?: '#ffde59' ?>"><small>Étoiles, badges et appels à l’action.</small></div>
      </div>
      <div class="row">
        <div><label>Police principale</label><input type="text" name="font_primary" value="<?= $val('font_primary') ?>"></div>
        <div><label>Police secondaire</label><input type="text" name="font_secondary" value="<?= $val('font_secondary') ?>"></div>
      </div>
      <label>Style du site</label>
      <select name="theme_style">
        <?php foreach (['classique' => 'Professionnel classique', 'moderne' => 'Moderne', 'premium' => 'Premium', 'artisanal' => 'Artisanal', 'ecologique' => 'Ecologique', 'industriel' => 'Industriel'] as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $company?->getAttribute('theme_style') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </fieldset>
    <fieldset class="fieldset company-settings-panel" data-company-panel="visual">
      <legend>Logos des partenaires — page d’accueil</legend>
      <p>Ces logos apparaissent dans la section « Nos partenaires constructeurs » de la page d’accueil. Ajoutez ou remplacez jusqu’à 6 logos ; un emplacement laissé vide conserve son logo actuel.</p>
      <div class="row3">
        <?php foreach (range(1, 6) as $slot): $partner = $partnerLogos[$slot - 1] ?? null; ?>
          <div><?php if($partner): ?><img src="<?= e($partner->getAttribute('url')) ?>" alt="Partenaire <?= $slot ?>" style="height:54px;max-width:150px;object-fit:contain;display:block;margin-bottom:8px"><?php endif; ?><label>Partenaire <?= $slot ?></label><input type="file" name="partner_logo_<?= $slot ?>" accept="image/*"></div>
        <?php endforeach; ?>
      </div>
    </fieldset>
  </div>

  <div class="card">
    <fieldset class="fieldset company-settings-panel" data-company-panel="editorial">
      <legend>Informations redactionnelles (base reelle pour l'IA)</legend>
      <?php foreach ($editorialFields as $key => $label): ?>
        <label for="<?= e($key) ?>"><?= e($label) ?></label>
        <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="2"><?= $val($key) ?></textarea>
      <?php endforeach; ?>
    </fieldset>
  </div>

  <div class="actions-bar">
    <button type="submit">Enregistrer</button>
  </div>
</form>
<script>
(() => {
  const form = document.querySelector('.company-settings-form');
  if (!form) return;
  const tabs = [...form.querySelectorAll('[data-company-tab]')];
  const panels = [...form.querySelectorAll('[data-company-panel]')];
  const cards = [...form.querySelectorAll('.card')];
  const allowed = tabs.map(tab => tab.dataset.companyTab);
  const requested = location.hash.replace('#', '');
  const initial = allowed.includes(requested) ? requested : 'identity';

  const activate = section => {
    tabs.forEach(tab => {
      const active = tab.dataset.companyTab === section;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    panels.forEach(panel => { panel.hidden = panel.dataset.companyPanel !== section; });
    cards.forEach(card => {
      const cardPanels = [...card.querySelectorAll('[data-company-panel]')];
      card.hidden = cardPanels.length > 0 && cardPanels.every(panel => panel.hidden);
    });
    history.replaceState(null, '', '#' + section);
    window.scrollTo({top: 0, behavior: 'smooth'});
  };

  tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.companyTab)));
  activate(initial);
})();
</script>
