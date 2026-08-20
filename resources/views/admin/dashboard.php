<?php
/**
 * @var int $pageCount
 * @var int $servicesCount
 * @var int $citiesCount
 * @var array<int,\App\Models\Lead> $recentLeads
 * @var array<int,\App\Models\Page> $placeholderPages
 * @var \App\Models\AiProvider|null $aiProvider
 * @var array<int,\App\Models\AiGeneration> $recentGenerations
 * @var int $failedGenerationsCount
 * @var string|null $installedAt
 */
?>
<div class="admin-topbar"><h1>Tableau de bord</h1></div>

<div class="stat-grid">
  <div class="stat-card"><b><?= (int) $pageCount ?></b><span>Pages</span></div>
  <div class="stat-card"><b><?= (int) $servicesCount ?></b><span>Services</span></div>
  <div class="stat-card"><b><?= (int) $citiesCount ?></b><span>Villes</span></div>
  <div class="stat-card"><b><?= count($recentLeads) ?></b><span>Demandes recentes</span></div>
</div>

<?php if (!empty($placeholderPages)): ?>
  <div class="alert warn">
    <?= count($placeholderPages) ?> page(s) contiennent encore un contenu temporaire non genere.
    <a href="/admin/pages">Voir les pages</a>.
  </div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0">Etat de l'IA</h3>
  <p>Fournisseur actif : <strong><?= e($aiProvider?->getAttribute('provider') ?? 'aucun') ?></strong>
    <?php if ($aiProvider?->getAttribute('model')): ?> (<?= e($aiProvider->getAttribute('model')) ?>)<?php endif; ?>
  </p>
  <?php if ($failedGenerationsCount > 0): ?>
    <p style="color:var(--danger)"><?= (int) $failedGenerationsCount ?> generation(s) en echec au total. <a href="/admin/ai">Voir le journal</a>.</p>
  <?php endif; ?>
  <a class="btn secondary sm" href="/admin/ai">Gerer l'IA</a>
</div>

<div class="card">
  <h3 style="margin-top:0">Dernieres demandes</h3>
  <?php if (empty($recentLeads)): ?>
    <p style="color:var(--muted);font-size:13px">Aucune demande recue pour le moment.</p>
  <?php else: ?>
    <table>
      <tr><th>Nom</th><th>Contact</th><th>Statut</th><th>Date</th></tr>
      <?php foreach ($recentLeads as $lead): ?>
        <tr>
          <td><?= e($lead->getAttribute('name')) ?></td>
          <td><?= e($lead->getAttribute('phone') ?: $lead->getAttribute('email')) ?></td>
          <td><span class="badge draft"><?= e($lead->getAttribute('status')) ?></span></td>
          <td><?= e($lead->getAttribute('created_at')) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <p style="margin-top:12px"><a class="btn secondary sm" href="/admin/leads">Voir toutes les demandes</a></p>
  <?php endif; ?>
</div>

<div class="card">
  <h3 style="margin-top:0">Etat du site</h3>
  <p style="font-size:13px;color:var(--muted)">
    Installe le <?= e($installedAt ?: 'N/C') ?>. Le site public et le fichier <a href="/robots.txt">robots.txt</a> sont actifs.
  </p>
</div>
