<?php
use App\Models\User;
/** @var array<int,\App\Models\ActivityLog> $logs */
?>
<div class="admin-topbar"><h1>Journal d'activite</h1></div>

<table>
  <tr><th>Action</th><th>Utilisateur</th><th>Description</th><th>Date</th></tr>
  <?php foreach ($logs as $log): $user = $log->getAttribute('user_id') ? User::find((int) $log->getAttribute('user_id')) : null; ?>
    <tr>
      <td><?= e($log->getAttribute('action')) ?></td>
      <td><?= e($user?->fullName() ?? 'Systeme') ?></td>
      <td><?= e($log->getAttribute('description')) ?></td>
      <td><?= e($log->getAttribute('created_at')) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
