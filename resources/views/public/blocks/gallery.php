<?php
/** @var array<string,mixed> $data */
$images = (array) ($data['images'] ?? []);
if ($images === []) {
    return;
}
?>
<section class="section">
  <div class="container">
    <div class="gallery-grid">
      <?php foreach ($images as $img): ?>
        <figure>
          <img src="<?= e($img['url'] ?? '') ?>" alt="<?= e($img['alt'] ?? '') ?>" loading="lazy">
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
