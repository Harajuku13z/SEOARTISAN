<?php
/** @var array<string,mixed> $data */
$label = (string) ($data['label'] ?? '');
$url = (string) ($data['url'] ?? '#');
if ($label === '') {
    return;
}
?>
<section class="section" style="padding-top:0">
  <div class="container">
    <a class="btn primary" href="<?= e($url) ?>"><?= e($label) ?></a>
  </div>
</section>
