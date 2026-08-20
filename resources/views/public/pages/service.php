<?php
use App\Models\CompanyService;
use App\Models\CompanyLocation;
use App\Models\City;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Project;
use App\Models\Setting;
use App\Services\Content\MenuService;
$service = $page->getAttribute('company_service_id') ? CompanyService::find((int)$page->getAttribute('company_service_id')) : null;
$image = $service?->getAttribute('image_media_id') ? Media::find((int)$service->getAttribute('image_media_id'))?->getAttribute('url') : null;
$image = $image ?: ($company?->getAttribute('hero_media_id') ? Media::find((int)$company->getAttribute('hero_media_id'))?->getAttribute('url') : null);
$phone = (string)($company?->getAttribute('phone') ?? '');
$phoneHref = preg_replace('/\D+/', '', $phone);
$description = (string)($service?->getAttribute('description') ?: $page->getAttribute('meta_description'));
$city = (string)($company?->getAttribute('city') ?? 'votre secteur');
$relatedServices = [];
$currentUrl = '/'.ltrim((string)$page->getAttribute('slug'), '/'); $parentId = '';
foreach (MenuService::items() as $item) if (($item['url'] ?? '') === $currentUrl) { $parentId=(string)($item['parent_id'] ?? ''); break; }
if ($parentId !== '') foreach (MenuService::items() as $item) if (($item['parent_id'] ?? '') === $parentId && ($item['url'] ?? '') !== $currentUrl) { $s=CompanyService::first(['slug'=>ltrim((string)$item['url'],'/')]); if($s)$relatedServices[]=$s; }
if (!$relatedServices) {
    $slug = (string)$page->getAttribute('slug');
    $keyword = str_contains($slug,'pompe-a-chaleur') ? 'pompe-a-chaleur' : (str_contains($slug,'climatisation') ? 'climatisation' : (str_contains($slug,'ballon-thermodynamique') ? 'ballon-thermodynamique' : (str_contains($slug,'adoucisseur') ? 'adoucisseur' : '')));
    foreach (CompanyService::all('sort_order ASC') as $candidate) if ($candidate->id() !== $service?->id() && ($keyword === '' || str_contains((string)$candidate->getAttribute('slug'),$keyword))) $relatedServices[]=$candidate;
}
$relatedServices = array_slice($relatedServices,0,6);
$allProjects = Project::visible();
$projects = [];
if ($service) foreach ($allProjects as $project) if ((int)$project->getAttribute('company_service_id') === (int)$service->id()) $projects[] = $project;
$fallbackProjects = array_values(array_filter($allProjects, static fn($project) => !in_array($project, $projects, true)));
shuffle($fallbackProjects);
$projects = array_slice(array_merge($projects, $fallbackProjects), 0, 6);
$faqs = $service ? Faq::forSubject('CompanyService', (int)$service->id()) : [];
$partnerIds = json_decode((string)(Setting::first(['key'=>'branding.partner_logo_ids'])?->getAttribute('value')??'[]'),true)?:[];
$partnerLogos = array_values(array_filter(array_map(static fn($id)=>Media::find((int)$id),$partnerIds)));
$serviceName = (string)($service?->getAttribute('public_name') ?: $page->getAttribute('title'));
$serviceSlug = (string)$page->getAttribute('slug');
$action = str_contains($serviceSlug,'entretien') ? 'entretien' : (str_contains($serviceSlug,'depannage') ? 'dépannage' : (str_contains($serviceSlug,'remplacement') ? 'remplacement' : 'installation'));
$equipment = trim(preg_replace('/^(installation|entretien|dépannage|depannage|remplacement)\s+(de |du |d’|d\')?/iu','',$serviceName));
$equipment = $equipment !== '' ? $equipment : $serviceName;
$editorial = match($action) {
  'entretien' => ['Un équipement fiable et performant, saison après saison.','Nous assurons l’entretien de votre '.$equipment.' afin de préserver ses performances, limiter les risques de panne et prolonger sa durée de vie. Notre intervention comprend les contrôles essentiels et des conseils adaptés à votre installation.',['Contrôle complet de l’équipement','Préservation des performances','Conseils d’utilisation personnalisés']],
  'dépannage' => ['Retrouvez rapidement le confort de votre logement.','Notre équipe intervient pour diagnostiquer la panne de votre '.$equipment.', identifier son origine et vous proposer une solution claire. Chaque intervention privilégie la sécurité, la fiabilité et la remise en service durable.',['Diagnostic précis','Intervention dans votre secteur','Solution expliquée avant intervention']],
  'remplacement' => ['Modernisez votre installation en toute sérénité.','Nous étudions votre équipement actuel et vos besoins afin de remplacer votre '.$equipment.' par une solution correctement dimensionnée, performante et adaptée à votre logement.',['Étude de l’installation existante','Équipement adapté à vos besoins','Pose et mise en service soignées']],
  default => ['Une installation pensée pour votre confort et vos économies.','Nous vous accompagnons de l’étude du projet jusqu’à la mise en service de votre '.$equipment.'. Le choix de l’équipement, son dimensionnement et son implantation sont étudiés selon votre logement et vos habitudes.',['Étude personnalisée du logement','Dimensionnement adapté','Installation et mise en service']],
};
$copyMap=json_decode((string)(Setting::first(['key'=>'content.service_copy_map'])?->getAttribute('value')??'{}'),true)?:[];
$savedCopy=(array)($copyMap[(string)($service?->id()??0)]??[]);
$heroEyebrow=trim((string)($savedCopy['hero_eyebrow']??''))?:(ucfirst($action).' · '.$city);
$heroIntro=trim((string)($savedCopy['hero_intro']??''))?:$editorial[0];
$aboutTitle=trim((string)($savedCopy['about_title']??''))?:$editorial[0];
$aboutText=trim((string)($savedCopy['about_text']??''))?:$editorial[1];
$localCities=[];
foreach(CompanyLocation::where(['is_active'=>1]) as $location){$localCity=City::find((int)$location->getAttribute('city_id'));if($localCity)$localCities[]=(string)$localCity->getAttribute('name');if(count($localCities)>=10)break;}
$serviceRegion=(string)($company?->getAttribute('region')??'');
?>
<?= view('public.partials.breadcrumbs', ['items'=>$breadcrumbs ?? []]) ?>
<main class="service-premium">
  <section class="service-premium-hero"><?php if($image): ?><img src="<?= e($image) ?>" alt="<?= e($serviceName) ?>" fetchpriority="high"><?php endif; ?><div class="service-premium-shade"></div><div class="service-premium-copy"><span class="ph-eyebrow"><?= e($heroEyebrow) ?></span><h1><?= e($page->getAttribute('h1') ?: $serviceName) ?></h1><p><?= e($heroIntro) ?></p><div class="service-premium-actions"><a href="#demande-devis">Demander un devis gratuit</a><?php if($phone): ?><a href="tel:<?= e($phoneHref) ?>">Appeler — <?= e($phone) ?></a><?php endif; ?></div></div></section>

  <section class="service-about"><div class="service-about-inner"><span class="eyebrow">À propos du service</span><h2><?= e($aboutTitle) ?></h2><div class="service-about-layout"><div class="service-about-text"><?= $aboutText ?></div><?php if($localCities||$serviceRegion!==''): ?><div class="service-about-cities-col"><h3 class="service-about-cities-title">Zone d’intervention</h3><ul class="service-about-cities"><?php foreach($localCities as $localCity): ?><li><?= e($serviceName) ?> à <?= e($localCity) ?></li><?php endforeach; ?><?php if($serviceRegion!==''): ?><li><?= e($serviceName) ?> en <?= e($serviceRegion) ?></li><?php endif; ?></ul></div><?php endif; ?></div><div class="service-about-cta"><strong>Vous souhaitez vérifier la faisabilité de votre projet ?</strong><a href="#demande-devis">Échanger avec un conseiller →</a></div></div></section>

  <?php if($relatedServices): ?><section class="marketing-section service-offers"><div class="marketing-head"><div><span class="eyebrow">Nos prestations associées</span><h2>Découvrez aussi nos autres services</h2></div><p>Entretien, dépannage ou solutions complémentaires : retrouvez les prestations liées à votre équipement.</p></div><div class="marketing-service-grid"><?php foreach($relatedServices as $related): $relatedImage=$related->getAttribute('image_media_id')?Media::find((int)$related->getAttribute('image_media_id'))?->getAttribute('url'):null; ?><article class="marketing-service-card<?= $relatedImage?'':' no-image' ?>"><?php if($relatedImage): ?><img src="<?= e($relatedImage) ?>" alt="<?= e($related->getAttribute('public_name')) ?>" loading="lazy"><?php endif; ?><div><h3><?= e($related->getAttribute('public_name')) ?></h3><p><?= e($related->getAttribute('description')) ?></p><a class="service-contact-button" href="/contact?service=<?= e(rawurlencode((string)$related->getAttribute('slug'))) ?>">C’est ce que je veux →</a></div></article><?php endforeach; ?></div></section><?php endif; ?>

  <?= view('public.partials.marketing_sections', ['company'=>$company,'relatedServices'=>[],'projects'=>$projects,'faqs'=>$faqs,'partnerLogos'=>$partnerLogos,'currentService'=>$service]) ?>
</main>
