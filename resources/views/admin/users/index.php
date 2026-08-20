<?php
/** @var array<int,\App\Models\User> $users */
$errors = flash_errors();
$success = flash_message('success');
?>
<div class="admin-topbar"><h1>Utilisateurs</h1></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<?php if (!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<div class="card">
  <table>
    <tr><th>Nom</th><th>E-mail</th><th>Role</th><th>Statut</th><th></th></tr>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= e($user->fullName()) ?></td>
        <td><?= e($user->getAttribute('email')) ?></td>
        <td><?= e($user->getAttribute('role')) ?></td>
        <td><span class="badge <?= $user->getAttribute('is_active') ? 'published' : 'archived' ?>"><?= $user->getAttribute('is_active') ? 'actif' : 'inactif' ?></span></td>
        <td>
          <form method="post" action="/admin/users/<?= (int) $user->id() ?>/toggle">
            <?= csrf_field() ?>
            <button type="submit" class="btn secondary sm"><?= $user->getAttribute('is_active') ? 'Desactiver' : 'Activer' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h3 style="margin-top:0">Ajouter un utilisateur</h3>
  <form method="post" action="/admin/users">
    <?= csrf_field() ?>
    <div class="row">
      <div><label>Prenom</label><input type="text" name="first_name" required></div>
      <div><label>Nom</label><input type="text" name="last_name"></div>
    </div>
    <label>E-mail</label>
    <input type="email" name="email" required>
    <label>Mot de passe</label>
    <input type="password" name="password" minlength="10" required>
    <label>Role</label>
    <select name="role">
      <option value="editor">Editeur</option>
      <option value="admin">Administrateur</option>
      <option value="super_admin">Super administrateur</option>
    </select>
    <button type="submit">Creer</button>
  </form>
</div>
