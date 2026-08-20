<?php
/** @var array<string,mixed> $data */
$items = (array) ($data['items'] ?? []);
if ($items === []) {
    return;
}
?>
<section class="section" id="faq">
  <div class="container" style="max-width:820px">
    <span class="eyebrow">Questions frequentes</span>
    <h2 style="margin-bottom:24px">Vous vous demandez peut-etre...</h2>
    <div class="faq-list">
      <?php foreach ($items as $item): if (empty($item['question'])) continue; ?>
        <details class="faq-item">
          <summary><?= e($item['question']) ?></summary>
          <p><?= e($item['answer'] ?? '') ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
