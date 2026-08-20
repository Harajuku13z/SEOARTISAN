<?php
/** @var array<string,mixed> $data */
$url = (string) ($data['url'] ?? '');
if ($url === '') {
    return;
}
$alt = (string) ($data['alt'] ?? '');
?>
<section class="section">
  <div class="container">
    <img src="<?= e($url) ?>" alt="<?= e($alt) ?>" loading="lazy" style="border-radius:14px;width:100%">
  </div>
</section>
