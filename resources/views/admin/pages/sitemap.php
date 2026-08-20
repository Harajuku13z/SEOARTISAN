<div class="card">
  <h1><?= e($pageTitle) ?></h1>
  <p>Le sitemap contient automatiquement les pages publiées et indexables, les services actifs et les articles du blog connecté.</p>

  <?php if (!empty($_GET['success'])): ?><div class="alert success"><?= e($_GET['success']) ?></div><?php endif; ?>
  <?php if (!empty($_GET['error'])): ?><div class="alert error"><?= e($_GET['error']) ?></div><?php endif; ?>

  <?php if ($exists): ?>
    <p><strong>Dernière génération :</strong> <?= e($lastModified) ?></p>
    <p><strong>Adresse :</strong> <a href="<?= e($sitemapUrl) ?>" target="_blank" rel="noopener"><?= e($sitemapUrl) ?></a></p>
  <?php else: ?>
    <p>Aucun sitemap n’a encore été généré.</p>
  <?php endif; ?>

  <form method="post" action="/admin/sitemap/generate">
    <?= csrf_field() ?>
    <button type="submit">Générer ou mettre à jour le sitemap</button>
  </form>
</div>

