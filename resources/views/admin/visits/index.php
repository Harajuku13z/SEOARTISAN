<?php
/**
 * @var array<string,int> $stats
 * @var array<int,array<string,mixed>> $byCategory
 * @var array<int,array<string,mixed>> $byBot
 * @var array<int,array<string,mixed>> $topPages
 * @var array<int,array<string,mixed>> $daily
 * @var array<string,mixed> $settings
 */
$categoryLabels = ['search' => 'Moteurs de recherche', 'ai' => 'IA (GPT, Claude, Perplexity…)', 'social' => 'Aperçus réseaux sociaux', 'tool' => 'Outils & scrapers'];
$maxDaily = 1;
foreach ($daily as $row) {
    $maxDaily = max($maxDaily, (int) $row['humans'] + (int) $row['bots']);
}
?>
<div class="admin-page">
  <header class="admin-page-head">
    <div>
      <h1>Audience &amp; robots</h1>
      <p class="admin-page-sub">Compteur de visites humaines, filtrage des robots et notification aux IA.</p>
    </div>
  </header>

  <?php if (!empty($_GET['success'])): ?><div class="alert alert-success">Réglages enregistrés.</div><?php endif; ?>

  <section class="stats-grid">
    <article class="stat-card"><b><?= number_format($stats['humans_total'], 0, ',', ' ') ?></b><span>Visiteurs humains (total)</span></article>
    <article class="stat-card"><b><?= number_format($stats['humans_today'], 0, ',', ' ') ?></b><span>Aujourd'hui</span></article>
    <article class="stat-card"><b><?= number_format($stats['humans_7d'], 0, ',', ' ') ?></b><span>7 derniers jours</span></article>
    <article class="stat-card"><b><?= number_format($stats['humans_30d'], 0, ',', ' ') ?></b><span>30 derniers jours</span></article>
    <article class="stat-card"><b><?= number_format($stats['bots_7d'], 0, ',', ' ') ?></b><span>Robots (7 j)</span></article>
    <article class="stat-card"><b><?= number_format($stats['blocked_7d'], 0, ',', ' ') ?></b><span>Bots bloqués (7 j)</span></article>
  </section>

  <section class="admin-panel">
    <h2>Activité des 14 derniers jours</h2>
    <div class="visits-chart">
      <?php foreach ($daily as $row): $total = max(1, (int) $row['humans'] + (int) $row['bots']); ?>
      <div class="visits-chart-col" title="<?= e($row['day']) ?> : <?= (int) $row['humans'] ?> humains, <?= (int) $row['bots'] ?> robots">
        <div class="visits-chart-bar" style="height:<?= max(4, (int) round($total / $maxDaily * 100)) ?>%"><i style="height:<?= (int) round((int) $row['humans'] / max(1, $total) * 100) ?>%"></i></div>
        <small><?= e(date('d/m', strtotime((string) $row['day']))) ?></small>
      </div>
      <?php endforeach; ?>
      <?php if (!$daily): ?><p>Aucune donnée pour le moment.</p><?php endif; ?>
    </div>
    <small class="admin-hint">Barre pleine = total du jour · partie orange = visiteurs humains.</small>
  </section>

  <div class="admin-columns">
    <section class="admin-panel">
      <h2>Robots des 7 derniers jours</h2>
      <table class="admin-table">
        <thead><tr><th>Catégorie</th><th>Requêtes</th></tr></thead>
        <tbody>
          <?php foreach ($byCategory as $row): ?>
          <tr><td><?= e($categoryLabels[$row['bot_category']] ?? $row['bot_category']) ?></td><td><?= number_format((int) $row['n'], 0, ',', ' ') ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$byCategory): ?><tr><td colspan="2">Aucun robot détecté.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </section>

    <section class="admin-panel">
      <h2>Top robots identifiés</h2>
      <table class="admin-table">
        <thead><tr><th>Robot</th><th>Catégorie</th><th>Requêtes</th></tr></thead>
        <tbody>
          <?php foreach ($byBot as $row): ?>
          <tr><td><?= e((string) $row['bot_name']) ?></td><td><?= e($categoryLabels[$row['bot_category']] ?? $row['bot_category']) ?></td><td><?= number_format((int) $row['n'], 0, ',', ' ') ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$byBot): ?><tr><td colspan="3">Aucun robot identifié.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>

  <section class="admin-panel">
    <h2>Pages les plus vues (7 j, humains)</h2>
    <table class="admin-table">
      <thead><tr><th>Page</th><th>Vues</th></tr></thead>
      <tbody>
        <?php foreach ($topPages as $row): ?>
        <tr><td><a href="<?= e((string) $row['path']) ?>"><?= e((string) $row['path']) ?></a></td><td><?= number_format((int) $row['n'], 0, ',', ' ') ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topPages): ?><tr><td colspan="2">Pas encore de données.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>

  <section class="admin-panel">
    <h2>Réglages</h2>
    <form method="post" action="/admin/visits" class="admin-form">
      <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
      <label class="check-row"><input type="checkbox" name="tracking_enabled" value="1" <?= !empty($settings['tracking_enabled']) ? 'checked' : '' ?>> <span><strong>Comptage des visites</strong> — enregistre chaque page vue (humains et robots classés).</span></label>
      <label class="check-row"><input type="checkbox" name="block_bad_bots" value="1" <?= !empty($settings['block_bad_bots']) ? 'checked' : '' ?>> <span><strong>Bloquer les robots indésirables</strong> — scrapers, outils d'audit et bots agressifs reçoivent une erreur 403. Les moteurs de recherche (Google, Bing…) ne sont jamais bloqués.</span></label>
      <label class="check-row"><input type="checkbox" name="allow_ai_bots" value="1" <?= !empty($settings['allow_ai_bots']) ? 'checked' : '' ?>> <span><strong>Notifier et autoriser les IA</strong> — GPTBot, ClaudeBot, PerplexityBot… peuvent explorer le site (robots.txt + llms.txt). Décoché, ils sont bloqués.</span></label>
      <label class="check-row"><input type="checkbox" name="public_counter" value="1" <?= !empty($settings['public_counter']) ? 'checked' : '' ?>> <span><strong>Afficher le compteur public</strong> — nombre de visiteurs dans le pied de page.</span></label>
      <label class="field-row"><span>Point de départ du compteur :</span> <input type="number" name="counter_offset" min="0" value="<?= (int) $settings['counter_offset'] ?>"></label>
      <button type="submit" class="btn primary">Enregistrer les réglages</button>
    </form>
  </section>
</div>
