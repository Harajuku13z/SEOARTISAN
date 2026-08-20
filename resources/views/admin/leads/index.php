<?php
/**
 * @var array<int,\App\Models\Lead> $leads
 * @var array<int,string> $statuses
 * @var string $currentStatus
 * @var array<int,string> $leadSources
 */
$labels = ['new' => 'Nouveau', 'abandoned' => 'Abandonné / incomplet', 'completed' => 'Terminé', 'to_contact' => 'A contacter', 'contacted' => 'Contacte', 'quoted' => 'Devis envoye', 'won' => 'Gagne', 'lost' => 'Perdu', 'spam' => 'Spam'];
?>
<div class="admin-topbar">
  <h1>Demandes</h1>
  <a class="btn secondary" href="/admin/leads/export.csv">Exporter en CSV</a>
</div>

<div class="tabs">
  <a href="/admin/leads" class="<?= $currentStatus === '' ? 'active' : '' ?>">Toutes</a>
  <?php foreach ($statuses as $s): ?>
    <a href="/admin/leads?status=<?= e($s) ?>" class="<?= $currentStatus === $s ? 'active' : '' ?>"><?= e($labels[$s] ?? $s) ?></a>
  <?php endforeach; ?>
</div>

<table>
  <tr><th>Nom</th><th>Contact</th><th>Page d’origine</th><th>Ville</th><th>Statut</th><th>Date</th><th></th></tr>
  <?php foreach ($leads as $lead): ?>
    <tr>
      <td><?= e($lead->getAttribute('name')) ?></td>
      <td><?= e($lead->getAttribute('phone') ?: $lead->getAttribute('email')) ?></td>
      <td><a href="<?= e($leadSources[(int)$lead->id()] ?? '/') ?>" target="_blank" rel="noopener"><?= e($leadSources[(int)$lead->id()] ?? '/') ?></a></td>
      <td><?= e($lead->getAttribute('city')) ?></td>
      <td><span class="badge draft"><?= e($labels[$lead->getAttribute('status')] ?? $lead->getAttribute('status')) ?></span></td>
      <td><?= e($lead->getAttribute('created_at')) ?></td>
      <td><a class="btn secondary sm" href="/admin/leads/<?= (int) $lead->id() ?>">Voir</a></td>
    </tr>
  <?php endforeach; ?>
</table>
