<?php
/** @var array<string,mixed> $data */
use App\Models\Company;

$title = (string) ($data['title'] ?? '');
$text = (string) ($data['text'] ?? '');
if ($title === '' && $text === '') {
    return;
}
$company = Company::current();
$phone = $company?->getAttribute('phone');
?>
<section class="cta-split centered">
  <div class="container">
    <?php if ($title !== ''): ?><h2><?= e($title) ?></h2><?php endif; ?>
    <?php if ($text !== ''): ?><p class="lead"><?= e($text) ?></p><?php endif; ?>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:20px">
      <a class="btn on-color" href="#quote-form">Demander un devis gratuit</a>
      <?php if ($phone): ?><a class="btn on-color-outline" href="tel:<?= e(preg_replace('/\s+/', '', (string) $phone)) ?>">Appeler : <?= e($phone) ?></a><?php endif; ?>
    </div>
  </div>
</section>
