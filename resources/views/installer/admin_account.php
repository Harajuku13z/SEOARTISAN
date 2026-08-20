<?php
$errors = flash_errors();
?>
<h1>Compte administrateur</h1>
<p class="subtitle">Ce compte vous permettra d'acceder a l'administration du site.</p>

<?php if (!empty($errors['form'])): ?>
  <div class="alert error"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="/install/admin-account">
  <?= csrf_field() ?>
  <div class="row">
    <div>
      <label for="first_name">Prenom</label>
      <input type="text" id="first_name" name="first_name" value="<?= old('first_name') ?>" required>
    </div>
    <div>
      <label for="last_name">Nom</label>
      <input type="text" id="last_name" name="last_name" value="<?= old('last_name') ?>" required>
    </div>
  </div>
  <label for="email">Adresse e-mail</label>
  <input type="email" id="email" name="email" value="<?= old('email') ?>" required>
  <div class="row">
    <div>
      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required minlength="10">
    </div>
    <div>
      <label for="password_confirmation">Confirmation</label>
      <input type="password" id="password_confirmation" name="password_confirmation" required minlength="10">
    </div>
  </div>
  <p class="hint">Le mot de passe est stocke de maniere securisee (Argon2id ou bcrypt selon votre serveur).</p>

  <div class="actions">
    <a class="btn secondary" href="/install/database">Retour</a>
    <button type="submit">Continuer</button>
  </div>
</form>
