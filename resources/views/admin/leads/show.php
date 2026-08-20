<?php
/**
 * @var \App\Models\Lead $lead
 * @var array<int,\App\Models\LeadNote> $notes
 * @var array<int,string> $statuses
 * @var string $sourcePath
 */
$labels = ['new' => 'Nouveau', 'abandoned' => 'Abandonné / incomplet', 'completed' => 'Terminé', 'to_contact' => 'A contacter', 'contacted' => 'Contacte', 'quoted' => 'Devis envoye', 'won' => 'Gagne', 'lost' => 'Perdu', 'spam' => 'Spam'];
?>
<div class="admin-topbar"><h1><?= e($lead->getAttribute('name')) ?></h1></div>

<div class="card">
  <p><strong>Telephone :</strong> <?= e($lead->getAttribute('phone') ?: 'N/C') ?></p>
  <p><strong>E-mail :</strong> <?= e($lead->getAttribute('email') ?: 'N/C') ?></p>
  <p><strong>Ville :</strong> <?= e($lead->getAttribute('city')) ?> <?= e($lead->getAttribute('postal_code')) ?></p>
  <p><strong>Creneau souhaite :</strong> <?= e($lead->getAttribute('time_slot') ?: 'N/C') ?></p>
  <p><strong>Page d’origine :</strong> <a href="<?= e($sourcePath ?: '/') ?>" target="_blank" rel="noopener"><?= e($sourcePath ?: '/') ?></a></p>
  <p><strong>Message :</strong><br><?= nl2br(e($lead->getAttribute('message') ?: '-')) ?></p>
  <p><strong>Recu le :</strong> <?= e($lead->getAttribute('created_at')) ?></p>

  <form method="post" action="/admin/leads/<?= (int) $lead->id() ?>/status" style="margin-top:14px">
    <?= csrf_field() ?>
    <label>Statut</label>
    <select name="status" onchange="this.form.submit()">
      <?php foreach ($statuses as $s): ?>
        <option value="<?= e($s) ?>" <?= $lead->getAttribute('status') === $s ? 'selected' : '' ?>><?= e($labels[$s] ?? $s) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="card">
  <h3 style="margin-top:0">Notes</h3>
  <?php foreach ($notes as $note): ?>
    <div style="border-bottom:1px solid var(--border);padding:8px 0;font-size:13px">
      <?= nl2br(e($note->getAttribute('note'))) ?>
      <div style="color:var(--muted);font-size:12px"><?= e($note->getAttribute('created_at')) ?></div>
    </div>
  <?php endforeach; ?>
  <form method="post" action="/admin/leads/<?= (int) $lead->id() ?>/notes" style="margin-top:12px">
    <?= csrf_field() ?>
    <textarea name="note" rows="3" placeholder="Ajouter une note..."></textarea>
    <button type="submit" class="btn secondary">Ajouter</button>
  </form>
</div>
