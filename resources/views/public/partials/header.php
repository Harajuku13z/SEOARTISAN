<?php
/**
 * @var \App\Models\Company|null $company
 * @var array<int,array<string,mixed>> $menuServices
 */
$name = $company?->getAttribute('trade_name') ?? config('app.name');
$initials = mb_strtoupper(mb_substr((string) $name, 0, 1));
$logo = $logoUrl ?? null;
$social = (array)($company?->getAttribute('social_links') ?? []);
$navigationServices = !empty($siteMenu) ? $siteMenu : ($menuServices ?? []);
?>
<header class="site-header">
  <div class="site-header-inner">
  <a href="/" class="brand">
    <span class="brand-badge"><?php if ($logo): ?><img src="<?= e($logo) ?>" alt="Logo <?= e($name) ?>"><?php else: ?><?= e($initials) ?><?php endif; ?></span>
  </a>
  <nav class="site-nav">
    <a href="/">Accueil</a>
    <div class="nav-item has-children services-menu"><a href="/#services" class="services-trigger" aria-expanded="false">Nos services</a><div class="sub-menu mega-menu">
      <?php foreach ($navigationServices as $item): ?><div class="mega-group"><button type="button" class="mega-title" aria-expanded="false"><?= e($item['label'] ?? $item['public_name'] ?? '') ?><span aria-hidden="true">+</span></button><div class="mega-children"><?php if (!empty($item['children'])): ?><a class="mega-category-link" href="<?= e($item['url'] ?? '/#services') ?>">Voir toute la catégorie →</a><?php endif; ?><?php foreach (($item['children'] ?? []) as $child): ?><a href="<?= e($child['url']) ?>"><?= e($child['label']) ?></a><?php endforeach; ?><?php if (empty($item['children']) && !empty($item['slug'])): ?><a href="/<?= e($item['slug']) ?>">Découvrir ce service →</a><?php endif; ?></div></div><?php endforeach; ?>
    </div></div>
    <a href="/realisations">Réalisations</a>
    <a href="/a-propos">À propos</a>
    <a href="/blog">Blog</a>
    <a href="/contact">Contact</a>
  </nav>
  <div style="display:flex;align-items:center;gap:10px">
    <?php if (!empty($social['facebook']) || !empty($social['instagram'])): ?><div class="header-socials">
      <?php if (!empty($social['facebook'])): ?><a href="<?= e($social['facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06C2 17.08 5.66 21.25 10.44 22v-7.03H7.9v-2.91h2.54V9.85c0-2.52 1.49-3.91 3.77-3.91 1.09 0 2.23.2 2.23.2v2.46h-1.25c-1.24 0-1.63.77-1.63 1.56v1.9h2.77l-.44 2.91h-2.33V22C18.34 21.25 22 17.08 22 12.06Z"/></svg></a><?php endif; ?>
      <?php if (!empty($social['instagram'])): ?><a class="instagram-link" href="<?= e($social['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="instagramGradientHeader" x1="2" y1="22" x2="22" y2="2" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD600"/><stop offset=".42" stop-color="#FF0169"/><stop offset="1" stop-color="#D300C5"/></linearGradient></defs><rect width="24" height="24" rx="6" fill="url(#instagramGradientHeader)"/><rect x="5.2" y="5.2" width="13.6" height="13.6" rx="4" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="12" cy="12" r="3.3" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="17" cy="7.2" r="1" fill="#fff"/></svg></a><?php endif; ?>
    </div><?php endif; ?>
    <?php if ($company && $company->getAttribute('phone')): ?>
      <a class="call-btn" href="tel:<?= e(preg_replace('/\s+/', '', (string) $company->getAttribute('phone'))) ?>">
        <svg class="phone-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg><?= e($company->getAttribute('phone')) ?>
      </a>
    <?php endif; ?>
    <button class="mobile-toggle" aria-label="Menu">&#9776;</button>
  </div>
</div>
</header>
