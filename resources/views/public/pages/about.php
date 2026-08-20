<?php
use App\Models\Media;
$name=(string)($company?->getAttribute('trade_name')?:config('app.name','Votre artisan'));
$city=(string)($company?->getAttribute('city')??'');
$hero=$company?->getAttribute('hero_media_id')?Media::find((int)$company->getAttribute('hero_media_id'))?->getAttribute('url'):null;
?>
<?= view('public.partials.breadcrumbs',['items'=>$breadcrumbs??[]]) ?>
<main class="about-page">
  <section class="service-premium-hero"><?php if($hero): ?><img src="<?= e($hero) ?>" alt="L’équipe <?= e($name) ?>" fetchpriority="high"><?php endif; ?><div class="service-premium-shade"></div><div class="service-premium-copy"><span class="ph-eyebrow">À propos</span><h1><?= e($page->getAttribute('h1')?:$name) ?></h1><?php if($company?->getAttribute('short_description')): ?><p><?= e($company->getAttribute('short_description')) ?></p><?php endif; ?><a href="#demande-devis">Parler de votre projet</a></div></section>
  <section class="ph-section"><span class="ph-eyebrow blue">Notre entreprise</span><h2>Un savoir-faire au service de vos projets</h2><?php if($company?->getAttribute('long_description')): ?><p><?= nl2br(e($company->getAttribute('long_description'))) ?></p><?php elseif($company?->getAttribute('short_description')): ?><p><?= e($company->getAttribute('short_description')) ?></p><?php else: ?><p><?= e($name) ?> accompagne ses clients avec une approche claire, soignée et adaptée à chaque projet.</p><?php endif; ?></section>
  <section class="ph-section"><div class="marketing-head"><div><span class="ph-eyebrow blue">Nos engagements</span><h2>Une relation simple et transparente</h2></div></div><div class="pac-benefits"><article><b>01</b><h3>Écoute</h3><p>Nous prenons le temps de comprendre votre besoin.</p></article><article><b>02</b><h3>Conseil</h3><p>Chaque proposition est adaptée au contexte du projet.</p></article><article><b>03</b><h3>Soin</h3><p>Les interventions sont préparées et réalisées avec attention.</p></article><article><b>04</b><h3>Suivi</h3><p>Notre équipe reste disponible après l’intervention.</p></article></div></section>
  <?php if($city!==''||$company?->getAttribute('region')): ?><?= view('public.partials.zone_card',['company'=>$company]) ?><?php endif; ?>
  <?= view('public.partials.quote_card',['company'=>$company]) ?>
</main>
