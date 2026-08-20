<?php
use App\Models\CompanyService;
use App\Models\Media;
use App\Models\Project;
use App\Models\Setting;
use App\Services\Content\MenuService;

$groupSlug = (string)$page->getAttribute('slug');
$groupName = (string)($page->getAttribute('h1') ?: $page->getAttribute('title'));
$currentUrl = '/' . ltrim($groupSlug, '/');
$groupServices = [];
foreach (MenuService::tree() as $root) {
    if (($root['url'] ?? '') !== $currentUrl) continue;
    $groupName = (string)($root['label'] ?? $groupName);
    foreach ((array)($root['children'] ?? []) as $child) {
        $service = CompanyService::first(['slug' => ltrim((string)($child['url'] ?? ''), '/')]);
        if ($service && $service->getAttribute('is_active')) $groupServices[] = $service;
    }
    break;
}
if (!$groupServices) foreach (CompanyService::all('sort_order ASC') as $service) {
    if ($service->getAttribute('is_active') && str_contains((string)$service->getAttribute('slug'), $groupSlug)) $groupServices[] = $service;
}
$descriptions = [
    'climatisation' => ['Climatisation sur mesure pour votre confort toute l’année','Notre équipe vous accompagne pour choisir une climatisation performante, silencieuse et adaptée à chaque pièce de votre logement.','Du premier diagnostic à la mise en service, nous étudions la surface, l’isolation et vos habitudes afin de proposer une solution correctement dimensionnée. Notre équipe intervient également pour l’entretien régulier et le dépannage de votre équipement.'],
    'pompe-a-chaleur' => ['Une pompe à chaleur adaptée à votre logement','Réduisez votre consommation tout en améliorant votre confort grâce à une installation étudiée selon votre bâtiment et vos besoins.','Nous vous accompagnons pour l’étude, le dimensionnement, l’installation, la mise en service et le suivi de votre pompe à chaleur.'],
    'chauffage' => ['Des solutions de chauffage fiables et performantes','Installation, remplacement, entretien et dépannage de vos équipements de chauffage dans votre secteur.','Nous analysons votre installation actuelle et vos usages afin de vous orienter vers une solution sûre, confortable et maîtrisée.'],
    'adoucisseur-deau' => ['Une eau plus douce au quotidien','Protégez vos canalisations et vos équipements contre le calcaire avec un adoucisseur adapté à votre foyer.','Notre équipe dimensionne, installe et règle votre équipement selon la dureté de votre eau et votre consommation.'],
    'ballon-thermodynamique' => ['Produisez votre eau chaude plus efficacement','Le ballon thermodynamique utilise les calories de l’air pour réduire la consommation liée à l’eau chaude sanitaire.','Nous étudions son implantation, son volume et son réglage afin de garantir confort, économies et durabilité.'],
    'ventilation' => ['Respirez un air plus sain dans votre logement','Une ventilation adaptée limite l’humidité, renouvelle l’air intérieur et contribue à préserver votre habitation.','Nous proposons des solutions simple flux et double flux adaptées à la configuration du bâtiment.'],
];
$copy = $descriptions[$groupSlug] ?? [$groupName . ' : des solutions adaptées à votre projet',(string)($page->getAttribute('meta_description') ?: 'Découvrez nos prestations, réalisées par une équipe locale et qualifiée.'),'Nous étudions votre besoin afin de vous proposer une intervention claire, durable et adaptée à votre installation.'];
$hero = null;
foreach ($groupServices as $service) if ($service->getAttribute('image_media_id') && ($hero = Media::find((int)$service->getAttribute('image_media_id'))?->getAttribute('url'))) break;
$hero = $hero ?: ($company?->getAttribute('hero_media_id') ? Media::find((int)$company->getAttribute('hero_media_id'))?->getAttribute('url') : null);
$phone = (string)($company?->getAttribute('phone') ?? '');
$phoneHref = preg_replace('/\D+/', '', $phone);
$serviceIds = array_map(static fn($service)=>(int)$service->id(), $groupServices);
$projects = array_values(array_filter(Project::visible(), static fn($project) => $project->getAttribute('category') === $groupSlug || in_array((int)$project->getAttribute('company_service_id'), $serviceIds, true)));
$partnerIds = json_decode((string)(Setting::first(['key'=>'branding.partner_logo_ids'])?->getAttribute('value')??'[]'),true)?:[];
$partnerLogos = array_values(array_filter(array_map(static fn($id)=>Media::find((int)$id),$partnerIds)));
?>
<main class="service-group-page">
  <section class="group-hero"><?php if($hero): ?><img src="<?= e($hero) ?>" alt="<?= e($groupName) ?>" fetchpriority="high"><?php endif; ?><div class="group-hero-shade"></div><div class="group-hero-copy"><span class="ph-eyebrow">Nos solutions · <?= e($company?->getAttribute('city') ?? 'votre secteur') ?></span><h1><?= e($copy[0]) ?></h1><p><?= e($copy[1]) ?></p><div><a class="group-btn accent" href="#groupe-services">Découvrir les services</a><?php if($phone): ?><a class="group-btn light" href="tel:<?= e($phoneHref) ?>">Appelez maintenant — <?= e($phone) ?></a><?php endif; ?></div></div></section>

  <section class="group-services" id="groupe-services"><div class="group-section-head"><div><span class="ph-eyebrow blue"><?= e($groupName) ?></span><h2>Nos services en <?= e(mb_strtolower($groupName)) ?></h2></div><p>Choisissez la prestation correspondant à votre besoin pour découvrir notre accompagnement détaillé.</p></div><div class="group-service-grid"><?php foreach($groupServices as $service):$image=$service->getAttribute('image_media_id')?Media::find((int)$service->getAttribute('image_media_id'))?->getAttribute('url'):$hero; ?><a href="/<?= e($service->getAttribute('slug')) ?>"><?php if($image): ?><img src="<?= e($image) ?>" alt="<?= e($service->getAttribute('public_name')) ?>" loading="lazy"><?php endif; ?><div><h3><?= e($service->getAttribute('public_name')) ?></h3><p><?= e($service->getAttribute('description') ?: 'Une prestation réalisée avec soin, adaptée à votre équipement et à votre logement.') ?></p><span>Découvrir ce service →</span></div></a><?php endforeach; ?></div></section>

  <section class="group-about"><div><span class="ph-eyebrow blue"><?= e($groupName) ?></span><h2><?= e($groupName) ?> : étude, conseil et accompagnement</h2><p><?= e($copy[2]) ?></p><ul><li>Étude personnalisée de votre besoin</li><li>Équipement correctement dimensionné</li><li>Installation et réglages soignés</li><li>Entretien, suivi et service après-vente</li></ul><a href="/simulateur-de-devis">Décrire mon projet étape par étape →</a></div></section>

  <?= view('public.partials.marketing_sections', ['company'=>$company,'relatedServices'=>[],'projects'=>$projects,'partnerLogos'=>$partnerLogos,'currentService'=>null]) ?>
</main>
