<?php
use App\Models\CompanyService;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Project;
use App\Models\Setting;

$service=$page->getAttribute('company_service_id')?CompanyService::find((int)$page->getAttribute('company_service_id')):null;
$mediaUrl=static fn($id)=>$id?Media::find((int)$id)?->getAttribute('url'):null;
$hero=$mediaUrl($service?->getAttribute('image_media_id'))?:$mediaUrl($company?->getAttribute('hero_media_id'));
$phone=(string)($company?->getAttribute('phone')??'');$phoneHref=preg_replace('/\D+/','',$phone);
$city=(string)($company?->getAttribute('city')?:'Chalon-sur-Saône');
$related=[];
foreach(CompanyService::all('sort_order ASC') as $candidate){$slug=(string)$candidate->getAttribute('slug');if($candidate->getAttribute('is_active')&&$candidate->id()!==$service?->id()&&str_contains($slug,'pompe-a-chaleur'))$related[]=$candidate;}
$related=array_slice($related,0,4);
$allProjects=Project::visible();$projects=[];
if($service)foreach($allProjects as $project)if((int)$project->getAttribute('company_service_id')===(int)$service->id())$projects[]=$project;
$otherProjects=array_values(array_filter($allProjects,static fn($project)=>!in_array($project,$projects,true)));shuffle($otherProjects);$projects=array_slice(array_merge($projects,$otherProjects),0,6);
$faqs=$service?Faq::forSubject('CompanyService',(int)$service->id()):[];
$partnerIds=json_decode((string)(Setting::first(['key'=>'branding.partner_logo_ids'])?->getAttribute('value')??'[]'),true)?:[];
$partnerMap=json_decode((string)(Setting::first(['key'=>'branding.service_partner_map'])?->getAttribute('value')??'{}'),true)?:[];
$partnerIds=array_key_exists((string)$service?->id(),$partnerMap)?(array)$partnerMap[(string)$service?->id()]:$partnerIds;
$partnerLogos=array_values(array_filter(array_map(static fn($id)=>Media::find((int)$id),$partnerIds)));
?>
<?= view('public.partials.breadcrumbs',['items'=>$breadcrumbs??[]]) ?>
<main class="pac-install-page">
  <section class="pac-hero"><?php if($hero): ?><img src="<?= e($hero) ?>" alt="Installation de pompe à chaleur" fetchpriority="high"><?php endif; ?><div class="pac-hero-shade"></div><div class="pac-hero-copy"><span class="ph-eyebrow">Pompe à chaleur · <?= e($city) ?></span><h1>Installation de pompe à chaleur</h1><p>Chauffez votre maison plus efficacement avec une solution dimensionnée pour votre logement, vos habitudes et votre confort.</p><div><a class="pac-btn accent" href="#demande-devis">Demander mon devis gratuit</a><?php if($phone): ?><a class="pac-btn light" href="tel:<?= e($phoneHref) ?>">Appeler — <?= e($phone) ?></a><?php endif; ?></div></div></section>

  <section class="pac-section pac-services"><span class="ph-eyebrow blue">Services proposés</span><h2>Votre pompe à chaleur, de l’étude au suivi</h2><p class="pac-lead">Choisissez la prestation qui correspond à votre projet. Notre équipe vous accompagne avant, pendant et après l’installation.</p><div class="pac-service-grid"><?php foreach($related as $item):$img=$mediaUrl($item->getAttribute('image_media_id'))?:$hero; ?><a href="/<?= e($item->getAttribute('slug')) ?>"><?php if($img): ?><img src="<?= e($img) ?>" alt="<?= e($item->getAttribute('public_name')) ?>" loading="lazy"><?php endif; ?><div><h3><?= e($item->getAttribute('public_name')) ?></h3><p><?= str_contains((string)$item->getAttribute('slug'),'entretien')?'Préservez les performances et la durée de vie de votre pompe à chaleur grâce à un suivi régulier.':'Découvrez une solution de chauffage adaptée à la configuration de votre logement.' ?></p><span>Découvrir le service →</span></div></a><?php endforeach; ?><a href="#demande-devis"><?php if($hero): ?><img src="<?= e($hero) ?>" alt="Étude de pompe à chaleur" loading="lazy"><?php endif; ?><div><h3>Étude et dimensionnement</h3><p>Nous analysons votre logement et vos besoins pour vous orienter vers une puissance et une technologie adaptées.</p><span>Demander une étude →</span></div></a></div></section>

  <section class="pac-about"><div class="pac-about-inner"><div><span class="ph-eyebrow blue">À propos de l’installation</span><h2>Une pompe à chaleur adaptée à votre maison</h2><p>Une installation performante commence par un dimensionnement juste. Nous étudions la surface, l’isolation, les équipements existants et vos habitudes afin de vous proposer une pompe à chaleur cohérente avec votre logement.</p><p>Notre équipe prend en charge la pose, les raccordements, les réglages et la mise en service. À la fin de l’intervention, nous vous expliquons simplement le fonctionnement de votre nouvel équipement.</p></div><div class="pac-benefits"><article><b>01</b><h3>Étude personnalisée</h3><p>Analyse du logement et de votre besoin réel.</p></article><article><b>02</b><h3>Pose soignée</h3><p>Installation réalisée dans le respect de votre habitation.</p></article><article><b>03</b><h3>Mise en service</h3><p>Réglages, vérifications et prise en main avec vous.</p></article><article><b>04</b><h3>Suivi & entretien</h3><p>Une équipe disponible après votre installation.</p></article></div></div></section>

  <?= view('public.partials.marketing_sections',['company'=>$company,'relatedServices'=>[],'projects'=>$projects,'faqs'=>$faqs,'partnerLogos'=>$partnerLogos,'currentService'=>$service]) ?>
</main>
