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
use App\Models\Service;
use App\Repositories\SettingsRepository;
use Database\Seeders\BusinessCategoriesSeeder;
use Database\Seeders\RegionsDepartmentsSeeder;
use App\Services\Geography\GeoGouvFrProvider;

require_once dirname(__DIR__) . '/bootstrap.php';

Database::configure(config('database'));
(new Migrator(Database::instance(), database_path('migrations')))->run();
BusinessCategoriesSeeder::run();
RegionsDepartmentsSeeder::run(new GeoGouvFrProvider());

$category = BusinessCategory::first(['slug' => 'couvreur']);
$company = Company::current() ?? new Company();
$company->fill([
    'business_category_id' => $category?->id(),
    'trade_name' => 'ETS Guillaume',
    'legal_name' => 'ETS Guillaume',
    'slogan' => 'Façadier & couvreur dans le Jura et le Rhône',
    'short_description' => 'Rénovation, nettoyage et entretien de toiture et de façade, zinguerie, tuiles, rives aluminium, faîtage, Velux et peinture. Devis gratuit dans le Jura (39) et le Rhône (69).',
    'long_description' => 'ETS Guillaume accompagne les particuliers pour rénover, protéger et embellir leur toiture et leur façade. Chaque chantier est étudié selon l’état du support, avec une intervention soignée et un devis gratuit.',
    'phone' => '06 98 67 88 03',
    'whatsapp' => '06 98 67 88 03',
    'public_email' => 'guillaumerodrigue39@gmail.com',
    'leads_email' => 'guillaumerodrigue39@gmail.com',
    'postal_code' => '39120',
    'city' => 'Petit-Noir',
    'department' => 'Jura (39) et Rhône (69)',
    'region' => 'Bourgogne-Franche-Comté et Auvergne-Rhône-Alpes',
    'certifications' => [],
    'opening_hours' => ['lundi'=>'08:00-19:00','mardi'=>'08:00-19:00','mercredi'=>'08:00-19:00','jeudi'=>'08:00-19:00','vendredi'=>'08:00-19:00','samedi'=>'08:00-18:00'],
    'service_radius_km' => 80,
    'offers_emergency' => true,
    'offers_free_quote' => true,
    'primary_color' => '#183153',
    'secondary_color' => '#2d5d7b',
    'accent_color' => '#d9772a',
    'button_style' => 'rounded',
    'font_primary' => 'Manrope',
    'font_secondary' => 'Public Sans',
    'theme_style' => 'artisanal',
    'editorial_presentation' => 'Entreprise locale spécialisée dans les travaux de toiture, de façade et de zinguerie, ETS Guillaume intervient auprès des particuliers dans le Jura et le Rhône.',
    'editorial_values' => 'Écoute, clarté du devis, soin du chantier et solutions adaptées à l’état réel du bâtiment.',
    'editorial_work_method' => 'Échange sur le besoin, diagnostic du support, devis gratuit, préparation et protection du chantier, réalisation puis contrôle des finitions.',
    'editorial_achievements' => 'Nettoyage et rénovation de toitures, remise en peinture de façades, habillage de rives, travaux de zinguerie et nettoyage de dallages.',
    'editorial_priority_areas' => 'Petit-Noir, Dole, Lons-le-Saunier, Jura (39), Lyon et Rhône (69).',
]);
$company->save();

$serviceDefinitions = [
    ['Nettoyage de toiture et façade', 'nettoyage-toiture-facade', 'Nettoyage adapté des toitures et façades pour retirer salissures, mousses et traces sans abîmer le support.'],
    ['Rénovation et remplacement de tuiles', 'renovation-remplacement-tuiles', 'Réfection des zones endommagées et remplacement des tuiles cassées, fissurées ou déplacées.'],
    ['Habillage de rives en aluminium', 'habillage-rives-aluminium', 'Protection durable et finition nette des rives avec un habillage aluminium adapté à la toiture.'],
    ['Réparation de faîtage et pose de Velux', 'reparation-faitage-pose-velux', 'Réparation du faîtage et pose ou remplacement de fenêtres de toit pour préserver l’étanchéité.'],
    ['Peinture et rénovation de façade', 'peinture-renovation-facade', 'Préparation des supports, traitement des défauts et mise en peinture extérieure pour une façade protégée.'],
    ['Zinguerie et gouttières', 'zinguerie-gouttieres', 'Pose et réparation des éléments d’évacuation des eaux pluviales et des finitions de zinguerie.'],
    ['Nettoyage de dalles et extérieurs', 'nettoyage-dalles-exterieurs', 'Nettoyage des dallages, terrasses et surfaces extérieures avec une méthode adaptée au matériau.'],
];

$serviceModels = [];
foreach ($serviceDefinitions as $sort => [$name, $slug, $description]) {
    $catalogService = Service::first(['slug' => $slug]);
    $model = CompanyService::first(['company_id' => $company->id(), 'slug' => $slug]) ?? new CompanyService();
    $model->fill(['company_id'=>$company->id(),'service_id'=>$catalogService?->id(),'public_name'=>$name,'slug'=>$slug,'description'=>$description,'content_mode'=>'manual','manual_content'=>$description,'is_custom'=>$catalogService===null,'show_in_menu'=>true,'is_featured'=>$sort<4,'is_emergency'=>false,'show_starting_price'=>false,'sort_order'=>$sort+1,'meta_title'=>$name.' | ETS Guillaume','meta_description'=>$description,'is_active'=>true]);
    $model->save();
    $serviceModels[$slug] = $model;

    $page = Page::findBySlug($slug) ?? new Page();
    $page->fill(['type'=>'service','company_service_id'=>$model->id(),'slug'=>$slug,'title'=>$name,'h1'=>$name.' dans le Jura (39) et le Rhône (69)','meta_title'=>$name.' – ETS Guillaume','meta_description'=>$description,'schema_type'=>'Service','status'=>'published','indexable'=>true,'content_is_placeholder'=>false,'published_at'=>date('Y-m-d H:i:s')]);
    $page->save();
    foreach (PageBlock::where(['page_id'=>$page->id()]) as $old) $old->delete();
    PageBlock::create(['page_id'=>$page->id(),'type'=>'text','position'=>0,'data'=>['heading'=>'Un travail adapté à votre bâtiment','content'=>$description.' ETS Guillaume commence par observer l’état du support afin de proposer une intervention cohérente, expliquée clairement dans un devis gratuit.'],'is_active'=>true]);
    PageBlock::create(['page_id'=>$page->id(),'type'=>'cta','position'=>1,'data'=>['title'=>'Demandez votre devis gratuit','text'=>'Expliquez votre projet à ETS Guillaume pour être rappelé rapidement.'],'is_active'=>true]);
}

$cities = [
    ['Petit-Noir','39120','39415','39'],['Neublans-Abergement','39120','39385','39'],['Longwy-sur-le-Doubs','39120','39299','39'],['Fretterans','71270','71208','71'],['Les Hays','39120','39268','39'],['Chemin','39120','39134','39'],['Annoire','39120','39011','39'],['Asnans-Beauvoisin','39120','39021','39'],['Authumes','71270','71013','71'],['Chaussin','39120','39128','39'],['Peseux','39120','39412','39'],['Tavaux','39500','39526','39'],['Dole','39100','39198','39'],['Lons-le-Saunier','39000','39300','39'],
    ['Lyon','69000','69123','69'],['Villeurbanne','69100','69266','69'],['Vénissieux','69200','69259','69'],['Caluire-et-Cuire','69300','69034','69'],['Bron','69500','69029','69'],['Saint-Priest','69800','69290','69'],['Vaulx-en-Velin','69120','69256','69'],['Meyzieu','69330','69282','69'],['Oullins-Pierre-Bénite','69310','69149','69'],['Tassin-la-Demi-Lune','69160','69244','69'],['Écully','69130','69081','69'],['Givors','69700','69091','69'],['Villefranche-sur-Saône','69400','69264','69']
];
foreach ($cities as $row) {
    [$name,$postal,$insee,$deptCode] = $row;
    $dept = Department::first(['code'=>$deptCode]);
    $city = City::first(['insee_code'=>$insee]) ?? City::create(['department_id'=>$dept?->id(),'name'=>$name,'slug'=>\App\Support\Str::slug($name),'postal_code'=>$postal,'insee_code'=>$insee]);
    $link = CompanyLocation::first(['company_id'=>$company->id(),'city_id'=>$city->id()]) ?? new CompanyLocation();
    $link->fill(['company_id'=>$company->id(),'city_id'=>$city->id(),'is_primary'=>$insee==='39415','is_active'=>true,'seo_priority'=>$insee==='39415'?10:5]);
    $link->save();
}

$mediaByFile = [];
$projectDir = public_path('uploads/realisations');
if (is_dir($projectDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile() || !preg_match('/\.(?:jpe?g|png|webp)$/i', $file->getFilename())) continue;
        $path = $file->getPathname();
        $key = ltrim(str_replace($projectDir, '', $path), DIRECTORY_SEPARATOR);
        $relative = 'uploads/realisations/' . str_replace(DIRECTORY_SEPARATOR, '/', $key);
        $existing = Media::first(['url'=>'/'.$relative]);
        [$width,$height] = getimagesize($path) ?: [null,null];
        $media = $existing ?? Media::create(['disk_path'=>$relative,'url'=>'/'.$relative,'mime_type'=>mime_content_type($path) ?: 'image/jpeg','size_bytes'=>filesize($path) ?: 0,'width'=>$width,'height'=>$height,'alt_text'=>'Réalisation ETS Guillaume','type'=>'gallery']);
        $mediaByFile[$key] = $media;
    }
}

$pairs = [
    ['Rénovation de toiture','toiture /2avant.jpeg','toiture /2apres.jpeg','Toiture'],
    ['Nettoyage de toiture','toiture /netoyage avant 8.jpeg','toiture /netoiyage apres 8.jpeg','Toiture'],
    ['Rénovation de façade','FACADE/2 avant .jpeg','FACADE/2 apres.jpeg','Façade'],
    ['Peinture de façade','FACADE/1 avant.jpeg','FACADE/1 apres.jpeg','Façade'],
    ['Nettoyage de dalle','Netoyage dalle/1 avant.jpeg','Netoyage dalle/1 apres.jpeg','Extérieurs'],
    ['Travaux de zinguerie','ZiNGUERIE/1avant.jpeg','ZiNGUERIE/1 apres.jpeg','Zinguerie'],
];
foreach ($pairs as $sort => [$title,$beforeKey,$afterKey,$categoryName]) {
    $before = $mediaByFile[$beforeKey] ?? null; $after = $mediaByFile[$afterKey] ?? null;
    if (!$before && !$after) continue;
    Project::create(['title'=>$title,'description'=>'Aperçu avant/après d’un chantier réalisé par ETS Guillaume.','category'=>$categoryName,'before_media_id'=>$before?->id(),'after_media_id'=>$after?->id(),'alt_text'=>$title.' par ETS Guillaume','is_visible'=>true,'sort_order'=>$sort+1]);
}

$hero = reset($mediaByFile) ?: null;
if ($hero) { $company->fill(['hero_media_id'=>$hero->id(),'og_media_id'=>$hero->id()]); $company->save(); }

$home = Page::findBySlug('accueil') ?? new Page();
$home->fill(['type'=>'home','slug'=>'accueil','title'=>'ETS Guillaume','h1'=>'Façadier & couvreur dans le Jura (39) et le Rhône (69)','meta_title'=>'ETS Guillaume – Façadier & couvreur Jura (39) et Rhône (69)','meta_description'=>'ETS Guillaume réalise vos travaux de toiture, façade, zinguerie et nettoyage dans le Jura (39) et le Rhône (69). Devis gratuit.','schema_type'=>'RoofingContractor','status'=>'published','indexable'=>true,'content_is_placeholder'=>false,'published_at'=>date('Y-m-d H:i:s')]);
$home->save();
foreach (PageBlock::where(['page_id'=>$home->id()]) as $old) $old->delete();

$structural = [['custom','a-propos','À propos d’ETS Guillaume'],['contact','contact','Contact'],['realizations','realisations','Nos réalisations'],['zones','zones-intervention','Zones d’intervention']];
foreach ($structural as [$type,$slug,$title]) {
    $page = Page::findBySlug($slug) ?? new Page();
    $page->fill(['type'=>$type,'slug'=>$slug,'title'=>$title,'h1'=>$title,'meta_title'=>$title.' – ETS Guillaume','meta_description'=>'ETS Guillaume intervient dans le Jura (39) et le Rhône (69).','status'=>'published','indexable'=>true,'content_is_placeholder'=>false,'published_at'=>date('Y-m-d H:i:s')]);
    $page->save();
    foreach (PageBlock::where(['page_id'=>$page->id()]) as $old) $old->delete();
    $blockType = match ($type) { 'custom' => 'text', 'contact' => 'form', 'realizations' => 'projects', default => 'service_area' };
    $blockData = $type === 'custom' ? ['heading'=>'Une entreprise locale à votre écoute','content'=>'ETS Guillaume intervient pour la rénovation, la protection et l’entretien des toitures, façades et extérieurs. Chaque demande commence par un échange et un diagnostic du support afin de proposer une solution adaptée et un devis gratuit.'] : [];
    PageBlock::create(['page_id'=>$page->id(),'type'=>$blockType,'position'=>0,'data'=>$blockData,'is_active'=>true]);
}

$settings = new SettingsRepository(Database::instance());
$settings->set('site_installed', true);
$settings->set('business_category_ids', [$category?->id()]);
$settings->set('active_theme', 'professional');
file_put_contents(storage_path('installed.lock'), date(DATE_ATOM));

echo "ETS Guillaume configured: ".count($serviceModels)." services, ".count($cities)." cities, ".count($mediaByFile)." photos.\n";
