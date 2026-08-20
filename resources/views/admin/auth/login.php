<?php
/** @var array<string,mixed> $data */
$errors = flash_errors();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion administration - <?= e(config('app.name')) ?></title>
<style>
  :root { color-scheme: light dark; }
  body { font-family: -apple-system, "Public Sans", sans-serif; background:#f4f5f7; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
  .card { background:#fff; border-radius:12px; padding:40px; width:100%; max-width:360px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
  h1 { font-size:20px; margin:0 0 24px; }
  label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:#333; }
  input { width:100%; box-sizing:border-box; padding:11px 12px; border:1px solid #d7d9dd; border-radius:8px; font-size:14px; margin-bottom:16px; }
  button { width:100%; padding:12px; border:none; border-radius:8px; background:#1f2430; color:#fff; font-weight:700; font-size:14px; cursor:pointer; }
  .error { background:#fdecea; color:#8a1f11; padding:10px 12px; border-radius:8px; font-size:13px; margin-bottom:16px; }
</style>
</head>
<body>
  <form class="card" method="post" action="/admin/login">
    <?= csrf_field() ?>
    <h1>Administration</h1>
    <?php if (!empty($errors['form'])): ?>
      <div class="error"><?= e($errors['form']) ?></div>
    <?php endif; ?>
    <label for="email">Adresse e-mail</label>
    <input type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
    <label for="password">Mot de passe</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Se connecter</button>
  </form>
</body>
</html>
