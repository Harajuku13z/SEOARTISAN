<?php
/**
 * @var array<string,string> $fields
 * @var \App\Models\Company|null $company
 */
?>
<h1>Informations redactionnelles</h1>
<p class="subtitle">
  Ces informations reelles servent de base a la generation de contenus par IA. Elle ne les completera jamais par des inventions
  (certifications, prix, annees d'experience, avis, etc.) : si un champ reste vide, le texte genere restera general sur ce point.
</p>

<form method="post" action="/install/editorial">
  <?= csrf_field() ?>
  <?php foreach ($fields as $key => $label): ?>
    <label for="<?= e($key) ?>"><?= e($label) ?></label>
    <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3"><?= e($company?->getAttribute($key)) ?></textarea>
  <?php endforeach; ?>

  <div class="actions">
    <a class="btn secondary" href="/install/ai">Retour</a>
    <button type="submit">Continuer</button>
  </div>
</form>
