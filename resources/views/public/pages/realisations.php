<?php
use App\Models\Media;
use App\Models\Project;
/**
 * @var \App\Models\Page $page
 * @var string $blocksHtml
 * @var \App\Models\Company|null $company
 */
$name = (string) ($company?->getAttribute('trade_name') ?: config('app.name', 'Votre artisan'));
$title = (string) ($page->getAttribute('h1') ?: $page->getAttribute('title') ?: 'Nos réalisations');
$description = (string) ($page->getAttribute('meta_description') ?: 'Découvrez nos derniers chantiers : installation, entretien et dépannage réalisés avec soin pour nos clients.');
$projects = Project::visible();
$mediaUrl = static fn($id) => $id ? Media::find((int)$id)?->getAttribute('url') : null;
?>
<main class="reviews-page">
  <section class="reviews-hero"><div class="reviews-hero-inner">
    <span class="reviews-eyebrow">L’expérience <?= e($name) ?></span>
    <h1><?= e($title) ?></h1>
    <p><?= e($description) ?></p>
  </div></section>
  <?php if ($projects): ?><section class="ph-section projects-showcase"><div class="marketing-project-grid">
    <?php foreach ($projects as $project): $image = $mediaUrl($project->getAttribute('after_media_id')) ?: $mediaUrl($project->getAttribute('before_media_id')); ?>
      <figure><?php if ($image): ?><img src="<?= e($image) ?>" alt="<?= e($project->getAttribute('alt_text') ?: $project->getAttribute('title')) ?>" loading="lazy"><?php else: ?><div class="marketing-image-empty" aria-hidden="true">R</div><?php endif; ?><figcaption><strong><?= e($project->getAttribute('title')) ?></strong><?php if ($project->getAttribute('category')): ?><span><?= e($project->getAttribute('category')) ?></span><?php endif; ?></figcaption></figure>
    <?php endforeach; ?>
  </div></section><?php endif; ?>
  <?= $blocksHtml ?>
</main>
