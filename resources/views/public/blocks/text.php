<?php
/** @var array<string,mixed> $data */
$heading = (string) ($data['heading'] ?? '');
$content = (string) ($data['content'] ?? '');
if ($content === '') {
    return;
}
?>
<section class="section">
  <div class="container">
    <?php if ($heading !== ''): ?><h2 style="margin-bottom:14px"><?= e($heading) ?></h2><?php endif; ?>
    <?php foreach (explode("\n", $content) as $paragraph): if (trim($paragraph) === '') continue; ?>
      <p style="color:var(--color-ink-soft);font-size:15px;margin:0 0 14px"><?= e(trim($paragraph)) ?></p>
    <?php endforeach; ?>
  </div>
</section>
