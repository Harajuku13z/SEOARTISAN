<?php
/** @var array<string,mixed> $data */
$address = trim((string) ($data['address'] ?? '') . ' ' . (string) ($data['city'] ?? ''));
if ($address === '') {
    return;
}
$query = urlencode($address);
?>
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="map-embed">
      <iframe
        src="https://maps.google.com/maps?q=<?= $query ?>&output=embed"
        width="100%" height="360" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
        title="Localisation"></iframe>
    </div>
  </div>
</section>
