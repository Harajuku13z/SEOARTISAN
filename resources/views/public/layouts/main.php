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
$trackingGet=static function(string $key,mixed $default=null):mixed{$row=\App\Models\Setting::first(['key'=>$key]);if(!$row)return $default;$value=json_decode((string)$row->getAttribute('value'),true);return $value??$default;};
$trackingEnabled=(bool)$trackingGet('tracking.enabled',false);
$googleTagId=trim((string)$trackingGet('tracking.google_tag_id',''));
$trackingConfig=['enabled'=>$trackingEnabled,'endpoint'=>'/track/event','csrf'=>csrf_token(),'googleTagId'=>$googleTagId,'googleCallLabel'=>(string)$trackingGet('tracking.google_call_label',''),'googleFormLabel'=>(string)$trackingGet('tracking.google_form_label','')];
?>
<!doctype html>
<html lang="<?= e(config('app.locale')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
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
<?php if($trackingEnabled&&preg_match('/^(AW-\d+|G-[A-Z0-9]+)$/',$googleTagId)): ?><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('consent','default',{analytics_storage:'denied',ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied'});gtag('js',new Date());gtag('config',<?= json_encode($googleTagId) ?>);</script><script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(rawurlencode($googleTagId)) ?>"></script><?php endif; ?>
<script>window.__trackingConfig=<?= json_encode($trackingConfig,JSON_UNESCAPED_SLASHES) ?>;</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=Public+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/theme.css?v=20260821-1">
<link rel="stylesheet" href="/assets/css/professional.css?v=20260821-2">
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
  <a class="whatsapp-fab" href="https://wa.me/<?= e(preg_replace('/\D/', '', (string) $company->getAttribute('whatsapp'))) ?>" target="_blank" rel="noopener" aria-label="Contacter sur WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12.04 2a9.84 9.84 0 0 0-8.49 14.8L2 22l5.34-1.5A9.95 9.95 0 1 0 12.04 2Zm0 17.93a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.17.89.85-3.09-.2-.32a8.08 8.08 0 1 1 6.95 3.83Zm4.43-6.06c-.24-.12-1.44-.71-1.66-.79-.22-.08-.38-.12-.54.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.02-.38-1.95-1.2a7.3 7.3 0 0 1-1.35-1.68c-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.47-.4-.4-.54-.41h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.58 4.1 3.62.57.25 1.02.4 1.37.51.58.18 1.1.16 1.51.1.46-.07 1.44-.59 1.64-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.46-.28Z"/></svg></a>
<?php endif; ?>
<?php if($trackingEnabled): ?><div class="tracking-consent" data-tracking-consent hidden><p>Avec votre accord, nous utilisons la mesure Google pour comprendre les appels et demandes issus du site. Le suivi interne anti-spam reste strictement nécessaire à la sécurité.</p><button type="button" data-consent-accept>Accepter la mesure</button><button type="button" data-consent-refuse>Refuser</button><a href="/politique-cookies">En savoir plus</a></div><?php endif; ?>
<script src="/assets/js/theme.js?v=20260821-3" defer></script>
</body>
</html>
