<?php
/**
 * @var \App\Models\Company|null $company
 * @var array<int,array<string,mixed>> $menuServices
 * @var string $content
 * @var string|null $pageTitle
 * @var string|null $metaDescription
 * @var string|null $canonicalUrl
 * @var string|null $ogImageUrl
 * @var array<string,mixed>|null $jsonLd
 */
$siteName = $company?->getAttribute('trade_name') ?? config('app.name');
$title = $pageTitle ? ($pageTitle . ' - ' . $siteName) : $siteName;
$description = $metaDescription ?: (string) ($company?->getAttribute('short_description') ?? '');
$isHomePage = isset($page) && $page instanceof \App\Models\Page && $page->getAttribute('type') === 'home';
?>
<!doctype html>
<html lang="<?= e(config('app.locale')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if (!empty($canonicalUrl)): ?><link rel="canonical" href="<?= e($canonicalUrl) ?>"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<?php if (!empty($ogImageUrl)): ?><meta property="og:image" content="<?= e($ogImageUrl) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php if (!empty($jsonLd)): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Public+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/theme.css?v=20260805-7">
<link rel="stylesheet" href="/assets/css/professional.css?v=20260810-2">
<?php if ($company): ?>
<style>
  :root {
    --color-primary: <?= e($company->getAttribute('primary_color') ?: '#2f6fed') ?>;
    --color-secondary: <?= e($company->getAttribute('secondary_color') ?: '#1f2430') ?>;
    --color-accent: <?= e($company->getAttribute('accent_color') ?: '#e8a53d') ?>;
    --nr-teal: <?= e($company->getAttribute('primary_color') ?: '#294352') ?>;
    --nr-teal-deep: <?= e($company->getAttribute('secondary_color') ?: '#1a2d38') ?>;
    --nr-sky: #38b6ff;
    --nr-yellow: <?= e($company->getAttribute('accent_color') ?: '#ffde59') ?>;
    --font-primary: '<?= e($company->getAttribute('font_primary') ?: 'Manrope') ?>', sans-serif;
    --font-secondary: '<?= e($company->getAttribute('font_secondary') ?: 'Public Sans') ?>', sans-serif;
    --radius-btn: <?= match ($company->getAttribute('button_style')) { 'square' => '2px', 'pill' => '999px', default => '8px' } ?>;
  }
</style>
<?php endif; ?>
</head>
<body class="<?= $isHomePage ? 'page-home' : 'page-inner' ?>">
<?= view('public.partials.header', ['company' => $company, 'menuServices' => $menuServices, 'siteMenu' => $siteMenu ?? [], 'logoUrl' => $logoUrl ?? null]) ?>
<?= $content ?>
<?= view('public.partials.footer', ['company' => $company, 'menuServices' => $menuServices, 'siteMenu' => $siteMenu ?? [], 'logoUrl' => $logoUrl ?? null]) ?>
<?php if ($company && !empty($company->getAttribute('whatsapp'))): ?>
  <a class="whatsapp-fab" href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) $company->getAttribute('whatsapp'))) ?>" target="_blank" rel="noopener" aria-label="Contacter sur WhatsApp">WA</a>
<?php endif; ?>
<script src="/assets/js/theme.js?v=20260805-3" defer></script>
</body>
</html>
