<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Migrator;
use App\Models\BusinessCategory;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyService;
use App\Models\Department;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use App\Repositories\SettingsRepository;
use App\Services\Geography\GeoGouvFrProvider;
use Database\Seeders\BusinessCategoriesSeeder;
use Database\Seeders\RegionsDepartmentsSeeder;

require_once dirname(__DIR__) . '/bootstrap.php';

Database::configure(config('database'));
(new Migrator(Database::instance(), database_path('migrations')))->run();
BusinessCategoriesSeeder::run();
RegionsDepartmentsSeeder::run(new GeoGouvFrProvider());

$adminPassword = (string) getenv('MIGRATION_ADMIN_PASSWORD');
if ($adminPassword !== '' && User::first(['role' => 'super_admin']) === null) {
    User::create([
        'first_name' => 'Administrateur',
        'last_name' => 'JC TOITURES',
        'email' => 'contact@jc-toitures95.fr',
        'password_hash' => password_hash($adminPassword, config('security.password_algo')),
        'role' => 'super_admin',
        'is_active' => true,
    ]);
}

$category = BusinessCategory::first(['slug' => 'couvreur']);
$company = Company::current() ?? new Company();
$company->fill([
    'business_category_id' => $category?->id(),
    'trade_name' => 'JC TOITURES',
    'legal_name' => 'JC TOITURES',
    'slogan' => 'Expert en rénovation et entretien de votre habitat en Île-de-France',
    'short_description' => 'Toiture, ravalement, nettoyage, démoussage, hydrofuge et gouttières dans le Val-d’Oise. Devis gratuit.',
    'long_description' => 'JC TOITURES accompagne particuliers et professionnels pour protéger et embellir leur habitat : couverture, entretien de toiture, recherche de fuite, gouttières, ravalement et nettoyage des extérieurs.',
    'phone' => '06 27 29 69 06',
    'whatsapp' => '06 27 29 69 06',
    'public_email' => 'contact@jc-toitures95.fr',
    'leads_email' => 'contact@jc-toitures95.fr',
    'address' => '17 allée des Éguerets',
    'postal_code' => '95280',
    'city' => 'Jouy-le-Moutier',
    'department' => 'Val-d’Oise (95)',
    'region' => 'Île-de-France',
    'certifications' => [],
    'opening_hours' => ['lundi'=>'08:00-19:00','mardi'=>'08:00-19:00','mercredi'=>'08:00-19:00','jeudi'=>'08:00-19:00','vendredi'=>'08:00-19:00','samedi'=>'08:00-18:00'],
    'service_radius_km' => 40,
    'offers_emergency' => true,
    'offers_free_quote' => true,
    'primary_color' => '#182f4c',
    'secondary_color' => '#315b7d',
    'accent_color' => '#d6942f',
    'button_style' => 'rounded',
    'font_primary' => 'Manrope',
    'font_secondary' => 'Public Sans',
    'theme_style' => 'artisanal',
    'editorial_presentation' => 'Entreprise locale spécialisée dans la toiture, le ravalement et l’entretien des extérieurs dans le Val-d’Oise.',
    'editorial_values' => 'Intervention soignée, écoute, transparence et solutions adaptées à l’état du bâtiment.',
    'editorial_work_method' => 'Demande de devis, étude du projet, planification puis réalisation et contrôle des travaux.',
    'editorial_guarantees' => 'Assurance responsabilité civile professionnelle et assurance décennale indiquées par l’entreprise.',
    'editorial_client_types' => 'Particuliers et professionnels.',
    'editorial_achievements' => 'Nettoyage de toiture, nettoyage de dalle et terrasse, travaux de rénovation avant/après.',
    'editorial_priority_areas' => 'Jouy-le-Moutier, Cergy, Pontoise et Val-d’Oise (95).',
]);
$company->save();

$services = [
    ['Nettoyage de toiture','nettoyage-toiture','Élimination de la mousse et des débris pour une toiture propre et durable.'],
    ['Démoussage toiture','demoussage-toiture','Traitement des mousses et lichens afin de préserver durablement la couverture.'],
    ['Hydrofuge toiture','hydrofuge-toiture','Traitement hydrofuge pour protéger la toiture contre l’humidité et limiter la réapparition des mousses.'],
    ['Couverture','couverture','Pose, réparation et rénovation de couvertures avec une attention particulière portée à l’étanchéité.'],
    ['Peinture et ravalement','peinture-ravalement','Préparation, protection et embellissement des façades.'],
    ['Réparation et pose de gouttière','reparation-pose-gouttiere','Réparation, remplacement et pose de gouttières pour une bonne évacuation des eaux pluviales.'],
    ['Nettoyage dallage, muret, façade et pignon','nettoyage-dallage-muret-facade-pignon','Nettoyage adapté des dallages, murets, façades et pignons.'],
    ['Recherche de fuite toiture','recherche-fuite-toiture','Recherche de l’origine des infiltrations et réparation ciblée de la toiture.'],
    ['Réparation de faîtière','reparation-faitiere','Réparation du faîtage pour restaurer l’étanchéité de la toiture.'],
];
foreach ($services as $sort => [$name, $slug, $description]) {
    $catalog = Service::first(['slug' => $slug]);
    $service = CompanyService::first(['company_id'=>$company->id(),'slug'=>$slug]) ?? new CompanyService();
    $service->fill(['company_id'=>$company->id(),'service_id'=>$catalog?->id(),'public_name'=>$name,'slug'=>$slug,'description'=>$description,'content_mode'=>'manual','manual_content'=>$description,'is_custom'=>$catalog===null,'show_in_menu'=>true,'is_featured'=>$sort<6,'is_emergency'=>$slug==='recherche-fuite-toiture','show_starting_price'=>false,'sort_order'=>$sort+1,'meta_title'=>$name.' à Jouy-le-Moutier | JC TOITURES','meta_description'=>$description,'is_active'=>true]);
    $service->save();
    $page = Page::findBySlug($slug) ?? new Page();
    $page->fill(['type'=>'service','company_service_id'=>$service->id(),'slug'=>$slug,'title'=>$name,'h1'=>$name.' à Jouy-le-Moutier et dans le Val-d’Oise','meta_title'=>$name.' | JC TOITURES','meta_description'=>$description,'schema_type'=>'Service','status'=>'published','indexable'=>true,'content_is_placeholder'=>false,'published_at'=>date('Y-m-d H:i:s')]);
    $page->save();
    foreach (PageBlock::where(['page_id'=>$page->id()]) as $old) $old->delete();
    PageBlock::create(['page_id'=>$page->id(),'type'=>'text','position'=>0,'data'=>['heading'=>'Une intervention adaptée à votre toiture','content'=>$description.' JC TOITURES étudie votre besoin et l’état du support avant de proposer une solution claire dans un devis gratuit.'],'is_active'=>true]);
    PageBlock::create(['page_id'=>$page->id(),'type'=>'cta','position'=>1,'data'=>['title'=>'Demandez votre devis gratuit','text'=>'Présentez votre projet à JC TOITURES pour être rappelé rapidement.'],'is_active'=>true]);
}

foreach ([['Jouy-le-Moutier','95280','95323',true],['Cergy','95000','95127',false],['Pontoise','95300','95500',false]] as [$name,$postal,$insee,$primary]) {
    $dept = Department::first(['code'=>'95']);
    $city = City::first(['insee_code'=>$insee]) ?? City::create(['department_id'=>$dept?->id(),'name'=>$name,'slug'=>\App\Support\Str::slug($name),'postal_code'=>$postal,'insee_code'=>$insee]);
    $location = CompanyLocation::first(['company_id'=>$company->id(),'city_id'=>$city->id()]) ?? new CompanyLocation();
    $location->fill(['company_id'=>$company->id(),'city_id'=>$city->id(),'is_primary'=>$primary,'is_active'=>true,'seo_priority'=>$primary?10:7]);
    $location->save();
}

function importRemoteMedia(string $url, string $relative, string $alt, string $type = 'gallery'): ?Media {
    $target = public_path($relative);
    if (!is_dir(dirname($target))) mkdir(dirname($target), 0775, true);
    if (!is_file($target)) {
        $data = @file_get_contents($url);
        if ($data === false || strlen($data) < 100) return null;
        file_put_contents($target, $data);
    }
    [$width,$height] = getimagesize($target) ?: [null,null];
    return Media::first(['url'=>'/'.$relative]) ?? Media::create(['disk_path'=>$relative,'url'=>'/'.$relative,'mime_type'=>mime_content_type($target) ?: 'image/jpeg','size_bytes'=>filesize($target) ?: 0,'width'=>$width,'height'=>$height,'alt_text'=>$alt,'type'=>$type]);
}

$logo = importRemoteMedia('https://www.jc-toitures95.fr/logo/logo.png','uploads/branding/logo.png','Logo JC TOITURES','logo');
$about = importRemoteMedia('https://www.jc-toitures95.fr/uploads/homepage/about-1770202528.jpeg','uploads/homepage/a-propos.jpeg','JC TOITURES à Jouy-le-Moutier','hero');
if ($logo || $about) {
    $company->fill(['logo_main_media_id'=>$logo?->id(),'logo_light_media_id'=>$logo?->id(),'logo_dark_media_id'=>$logo?->id(),'hero_media_id'=>$about?->id(),'og_media_id'=>$about?->id() ?? $logo?->id()]);
    $company->save();
}

$projectDefinitions = [
    ['Nettoyage de dalle et terrasse','portfolio-1771404792-6351.webp','Nettoyage extérieur'],
    ['Nettoyage de toiture','portfolio-1770204142-7201.jpeg','Toiture'],
    ['Travaux de rénovation avant/après','portfolio-1770204062-4690.jpeg','Rénovation'],
];
foreach ($projectDefinitions as $sort => [$title,$filename,$categoryName]) {
    $media = importRemoteMedia('https://www.jc-toitures95.fr/uploads/portfolio/'.$filename,'uploads/realisations/'.$filename,$title.' par JC TOITURES');
    if (!$media || Project::first(['title'=>$title])) continue;
    Project::create(['title'=>$title,'description'=>'Réalisation présentée sur le précédent site de JC TOITURES.','category'=>$categoryName,'after_media_id'=>$media->id(),'alt_text'=>$title.' par JC TOITURES','is_visible'=>true,'sort_order'=>$sort+1]);
}

$reviews = [
    ['Marie L.','Jouy-le-Moutier','JC TOITURES a rénové notre toiture et installé de nouvelles gouttières. L’équipe est professionnelle, ponctuelle et très minutieuse. Le résultat est impeccable, je recommande vivement !'],
    ['Karim D.','Cergy','Travail rapide et soigné pour le démoussage et le nettoyage de notre toit. Les artisans ont été très disponibles pour répondre à toutes nos questions. Service de qualité et sérieux !'],
    ['Sophie R.','Pontoise','Nous avons fait appel à JC TOITURES pour la réparation de notre faîtière et la pose d’un traitement hydrofuge. Très satisfait du résultat et de la propreté du chantier. Équipe professionnelle et sympathique !'],
];
foreach ($reviews as $sort => [$author,$city,$content]) {
    if (!Testimonial::first(['author_name'=>$author])) Testimonial::create(['author_name'=>$author,'author_city'=>$city,'content'=>$content,'rating'=>5,'reviewed_at'=>'2026-02-04','source'=>'manual','is_visible'=>true,'sort_order'=>$sort+1]);
}

$home = Page::findBySlug('accueil') ?? new Page();
$home->fill(['type'=>'home','slug'=>'accueil','title'=>'JC TOITURES','h1'=>'JC TOITURES, expert en rénovation et entretien de votre habitat en Île-de-France','meta_title'=>'JC TOITURES – Toiture et ravalement dans le Val-d’Oise','meta_description'=>'JC TOITURES intervient dans le Val-d’Oise pour toiture, ravalement, nettoyage, démoussage et gouttières. Devis gratuit.','schema_type'=>'RoofingContractor','status'=>'published','indexable'=>true,'content_is_placeholder'=>false,'published_at'=>date('Y-m-d H:i:s')]);
$home->save();
foreach (PageBlock::where(['page_id'=>$home->id()]) as $old) $old->delete();

$pages = [
    ['contact','contact','Contact','Parlons de votre projet','form',[]],
    ['realizations','realisations','Nos réalisations','Découvrez quelques-unes de nos réalisations récentes.','projects',[]],
    ['reviews','avis-clients','Nos avis clients','Découvrez ce que nos clients pensent de nos services.','testimonials',[]],
    ['custom','mentions-legales','Mentions légales','Informations légales de JC TOITURES.','text',['heading'=>'Éditeur et contact','content'=>'Le présent site est édité par JC TOITURES, 17 allée des Éguerets, 95280 Jouy-le-Moutier. Téléphone : 06 27 29 69 06. E-mail : contact@jc-toitures95.fr. Le site est hébergé par un prestataire professionnel. Les contenus sont protégés par le droit de la propriété intellectuelle.']],
    ['custom','politique-confidentialite','Politique de confidentialité','Protection de vos données personnelles.','text',['heading'=>'Données personnelles','content'=>'JC TOITURES utilise les informations transmises pour répondre aux demandes de devis et de contact, fournir ses services et respecter ses obligations légales. Vous disposez des droits prévus par le RGPD. Pour les exercer : contact@jc-toitures95.fr.']],
    ['custom','conditions-generales-vente','Conditions générales de vente','Conditions applicables aux prestations de JC TOITURES.','text',['heading'=>'Prestations, devis et garanties','content'=>'Toute prestation fait l’objet d’un devis détaillé et gratuit, valable 30 jours. Les prix sont indiqués en euros TTC et les délais figurent au devis. JC TOITURES indique disposer d’une responsabilité civile professionnelle et d’une assurance décennale. Toute réclamation doit être adressée par écrit dans les 8 jours suivant la réception des travaux.']],
];
foreach ($pages as [$type,$slug,$title,$description,$blockType,$blockData]) {
    $page = Page::findBySlug($slug) ?? new Page();
    $page->fill(['type'=>$type,'slug'=>$slug,'title'=>$title,'h1'=>$title,'meta_title'=>$title.' | JC TOITURES','meta_description'=>$description,'status'=>'published','indexable'=>true,'content_is_placeholder'=>false,'published_at'=>date('Y-m-d H:i:s')]);
    $page->save();
    foreach (PageBlock::where(['page_id'=>$page->id()]) as $old) $old->delete();
    PageBlock::create(['page_id'=>$page->id(),'type'=>$blockType,'position'=>0,'data'=>$blockData,'is_active'=>true]);
}

foreach (['/services'=>'/','/portfolio'=>'/realisations','/reviews'=>'/avis-clients','/legal/mentions'=>'/mentions-legales','/legal/privacy'=>'/politique-confidentialite','/legal/cgv'=>'/conditions-generales-vente'] as $from=>$to) {
    if (!Redirect::first(['from_path'=>$from])) Redirect::create(['from_path'=>$from,'to_path'=>$to,'status_code'=>301,'is_active'=>true]);
}

$settings = new SettingsRepository(Database::instance());
$settings->set('site_installed', true);
$settings->set('business_category_ids', [$category?->id()]);
$settings->set('active_theme', 'professional');
file_put_contents(storage_path('installed.lock'), date(DATE_ATOM));

echo "JC TOITURES configuré : ".count($services)." services, ".count($reviews)." avis et ".count($projectDefinitions)." réalisations.\n";
