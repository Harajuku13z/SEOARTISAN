<?php
/**
 * @var \App\Models\Company|null $company
 * @var array<int,string> $days
 */
$errors = flash_errors();
$dayLabels = ['lundi' => 'Lundi', 'mardi' => 'Mardi', 'mercredi' => 'Mercredi', 'jeudi' => 'Jeudi', 'vendredi' => 'Vendredi', 'samedi' => 'Samedi', 'dimanche' => 'Dimanche'];
$hours = $company?->getAttribute('opening_hours') ?? [];
$social = $company?->getAttribute('social_links') ?? [];
$val = static fn (string $key) => e($company?->getAttribute($key));
?>
<h1>Informations de l'entreprise</h1>
<p class="subtitle">Ces informations alimentent le site et servent de base reelle a la generation de contenus.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="/install/company">
  <?= csrf_field() ?>

  <fieldset>
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
    <label for="founded_year">Annee de creation</label>
    <input type="number" id="founded_year" name="founded_year" value="<?= $val('founded_year') ?>" min="1900" max="<?= date('Y') ?>">
  </fieldset>

  <fieldset>
    <legend>Contact</legend>
    <div class="row">
      <div>
        <label for="phone">Telephone</label>
        <input type="tel" id="phone" name="phone" value="<?= $val('phone') ?>">
      </div>
      <div>
        <label for="whatsapp">WhatsApp</label>
        <input type="tel" id="whatsapp" name="whatsapp" value="<?= $val('whatsapp') ?>">
      </div>
    </div>
    <div class="row">
      <div>
        <label for="public_email">E-mail public</label>
        <input type="email" id="public_email" name="public_email" value="<?= $val('public_email') ?>">
      </div>
      <div>
        <label for="leads_email">E-mail de reception des formulaires</label>
        <input type="email" id="leads_email" name="leads_email" value="<?= $val('leads_email') ?>">
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Adresse</legend>
    <label for="address">Adresse du siege</label>
    <input type="text" id="address" name="address" value="<?= $val('address') ?>">
    <div class="row3">
      <div>
        <label for="postal_code">Code postal</label>
        <input type="text" id="postal_code" name="postal_code" value="<?= $val('postal_code') ?>">
      </div>
      <div>
        <label for="city">Ville</label>
        <input type="text" id="city" name="city" value="<?= $val('city') ?>">
      </div>
      <div>
        <label for="department">Departement</label>
        <input type="text" id="department" name="department" value="<?= $val('department') ?>">
      </div>
    </div>
    <label for="region">Region</label>
    <input type="text" id="region" name="region" value="<?= $val('region') ?>">
  </fieldset>

  <fieldset>
    <legend>Legal &amp; reassurance</legend>
    <label for="siret">Numero SIRET (facultatif)</label>
    <input type="text" id="siret" name="siret" value="<?= $val('siret') ?>">
    <label for="certifications">Certifications reelles (facultatif, separees par des virgules)</label>
    <input type="text" id="certifications" name="certifications" value="<?= e(implode(', ', (array) ($company?->getAttribute('certifications') ?? []))) ?>" placeholder="ex : RGE, Qualibat">
    <p class="hint">N'indiquez que des certifications reellement obtenues - elles seront affichees telles quelles sur le site.</p>
    <label for="gbp_url">URL de la fiche Google Business Profile</label>
    <input type="url" id="gbp_url" name="gbp_url" value="<?= $val('gbp_url') ?>">
    <label for="legal_info">Informations legales complementaires</label>
    <textarea id="legal_info" name="legal_info" rows="2"><?= $val('legal_info') ?></textarea>
  </fieldset>

  <fieldset>
    <legend>Horaires d'ouverture</legend>
    <?php foreach ($dayLabels as $key => $label): ?>
      <div class="row">
        <label style="align-self:center;margin:0"><?= e($label) ?></label>
        <input type="text" name="hours_<?= e($key) ?>" value="<?= e($hours[$key] ?? '') ?>" placeholder="ex : 8h-12h, 14h-18h ou Ferme">
      </div>
    <?php endforeach; ?>
  </fieldset>

  <fieldset>
    <legend>Reseaux sociaux</legend>
    <label for="social_facebook">Facebook</label>
    <input type="url" id="social_facebook" name="social_facebook" value="<?= e($social['facebook'] ?? '') ?>">
    <label for="social_instagram">Instagram</label>
    <input type="url" id="social_instagram" name="social_instagram" value="<?= e($social['instagram'] ?? '') ?>">
    <label for="social_linkedin">LinkedIn</label>
    <input type="url" id="social_linkedin" name="social_linkedin" value="<?= e($social['linkedin'] ?? '') ?>">
  </fieldset>

  <fieldset>
    <legend>Zone &amp; offre</legend>
    <label for="service_radius_km">Rayon de deplacement (km)</label>
    <input type="number" id="service_radius_km" name="service_radius_km" value="<?= $val('service_radius_km') ?>">
    <div class="checkbox-row">
      <input type="checkbox" id="offers_emergency" name="offers_emergency" value="1" <?= $company?->getAttribute('offers_emergency') ? 'checked' : '' ?>>
      <label for="offers_emergency">Intervention urgente possible</label>
    </div>
    <div class="checkbox-row">
      <input type="checkbox" id="offers_free_quote" name="offers_free_quote" value="1" <?= ($company === null || $company->getAttribute('offers_free_quote')) ? 'checked' : '' ?>>
      <label for="offers_free_quote">Devis gratuit propose</label>
    </div>
  </fieldset>

  <div class="actions">
    <a class="btn secondary" href="/install/admin-account">Retour</a>
    <button type="submit">Continuer</button>
  </div>
</form>
