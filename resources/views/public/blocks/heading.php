<?php
/** @var array<string,mixed> $data */
$text = (string) ($data['text'] ?? '');
if ($text === '') {
    return;
}
$level = (int) ($data['level'] ?? 2);
$level = $level >= 2 && $level <= 4 ? $level : 2;
?>
<section class="section">
  <div class="container">
    <h<?= $level ?>><?= e($text) ?></h<?= $level ?>>
  </div>
</section>
