<?php
/**
 * @var \App\Models\Page $page
 * @var string $blocksHtml
 * @var \App\Models\Company|null $company
 */
$name = (string) ($company?->getAttribute('trade_name') ?: config('app.name', 'Votre artisan'));
$title = (string) ($page->getAttribute('h1') ?: $page->getAttribute('title') ?: 'Nos réalisations');
$description = (string) ($page->getAttribute('meta_description') ?: 'Découvrez nos derniers chantiers : installation, entretien et dépannage réalisés avec soin pour nos clients.');
?>
<main class="reviews-page">
  <section class="reviews-hero"><div class="reviews-hero-inner">
    <span class="reviews-eyebrow">L’expérience <?= e($name) ?></span>
    <h1><?= e($title) ?></h1>
    <p><?= e($description) ?></p>
  </div></section>
  <?= $blocksHtml ?>
</main>
