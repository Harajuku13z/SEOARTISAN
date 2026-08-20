<?php
/**
 * @var string $content
 * @var string $stepKey
 */
$steps = [
    'tech-check' => '1. Verification',
    'database' => '2. Base de donnees',
    'admin-account' => '3. Compte admin',
    'company' => '4. Entreprise',
    'branding' => '5. Identite visuelle',
    'business' => '6. Metier',
    'services' => '7. Services',
    'locations' => '8. Zones',
    'ai' => '9. Intelligence artificielle',
    'editorial' => '10. Redactionnel',
    'generate' => '11. Generation',
];
$stepKeys = array_keys($steps);
$currentIndex = array_search($stepKey, $stepKeys, true);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation - <?= e(config('app.name')) ?></title>
<link rel="stylesheet" href="/assets/css/installer.css">
</head>
<body>
<div class="wrap">
  <div class="brand"><?= e(config('app.name')) ?> — Assistant d'installation</div>
  <div class="steps">
    <?php foreach ($steps as $key => $label): $i = array_search($key, $stepKeys, true); ?>
      <span class="<?= $key === $stepKey ? 'current' : ($currentIndex !== false && $i < $currentIndex ? 'done' : '') ?>"><?= e($label) ?></span>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <?= $content ?>
  </div>
</div>
</body>
</html>
