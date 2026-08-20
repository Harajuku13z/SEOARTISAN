<?php
/** @var array<int,array{label:string,url:?string}> $items */
if (empty($items)) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="Fil d'Ariane">
  <?php foreach ($items as $i => $item): ?>
    <?php if ($i > 0): ?><span class="sep">/</span><?php endif; ?>
    <?php if (!empty($item['url']) && $i < count($items) - 1): ?>
      <a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
    <?php else: ?>
      <span><?= e($item['label']) ?></span>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
