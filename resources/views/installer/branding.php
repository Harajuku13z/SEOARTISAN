<?php
/**
 * @var \App\Models\Company|null $company
 * @var array<string,string> $styles
 * @var array<int,string> $fonts
 */
$errors = flash_errors();
$c = static fn (string $key, string $default) => e($company?->getAttribute($key) ?: $default);
?>
<h1>Identite visuelle</h1>
<p class="subtitle">Logos, images et style graphique du site. Tout est facultatif a ce stade et modifiable depuis l'administration.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="/install/branding" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <fieldset>
    <legend>Logos &amp; images</legend>
    <div class="row">
      <div><label for="logo_main">Logo principal</label><input type="file" id="logo_main" name="logo_main" accept="image/*"></div>
      <div><label for="logo_light">Logo (version claire)</label><input type="file" id="logo_light" name="logo_light" accept="image/*"></div>
    </div>
    <div class="row">
      <div><label for="logo_dark">Logo (version sombre)</label><input type="file" id="logo_dark" name="logo_dark" accept="image/*"></div>
      <div><label for="favicon">Favicon</label><input type="file" id="favicon" name="favicon" accept="image/*"></div>
    </div>
    <div class="row">
      <div><label for="hero_image">Photo principale (hero)</label><input type="file" id="hero_image" name="hero_image" accept="image/*"></div>
      <div><label for="og_image">Image de partage (reseaux sociaux)</label><input type="file" id="og_image" name="og_image" accept="image/*"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Couleurs</legend>
    <div class="row3">
      <div><label for="primary_color">Couleur principale</label><input type="text" id="primary_color" name="primary_color" value="<?= $c('primary_color', '#1f2430') ?>" placeholder="#1f2430"></div>
      <div><label for="secondary_color">Couleur secondaire</label><input type="text" id="secondary_color" name="secondary_color" value="<?= $c('secondary_color', '#2f6fed') ?>" placeholder="#2f6fed"></div>
      <div><label for="accent_color">Couleur d'accentuation</label><input type="text" id="accent_color" name="accent_color" value="<?= $c('accent_color', '#e8a53d') ?>" placeholder="#e8a53d"></div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Typographie &amp; boutons</legend>
    <div class="row">
      <div>
        <label for="font_primary">Police principale</label>
        <select id="font_primary" name="font_primary">
          <?php foreach ($fonts as $font): ?><option value="<?= e($font) ?>" <?= $c('font_primary', 'Manrope') === $font ? 'selected' : '' ?>><?= e($font) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="font_secondary">Police secondaire</label>
        <select id="font_secondary" name="font_secondary">
          <?php foreach ($fonts as $font): ?><option value="<?= e($font) ?>" <?= $c('font_secondary', 'Public Sans') === $font ? 'selected' : '' ?>><?= e($font) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <label for="button_style">Style des boutons</label>
    <select id="button_style" name="button_style">
      <option value="rounded" <?= $c('button_style', 'rounded') === 'rounded' ? 'selected' : '' ?>>Arrondi</option>
      <option value="square" <?= $c('button_style', 'rounded') === 'square' ? 'selected' : '' ?>>Carre</option>
      <option value="pill" <?= $c('button_style', 'rounded') === 'pill' ? 'selected' : '' ?>>Pilule</option>
    </select>
  </fieldset>

  <fieldset>
    <legend>Style general du site</legend>
    <div class="row3">
      <?php foreach ($styles as $key => $label): ?>
        <div class="checkbox-row">
          <input type="radio" id="style_<?= e($key) ?>" name="theme_style" value="<?= e($key) ?>" <?= $c('theme_style', 'moderne') === $key ? 'checked' : '' ?>>
          <label for="style_<?= e($key) ?>"><?= e($label) ?></label>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="actions">
    <a class="btn secondary" href="/install/company">Retour</a>
    <button type="submit">Continuer</button>
  </div>
</form>
