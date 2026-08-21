<?php
/**
 * @var string $content
 * @var string $activeNav
 * @var \App\Models\User|null $currentUser
 * @var int $newLeadsCount
 */
if (!function_exists('admin_nav_link')) {
    function admin_nav_link(string $key, string $activeNav, string $url, string $label): string
    {
        $class = $key === $activeNav ? 'active' : '';

        return '<a href="' . e($url) . '" class="' . $class . '">' . e($label) . '</a>';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration - <?= e(config('app.name')) ?></title>
<link rel="stylesheet" href="/assets/css/admin.css?v=20260805-1">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="brand-card">
      <div class="brand-kicker">Console artisan</div>
      <div class="brand"><?= e(config('app.name')) ?></div>
      <p>Pilotez votre présence locale, vos contenus et vos demandes clients.</p>
    </div>
    <nav class="admin-nav">
      <?= admin_nav_link('dashboard', $activeNav, '/admin', 'Vue d’ensemble') ?>
      <div class="group-label">Prospects</div>
      <a href="/admin/leads" class="admin-leads-link <?= $activeNav === 'leads' ? 'active' : '' ?>"><span>Formulaires &amp; leads</span><?php if (($newLeadsCount ?? 0) > 0): ?><b class="admin-notification"><?= (int) $newLeadsCount ?></b><?php endif; ?></a>
      <?= admin_nav_link('conversions', $activeNav, '/admin/conversions', 'Conversions & anti-bot') ?>
      <div class="group-label">Site &amp; contenus</div>
      <?= admin_nav_link('company', $activeNav, '/admin/company', 'Entreprise & identité') ?>
      <?= admin_nav_link('pages', $activeNav, '/admin/pages', 'Accueil & pages') ?>
      <?= admin_nav_link('blog', $activeNav, '/admin/blog', 'Blog WordPress') ?>
      <?= admin_nav_link('menu', $activeNav, '/admin/menu', 'Menu & sous-menus') ?>
      <?= admin_nav_link('locations', $activeNav, '/admin/locations', 'Zones d’intervention') ?>
      <?= admin_nav_link('local_pages', $activeNav, '/admin/local-pages', 'Pages locales') ?>
      <?= admin_nav_link('categories', $activeNav, '/admin/business-categories', 'Métiers') ?>
      <?= admin_nav_link('services', $activeNav, '/admin/services', 'Services') ?>
      <?= admin_nav_link('projects', $activeNav, '/admin/projects', 'Réalisations') ?>
      <?= admin_nav_link('media', $activeNav, '/admin/media', 'Médiathèque') ?>
      <div class="group-label">Activité</div>
      <?= admin_nav_link('testimonials', $activeNav, '/admin/testimonials', 'Avis Google & clients') ?>
      <?= admin_nav_link('ai', $activeNav, '/admin/ai', 'APIs & intelligence artificielle') ?>
      <div class="group-label">Système</div>
      <?= admin_nav_link('settings', $activeNav, '/admin/settings', 'Réglages SEO') ?>
      <?= admin_nav_link('sitemap', $activeNav, '/admin/sitemap', 'Sitemap XML') ?>
    <?php if ($currentUser?->isSuperAdmin()): ?>
      <?= admin_nav_link('users', $activeNav, '/admin/users', 'Utilisateurs') ?>
    <?php endif; ?>
    <?= admin_nav_link('activity', $activeNav, '/admin/activity-log', "Journal d’activité") ?>
    </nav>
    <form method="post" action="/admin/logout">
      <?= csrf_field() ?>
      <button type="submit" class="sidebar-logout">Se déconnecter</button>
    </form>
  </aside>
  <main class="admin-main">
    <div class="admin-header">
      <div><span class="admin-header-kicker">Espace de gestion</span><strong><?= e(config('app.name')) ?></strong></div>
      <a href="/" target="_blank" rel="noopener">Voir le site ↗</a>
    </div>
    <?= $content ?>
  </main>
</div>
</body>
</html>
