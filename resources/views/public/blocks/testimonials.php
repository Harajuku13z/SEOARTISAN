<?php
/**
 * Renders only real testimonials (prompt.md: "uniquement s'ils sont
 * reellement fournis") - never seeded, never invented. The rating badge
 * is computed from these same real rows, never a fabricated count.
 */
use App\Models\Testimonial;

$testimonials = Testimonial::visibleGoogle();
if ($testimonials === []) {
    return;
}

$rated = array_filter($testimonials, static fn ($t) => (int) $t->getAttribute('rating') > 0);
$averageRating = $rated !== []
    ? array_sum(array_map(static fn ($t) => (int) $t->getAttribute('rating'), $rated)) / count($rated)
    : null;

$stars = static fn (int $count): string => str_repeat('&#9733;', max(0, min(5, $count)));
?>
<section class="section">
  <div class="container">
    <div class="testimonials-head">
      <div>
        <span class="eyebrow">Avis clients</span>
        <h2>Ce que nos clients en pensent</h2>
      </div>
      <?php if ($averageRating !== null): ?>
        <div class="rating-badge">
          <span class="stars"><?= $stars((int) round($averageRating)) ?></span>
          <strong><?= number_format($averageRating, 1) ?>/5</strong>
          <span class="count">&middot; <?= count($testimonials) ?> avis</span>
        </div>
      <?php endif; ?>
    </div>
    <div class="testimonials-grid">
      <?php foreach ($testimonials as $t): ?>
        <div class="testimonial-card">
          <?php $rating = (int) $t->getAttribute('rating'); if ($rating > 0): ?>
            <div class="stars"><?= $stars($rating) ?></div>
          <?php endif; ?>
          <p>&ldquo;<?= e($t->getAttribute('content')) ?>&rdquo;</p>
          <div class="who">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr((string) $t->getAttribute('author_name'), 0, 1))) ?></span>
            <div>
              <strong><?= e($t->getAttribute('author_name')) ?></strong>
              <?php if ($t->getAttribute('role_or_service')): ?><span><?= e($t->getAttribute('role_or_service')) ?></span><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <a class="reviews-more-button" href="/avis-clients">Voir tous les avis →</a>
  </div>
</section>
