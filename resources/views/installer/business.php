<?php
/**
 * @var array<int,array<string,mixed>> $categories
 * @var array<int,array<int,array<string,mixed>>> $subcategoriesByCategory
 * @var array<int,int> $selectedCategoryIds
 * @var array<int,int> $selectedSubcategoryIds
 */
$errors = flash_errors();
?>
<h1>Selection du metier</h1>
<p class="subtitle">Choisissez un ou plusieurs metiers. Le premier selectionne sera utilise comme activite principale et tous leurs services seront regroupes a l'etape suivante.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="/install/business">
  <?= csrf_field() ?>
  <div class="category-grid">
    <?php foreach ($categories as $category): ?>
      <label class="category-card <?= in_array((int) $category['id'], $selectedCategoryIds, true) ? 'selected' : '' ?>">
        <input type="checkbox" name="business_category_ids[]" value="<?= e($category['id']) ?>" style="display:none"
          <?= in_array((int) $category['id'], $selectedCategoryIds, true) ? 'checked' : '' ?>
          onchange="this.parentElement.classList.toggle('selected',this.checked)">
        <?= e($category['name']) ?>
      </label>
    <?php endforeach; ?>
  </div>

  <?php foreach ($categories as $category): $subs = $subcategoriesByCategory[$category['id']] ?? []; if ($subs === []) continue; ?>
    <fieldset data-parent-category="<?= e($category['id']) ?>">
      <legend>Specialites secondaires — <?= e($category['name']) ?> (facultatif)</legend>
      <?php foreach ($subs as $sub): ?>
        <div class="checkbox-row">
          <input type="checkbox" id="sub_<?= e($sub['id']) ?>" name="subcategory_ids[]" value="<?= e($sub['id']) ?>" <?= in_array((int) $sub['id'], $selectedSubcategoryIds, true) ? 'checked' : '' ?>>
          <label for="sub_<?= e($sub['id']) ?>"><?= e($sub['name']) ?></label>
        </div>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>

  <div class="actions">
    <a class="btn secondary" href="/install/branding">Retour</a>
    <button type="submit">Continuer</button>
  </div>
</form>
