<?php
use App\Models\Media;
/**
 * @var \App\Models\Company|null $company
 * @var array<int,array<string,mixed>> $menuServices
 */
$name = $company?->getAttribute('trade_name') ?? config('app.name');
$initials = mb_strtoupper(mb_substr((string) $name, 0, 1));
$social = $company?->getAttribute('social_links') ?? [];
$year = date('Y');
$lightLogo = $company?->getAttribute('logo_light_media_id')
    ? Media::find((int)$company->getAttribute('logo_light_media_id'))?->getAttribute('url')
    : null;
$footerCategories = !empty($siteMenu) ? $siteMenu : ($menuServices ?? []);
?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <div class="footer-brand">
        <span class="brand-badge"><?php if ($lightLogo || !empty($logoUrl)): ?><img src="<?= e($lightLogo ?: $logoUrl) ?>" alt="Logo <?= e($name) ?>"><?php else: ?><?= e($initials) ?><?php endif; ?></span>
      </div>
      <?php if ($company && $company->getAttribute('short_description')): ?>
        <p><?= e($company->getAttribute('short_description')) ?></p>
      <?php endif; ?>
    </div>
    <div>
      <h4>Nos services</h4>
      <div class="footer-service-groups"><?php foreach ($footerCategories as $category): ?><a href="<?= e($category['url'] ?? ('/' . ltrim((string)($category['slug'] ?? ''), '/'))) ?>"><?= e($category['label'] ?? $category['public_name'] ?? '') ?></a><?php endforeach; ?></div>
    </div>
    <div>
      <h4>Navigation</h4>
      <a href="/avis-clients">Tous les avis clients</a>
      <a href="/realisations">Nos réalisations</a>
      <a href="/a-propos">À propos</a>
      <a href="/blog/">Blog & conseils</a>
      <a href="/contact">Demander un devis</a>
    </div>
    <div>
      <h4>Contact</h4>
      <?php if ($company && ($company->getAttribute('address') || $company->getAttribute('city'))): ?><a class="footer-address" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(trim((string)$company->getAttribute('address').' '.(string)$company->getAttribute('postal_code').' '.(string)$company->getAttribute('city')))) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3" fill="none" stroke="currentColor" stroke-width="2"/></svg><span><?= e($company->getAttribute('address')) ?><br><?= e($company->getAttribute('postal_code')) ?> <?= e($company->getAttribute('city')) ?></span></a><?php endif; ?>
      <?php if ($company && $company->getAttribute('phone')): ?><a class="footer-address" href="tel:<?= e(preg_replace('/\s+/', '', (string) $company->getAttribute('phone'))) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg><span><?= e($company->getAttribute('phone')) ?></span></a><?php endif; ?>
      <?php if ($company && $company->getAttribute('public_email')): ?><a class="footer-address" href="mailto:<?= e($company->getAttribute('public_email')) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m2 6 10 7 10-7"/></svg><span><?= e($company->getAttribute('public_email')) ?></span></a><?php endif; ?>
      <?php if (!empty($social['linkedin'])): ?><a href="<?= e($social['linkedin']) ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
      <?php if (!empty($social['facebook']) || !empty($social['instagram'])): ?><div class="footer-contact-socials">
        <?php if (!empty($social['facebook'])): ?><a href="<?= e($social['facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06C2 17.08 5.66 21.25 10.44 22v-7.03H7.9v-2.91h2.54V9.85c0-2.52 1.49-3.91 3.77-3.91 1.09 0 2.23.2 2.23.2v2.46h-1.25c-1.24 0-1.63.77-1.63 1.56v1.9h2.77l-.44 2.91h-2.33V22C18.34 21.25 22 17.08 22 12.06Z"/></svg></a><?php endif; ?>
        <?php if (!empty($social['instagram'])): ?><a class="instagram-link" href="<?= e($social['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="instagramGradientFooter" x1="2" y1="22" x2="22" y2="2" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD600"/><stop offset=".42" stop-color="#FF0169"/><stop offset="1" stop-color="#D300C5"/></linearGradient></defs><rect width="24" height="24" rx="6" fill="url(#instagramGradientFooter)"/><rect x="5.2" y="5.2" width="13.6" height="13.6" rx="4" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="12" cy="12" r="3.3" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="17" cy="7.2" r="1" fill="#fff"/></svg></a><?php endif; ?>
      </div><?php endif; ?>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>&copy; <?= e($year) ?> <?= e($name) ?> - Tous droits reserves</span>
    <div style="display:flex;gap:16px">
      <a href="/mentions-legales">Mentions legales</a>
      <a href="/politique-confidentialite">Confidentialite</a>
      <a href="/politique-cookies">Cookies</a>
    </div>
  </div>
</footer>
