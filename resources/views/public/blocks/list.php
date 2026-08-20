<?php
/** @var array<string,mixed> $data */
$items = (array) ($data['items'] ?? []);
if ($items === []) {
    return;
}
$heading = (string) ($data['heading'] ?? '');
?>
<section class="section">
  <div class="container" style="max-width:820px">
    <?php if ($heading !== ''): ?><h2 style="margin-bottom:14px"><?= e($heading) ?></h2><?php endif; ?>
    <ul style="color:var(--color-ink-soft);font-size:15px;line-height:1.8">
      <?php foreach ($items as $item): ?>
        <li><?= e($item) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
