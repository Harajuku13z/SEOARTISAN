<?php
$name=(string)($company?->getAttribute('trade_name')?:config('app.name','Votre artisan'));
$agencyCity=(string)($company?->getAttribute('city')??'');
$region=(string)($company?->getAttribute('region')??'');
$radius=(int)($company?->getAttribute('service_radius_km')??0);
?>
<section class="zone-card">
  <div class="zone-card-inner">
    <div class="zone-card-copy">
      <h2><?= e($name) ?> intervient chez vous</h2><div class="zone-card-rule"></div>
      <?php if($agencyCity!==''): ?><p class="zone-card-label">Notre agence :</p><div class="zone-card-pills"><span class="zone-pill"><?= e(mb_strtoupper($agencyCity)) ?></span></div><?php endif; ?>
      <p class="zone-card-text"><?= e($name) ?> accompagne les particuliers et les professionnels<?= $region!==''?' en '.e($region):'' ?><?= $radius>0?', dans un rayon d’environ '.e($radius).' km autour de '.e($agencyCity):'' ?>. Consultez nos zones d’intervention ou contactez-nous pour vérifier la disponibilité de notre équipe.</p>
      <a class="zone-card-cta" href="#devis">Demander un devis <span aria-hidden="true">→</span></a>
    </div>
    <div class="zone-card-map" aria-label="Zone d’intervention"><div class="zone-card-pills"><span class="zone-pill"><?= $region!==''?e($region):'Zone définie lors de l’installation' ?></span></div></div>
  </div>
</section>
