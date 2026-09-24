<?php

declare(strict_types=1);

/**
 * Configuration du site HART TOITURE — couvreur à Saint-Denis, La Réunion (974).
 * Données et photos reprises de l’ancien site www.toiture-reunion-hart.re.
 *
 * Idempotent : peut être relancé sans créer de doublons.
 * Usage : HART_ADMIN_PASSWORD='...' php scripts/configure-hart.php
 */

use App\Core\Cache;
use App\Core\Database;
use App\Core\Migrator;
use App\Models\BusinessCategory;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyLocation;
use App\Models\CompanyService;
use App\Models\Department;
use App\Models\Faq;
use App\Models\LocalPage;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\User;
use App\Repositories\SettingsRepository;
use App\Services\Geography\GeoGouvFrProvider;
use App\Support\Str;
use Database\Seeders\BusinessCategoriesSeeder;
use Database\Seeders\RegionsDepartmentsSeeder;

require_once dirname(__DIR__) . '/bootstrap.php';

Database::configure(config('database'));
(new Migrator(Database::instance(), database_path('migrations')))->run();
BusinessCategoriesSeeder::run();
RegionsDepartmentsSeeder::run(new GeoGouvFrProvider());


const HART_NAME = 'HART TOITURE';
const HART_PHONE = '0693 83 80 54';
const HART_EMAIL = 'enthart@hotmail.fr';
const HART_FACEBOOK = 'https://www.facebook.com/profile.php?id=61556104442931';

$adminPassword = (string) getenv('HART_ADMIN_PASSWORD');
$adminEmail = (string) (getenv('HART_ADMIN_EMAIL') ?: HART_EMAIL);
if ($adminPassword !== '' && User::first(['role' => 'super_admin']) === null) {
    User::create([
        'first_name' => 'Guy',
        'last_name' => 'HART',
        'email' => $adminEmail,
        'password_hash' => password_hash($adminPassword, config('security.password_algo')),
        'role' => 'super_admin',
        'is_active' => true,
    ]);
    echo "Compte administrateur créé : {$adminEmail}\n";
}

/* ------------------------------------------------------------------ */
/* Médias (fichiers versionnés dans public/assets)                     */
/* ------------------------------------------------------------------ */
function hartAsset(string $relative, string $alt, string $type): Media
{
    $url = '/' . ltrim($relative, '/');
    $path = public_path(ltrim($relative, '/'));
    $media = Media::first(['url' => $url]) ?? new Media();
    [$width, $height] = @getimagesize($path) ?: [null, null];
    $mime = str_ends_with($path, '.svg') ? 'image/svg+xml' : (mime_content_type($path) ?: 'image/png');
    $media->fill([
        'disk_path' => ltrim($relative, '/'),
        'url' => $url,
        'mime_type' => $mime,
        'size_bytes' => is_file($path) ? filesize($path) : 0,
        'width' => $width,
        'height' => $height,
        'alt_text' => $alt,
        'type' => $type,
    ]);
    $media->save();

    return $media;
}

/** Photo réelle de chantier HART. */
function hartPhoto(string $name, string $alt, string $type = 'realization'): Media
{
    return hartAsset('assets/images/hart/photos/' . $name . '.webp', $alt, $type);
}

$logoHorizontal = hartAsset('assets/images/hart/logo-horizontal.png', 'Logo HART — Couverture, rénovation, étanchéité', 'logo');
$logoFull = hartAsset('assets/images/hart/logo-hart.png', 'Logo HART TOITURE, couvreur à La Réunion', 'logo');
$favicon = hartAsset('assets/images/hart/icon-512.png', 'Icône HART', 'favicon');
$hero = hartPhoto('toiture-tole-rouge-renovee-la-reunion', 'Toiture en tôle rouge rénovée par HART TOITURE, vue sur l’océan à La Réunion', 'hero');

/* ------------------------------------------------------------------ */
/* Entreprise (informations issues du site toiture-reunion-hart.re)    */
/* ------------------------------------------------------------------ */
$category = BusinessCategory::first(['slug' => 'couvreur']);
$company = Company::current() ?? new Company();
$company->fill([
    'business_category_id' => $category?->id(),
    'trade_name' => HART_NAME,
    'legal_name' => 'HART TOITURE (entrepreneur individuel)',
    'slogan' => 'Couverture · Rénovation · Étanchéité',
    'short_description' => 'Couvreur à Saint-Denis de La Réunion : recherche de fuite et urgence 24 h/24, réparation et rénovation de toiture, traitement antirouille, nettoyage, peinture et étanchéité. Déplacement et devis gratuits.',
    'long_description' => "HART TOITURE protège les toitures de La Réunion en s’adaptant aux spécificités climatiques de l’île.\nFondée par M. Guy Hart, installé dans l’île depuis l’enfance et fort de trente ans d’expérience, l’entreprise a adapté des techniques éprouvées aux contraintes tropicales : soleil, fortes pluies, air salin et cyclones.\nAux côtés de Fabien, son collaborateur, il forme une équipe soudée dédiée à la rénovation et à l’étanchéité des toitures en tôle comme en dalle de béton. HART TOITURE intervient partout dans l’île, sans accueil en agence, pour rester au plus près de vos besoins.",
    'phone' => HART_PHONE,
    'whatsapp' => HART_PHONE,
    'public_email' => HART_EMAIL,
    'leads_email' => HART_EMAIL,
    'address' => '1 route du Moufia',
    'postal_code' => '97490',
    'city' => 'Saint-Denis',
    'department' => 'La Réunion (974)',
    'region' => 'La Réunion',
    'siret' => '91215929000010',
    'legal_info' => 'HART TOITURE — Entrepreneur individuel — RCS Saint-Denis-de-la-Réunion 912 159 290 — SIRET 912 159 290 00010 — TVA FR18912159290',
    'certifications' => [],
    'social_links' => ['facebook' => HART_FACEBOOK],
    'opening_hours' => ['lundi' => '07:00-19:00', 'mardi' => '07:00-19:00', 'mercredi' => '07:00-19:00', 'jeudi' => '07:00-19:00', 'vendredi' => '07:00-19:00', 'samedi' => '07:00-19:00', 'dimanche' => '07:00-19:00'],
    'service_radius_km' => null,
    'offers_emergency' => true,
    'offers_free_quote' => true,
    'primary_color' => '#E51720',
    'secondary_color' => '#18243A',
    'accent_color' => '#EEE414',
    'button_style' => 'rounded',
    'font_primary' => 'Montserrat',
    'font_secondary' => 'Poppins',
    'theme_style' => 'artisanal',
    'logo_main_media_id' => $logoHorizontal->id(),
    'logo_light_media_id' => $logoHorizontal->id(),
    'logo_dark_media_id' => $logoHorizontal->id(),
    'favicon_media_id' => $favicon->id(),
    'hero_media_id' => $hero->id(),
    'og_media_id' => $hero->id(),
    'editorial_presentation' => 'Entreprise de couverture basée à Saint-Denis : rénovation, étanchéité et protection des toitures en tôle et en béton partout à La Réunion.',
    'editorial_history' => 'Fondée par M. Guy Hart, installé à La Réunion depuis l’enfance, trente ans d’expérience dans la toiture. Équipe : Guy Hart et Fabien, collaborateur.',
    'editorial_values' => 'Savoir-faire, réactivité, travail soigné, relation de confiance.',
    'editorial_work_method' => 'Déplacement et diagnostic gratuits, explication claire de l’origine du problème, devis gratuit, réalisation soignée des travaux.',
    'editorial_brands_used' => 'Zolpan, Sika.',
    'editorial_client_types' => 'Particuliers, professionnels et collectivités.',
    'editorial_commitments' => 'Interventions d’urgence 24 h/24, 7 j/7. Déplacements et devis gratuits.',
    'editorial_priority_areas' => 'Saint-Denis et toute l’île de La Réunion.',
]);
$company->save();
$companyId = (int) $company->id();

/* ------------------------------------------------------------------ */
/* Services (mêmes URL que l’ancien site pour conserver le SEO)        */
/* ------------------------------------------------------------------ */
$services = [
    [
        'slug' => 'recherche-fuite-et-urgence', 'name' => 'Recherche de fuite et urgence', 'photo' => 'mise-hors-d-eau-bache-urgence',
        'photo_alt' => 'Mise hors d’eau d’urgence par bâchage sur une toiture à La Réunion',
        'description' => 'Détection rapide des fuites sur toitures en tôle et en dalle béton, mise en sécurité immédiate et interventions d’urgence 24 h/24.',
        'intro' => 'Une fuite d’eau provoque vite des dégâts importants. Nous commençons par une inspection visuelle pour repérer les infiltrations sur votre toiture en tôle ou en dalle de béton, puis nous vous expliquons clairement l’origine du problème.',
        'points' => ['Inspection visuelle des infiltrations, joints et raccords de tôle', 'Joint détérioré : remplacement de la visserie ou résine d’étanchéité avant mise en peinture', 'Raccord de tôle fragile : feuille d’étanchéité, première couche de résine, toile puis seconde couche', 'Intervention immédiate 24 h/24 pour sécuriser l’habitation et stopper temporairement la fuite', 'Diagnostic et conseil sur la réparation la plus durable'],
        'faq' => [['Intervenez-vous la nuit ou le week-end ?', 'Oui. HART TOITURE est disponible 24 h/24 et 7 j/7 pour les urgences : nous nous déplaçons rapidement pour sécuriser votre habitation.'], ['Quelles sont les fuites les plus fréquentes à La Réunion ?', 'La détérioration d’un joint et la faiblesse d’un raccord de tôle. Chacune demande une technique d’étanchement adaptée que nous vous expliquons avant d’intervenir.']],
        'emergency' => true,
    ],
    [
        'slug' => 'reparation-renovation', 'name' => 'Réparation et rénovation', 'photo' => 'reparation-toiture-apres',
        'photo_alt' => 'Toiture en tôle réparée et rénovée par HART TOITURE',
        'description' => 'Réparation et rénovation de toitures en tôle et de toits-terrasses béton : fissures, trous, tôles oxydées, fixations corrodées, rénovation complète.',
        'intro' => 'Fissures, trous et plaques oxydées fragilisent votre toiture. Après une inspection minutieuse, nous réparons les zones endommagées, remplaçons les éléments défectueux et, si nécessaire, rénovons entièrement votre toit pour l’adapter au climat tropical.',
        'points' => ['Réparation des fissures avec des produits d’étanchement adaptés', 'Comblement des trous avec des matériaux de couverture appropriés', 'Traitement antirouille et peinture de protection des plaques oxydées', 'Remplacement des tôles abîmées, fixations corrodées remplacées par des vis inox, joints refaits au mastic', 'Rénovation complète : nettoyage, reprise de charpente si nécessaire, isolation, nouvelle couverture'],
        'faq' => [['Faut-il changer toute la toiture ?', 'Pas forcément. Après inspection, nous privilégions la réparation ciblée lorsque la couverture est encore saine, et proposons une rénovation complète seulement si elle est nécessaire.'], ['Rénovez-vous aussi les toits-terrasses en béton ?', 'Oui, nous intervenons sur les toitures en tôle comme sur les toitures-terrasses en dalle de béton.']],
        'emergency' => false,
    ],
    [
        'slug' => 'traitement-anti-rouille', 'name' => 'Traitement antirouille', 'photo' => 'toiture-tole-rouillee',
        'photo_alt' => 'Toiture en tôle rouillée avant traitement antirouille à La Réunion',
        'description' => 'Traitement antirouille des toitures métalliques : brossage, décapage, traitement antioxydant et peinture spéciale Zolpan ou Sika.',
        'intro' => 'La rouille peut gravement endommager une toiture métallique. Nous éliminons la corrosion par un nettoyage préparatoire rigoureux, puis appliquons un traitement antioxydant et une peinture spéciale qui forment une barrière protectrice durable.',
        'points' => ['Inspection visuelle des zones affectées', 'Brossage mécanique des dépôts de rouille', 'Décapage chimique des surfaces difficiles', 'Traitement antirouille et peinture spéciale de marques reconnues (Zolpan, Sika)', 'Conseils d’entretien pour éviter le retour de la rouille'],
        'faq' => [['À quelle fréquence entretenir une toiture en tôle ?', 'Contrôlez votre toiture après chaque saison des pluies, dégagez débris et gouttières, et prévoyez une couche de peinture protectrice environ tous les deux ans.'], ['Quels produits utilisez-vous ?', 'Nous utilisons des produits de marques reconnues comme Zolpan et Sika, adaptés aux conditions tropicales de La Réunion.']],
        'emergency' => false,
    ],
    [
        'slug' => 'nettoyage-peinture', 'name' => 'Nettoyage et peinture', 'photo' => 'nettoyage-toiture-apres',
        'photo_alt' => 'Toiture en tôle nettoyée et repeinte par HART TOITURE, vue sur l’océan',
        'description' => 'Nettoyage complet de toiture, peinture de protection anti-UV et anti-humidité, préparation à la pose de résine d’étanchéité.',
        'intro' => 'Saletés, mousses et poussières abîment votre toiture. Nous la nettoyons entièrement, puis appliquons une peinture de protection conçue pour résister aux UV et à l’humidité, qui renforce l’étanchéité et redonne une nouvelle jeunesse à votre maison.',
        'points' => ['Nettoyage complet : saletés, mousses et poussières', 'Peinture de protection adaptée aux conditions tropicales', 'Barrière contre l’humidité et les rayons UV', 'Préparation à la résine : nettoyage approfondi, primaire d’accrochage, contrôle de l’uniformité'],
        'faq' => [['Pourquoi nettoyer avant de peindre ?', 'Une surface propre et sèche est indispensable à l’adhérence de la peinture ou de la résine. C’est ce qui garantit la durabilité du résultat.'], ['La peinture protège-t-elle vraiment la toiture ?', 'Oui : une peinture de protection adaptée forme une barrière contre l’humidité et les UV, ce qui prolonge la durée de vie de la toiture.']],
        'emergency' => false,
    ],
    [
        'slug' => 'etancheite', 'name' => 'Étanchéité', 'photo' => 'etancheite-terrasse-resine',
        'photo_alt' => 'Étanchéité de toit-terrasse par résine réalisée par HART TOITURE',
        'description' => 'Étanchéité des toitures en tôle et toits-terrasses béton : résine d’étanchéité, membranes, revêtements protecteurs et systèmes multicouches.',
        'intro' => 'Les signes d’humidité sur une toiture en tôle ou une dalle béton annoncent des infiltrations. Nous repérons les zones poreuses puis mettons en place la solution d’étanchéité adaptée : résine, membrane ou système multicouche.',
        'points' => ['Repérage des zones poreuses : gouttières, joints, fenêtres de toit, évacuations', 'Préparation du support par décapage ou ponçage, primaire d’adhérence', 'Application de résine d’étanchéité sur support propre et sec, entoilage si nécessaire', 'Membranes bitumineuses ou caoutchouc, revêtements protecteurs, pare-vapeur', 'Systèmes multicouches pour une protection renforcée'],
        'faq' => [['Quelle solution pour un toit-terrasse ?', 'Selon l’état du support, nous proposons une résine d’étanchéité entoilée ou une membrane. Le diagnostic gratuit permet de choisir la plus adaptée.'], ['Combien de temps dure le chantier ?', 'Cela dépend de la surface et des temps de séchage de la résine, que nous respectons scrupuleusement. La durée est indiquée dans le devis.']],
        'emergency' => false,
    ],
];

$activeSlugs = [];
$serviceCopyMap = [];
$serviceIds = [];
foreach ($services as $sort => $s) {
    $activeSlugs[] = $s['slug'];
    $image = hartPhoto($s['photo'], $s['photo_alt'], 'service');
    $catalog = Service::first(['slug' => $s['slug']]);
    $service = CompanyService::first(['company_id' => $companyId, 'slug' => $s['slug']]) ?? new CompanyService();
    $service->fill([
        'company_id' => $companyId, 'service_id' => $catalog?->id(), 'public_name' => $s['name'], 'slug' => $s['slug'],
        'description' => $s['description'], 'content_mode' => 'manual', 'manual_content' => $s['intro'],
        'is_custom' => $catalog === null, 'show_in_menu' => true, 'is_featured' => true, 'is_emergency' => $s['emergency'],
        'show_starting_price' => false, 'image_media_id' => $image->id(), 'sort_order' => $sort + 1,
        'meta_title' => $s['name'] . ' à Saint-Denis, La Réunion', 'meta_description' => $s['description'], 'is_active' => true,
    ]);
    $service->save();
    $serviceIds[$s['slug']] = (int) $service->id();

    $serviceCopyMap[(string) $service->id()] = [
        'hero_eyebrow' => 'Saint-Denis · Toute La Réunion',
        'hero_intro' => $s['description'],
        'hero_image' => $image->getAttribute('url'),
        'about_title' => 'Un ouvrage soigné et durable',
        'about_text' => '<p>' . e($s['intro']) . '</p><ul>' . implode('', array_map(static fn ($point) => '<li>' . e($point) . '</li>', $s['points'])) . '</ul><p>Le déplacement et le devis sont gratuits. Nous intervenons pour les particuliers, les professionnels et les collectivités.</p>',
        'related_text' => 'Fuite, rouille, peinture ou étanchéité : découvrez nos autres prestations toiture à La Réunion.',
    ];
    foreach (Faq::forSubject('CompanyService', (int) $service->id()) as $oldFaq) $oldFaq->delete();
    foreach ($s['faq'] as $faqIndex => [$question, $answer]) {
        Faq::create(['faqable_type' => 'CompanyService', 'faqable_id' => $service->id(), 'question' => $question, 'answer' => $answer, 'sort_order' => $faqIndex + 1]);
    }

    $page = Page::findBySlug($s['slug']) ?? new Page();
    $page->fill([
        'type' => 'service', 'company_service_id' => $service->id(), 'slug' => $s['slug'], 'title' => $s['name'],
        'h1' => $s['name'] . ' de toiture à Saint-Denis et à La Réunion', 'meta_title' => $s['name'] . ' de toiture à Saint-Denis (974)',
        'meta_description' => $s['description'] . ' Déplacement et devis gratuits.',
        'schema_type' => 'Service', 'status' => 'published', 'indexable' => true, 'content_is_placeholder' => false,
        'published_at' => $page->getAttribute('published_at') ?: date('Y-m-d H:i:s'),
    ]);
    $page->save();
}
// Désactive et archive tout service qui ne fait pas partie de l’offre réelle.
foreach (CompanyService::where(['company_id' => $companyId]) as $existing) {
    if (!in_array($existing->getAttribute('slug'), $activeSlugs, true)) {
        $existing->fill(['is_active' => false, 'show_in_menu' => false]);
        $existing->save();
        if ($oldPage = Page::findBySlug((string) $existing->getAttribute('slug'))) {
            $oldPage->setAttribute('status', 'archived');
            $oldPage->save();
        }
    }
}

/* ------------------------------------------------------------------ */
/* Réalisations (photos réelles de l’ancien site)                      */
/* ------------------------------------------------------------------ */
$projects = [
    ['Étanchéité d’un toit-terrasse', 'Étanchéité', 'etancheite', 'etancheite-toit-terrasse-avant', 'etancheite-toit-terrasse-apres', 'Reprise complète de l’étanchéité d’un toit-terrasse en dalle béton.'],
    ['Réparation d’une toiture en tôle', 'Réparation et rénovation', 'reparation-renovation', 'reparation-toiture-avant', 'reparation-toiture-apres', 'Réparation des zones endommagées et remise en état de la couverture.'],
    ['Nettoyage et peinture de toiture', 'Nettoyage et peinture', 'nettoyage-peinture', 'nettoyage-toiture-avant', 'nettoyage-toiture-apres', 'Nettoyage complet puis peinture de protection d’une toiture en tôle.'],
    ['Peinture d’une toiture en tôle bleue', 'Nettoyage et peinture', 'nettoyage-peinture', 'peinture-toiture-tole-en-cours', 'toiture-tole-bleue-peinture-saint-denis', 'Application d’une peinture de protection sur une grande toiture en tôle.'],
    ['Rénovation d’une toiture rouge', 'Réparation et rénovation', 'reparation-renovation', null, 'toiture-tole-rouge-renovee-la-reunion', 'Toiture en tôle rénovée, prête à affronter le climat tropical.'],
    ['Toiture de commerce', 'Réparation et rénovation', 'reparation-renovation', null, 'toiture-commerce-tole-rouge', 'Remise en peinture de la couverture en tôle d’un commerce.'],
    ['Étanchéité par résine', 'Étanchéité', 'etancheite', null, 'etancheite-terrasse-resine', 'Application d’une résine d’étanchéité sur un toit-terrasse.'],
    ['Rénovation de toiture de maison', 'Réparation et rénovation', 'reparation-renovation', null, 'toiture-tole-blanche-renovee', 'Rénovation de la couverture en tôle d’une maison.'],
    ['Toiture de bâtiment professionnel', 'Traitement antirouille', 'traitement-anti-rouille', null, 'toiture-industrielle-extracteurs', 'Traitement et protection d’une grande toiture de bâtiment.'],
];
foreach ($projects as $sort => [$title, $categoryName, $serviceSlug, $before, $after, $description]) {
    $afterMedia = hartPhoto($after, $title . ' — HART TOITURE, La Réunion');
    $beforeMedia = $before ? hartPhoto($before, 'Avant travaux — ' . $title) : null;
    $project = Project::first(['title' => $title]) ?? new Project();
    $project->fill([
        'title' => $title, 'description' => $description, 'category' => $categoryName,
        'company_service_id' => $serviceIds[$serviceSlug] ?? null,
        'before_media_id' => $beforeMedia?->id(), 'after_media_id' => $afterMedia->id(),
        'alt_text' => $title . ' par HART TOITURE à La Réunion', 'is_visible' => true, 'sort_order' => $sort + 1,
    ]);
    $project->save();
}
$localPhotos = array_map(static fn ($n) => '/assets/images/hart/photos/' . $n . '.webp', ['toiture-tole-rouge-renovee-la-reunion', 'toiture-tole-bleue-peinture-saint-denis', 'nettoyage-toiture-apres', 'etancheite-toit-terrasse-apres', 'couvreur-hart-sur-toiture-maison', 'reparation-toiture-apres', 'toiture-commerce-tole-rouge', 'couvreur-peinture-toiture-tole']);
foreach (['couvreur-hart-sur-toiture-maison', 'couvreur-peinture-toiture-tole', 'application-peinture-toiture-pistolet', 'couvreur-intervention-rive-toiture', 'infiltration-toit-terrasse', 'nettoyage-haute-pression-toiture'] as $extra) {
    hartPhoto($extra, 'Chantier de toiture HART TOITURE à La Réunion', 'gallery');
}

/* ------------------------------------------------------------------ */
/* Communes de La Réunion + pages locales                              */
/* ------------------------------------------------------------------ */
$zones = [
    'nord' => ['label' => 'le Nord', 'climate' => 'Entre littoral urbanisé et quartiers des hauteurs, le Nord cumule air salin en bord de mer et fortes pluies sur les pentes. Les couvertures y vieillissent vite si les fixations et les faîtages ne sont pas surveillés.'],
    'est' => ['label' => 'l’Est', 'climate' => 'L’Est est la côte au vent de l’île : c’est l’une des zones les plus arrosées au monde. Les toitures doivent y évacuer des volumes d’eau très importants et l’étanchéité des points singuliers est essentielle.'],
    'ouest' => ['label' => 'l’Ouest', 'climate' => 'Sur la côte sous le vent, l’ensoleillement est intense et l’air marin omniprésent. La chaleur dilate les tôles, le sel accélère la corrosion : traitement anticorrosion et fixations adaptées font la différence.'],
    'sud' => ['label' => 'le Sud', 'climate' => 'Le Sud alterne littoral venté, plaines agricoles et quartiers qui montent vers les Hauts. Les toitures y subissent à la fois le soleil, les embruns et des averses soudaines.'],
    'hauts' => ['label' => 'les Hauts', 'climate' => 'Dans les Hauts, fraîcheur, humidité et brouillard sont fréquents. Les toitures y demandent une attention particulière à la condensation, à la ventilation sous toiture et à la bonne évacuation des pluies.'],
];

$communes = [
    ['Saint-Denis', '97400', '97411', 'nord', 'Chef-lieu de l’île, Saint-Denis mêle cases créoles du centre-ville, résidences et quartiers perchés comme La Montagne, Le Brûlé ou Bellepierre.'],
    ['Sainte-Marie', '97438', '97418', 'nord', 'Entre l’aéroport Roland-Garros, les quartiers résidentiels et les hauteurs de La Montagne, Sainte-Marie compte de nombreuses maisons individuelles exposées au vent et aux pluies.'],
    ['Sainte-Suzanne', '97441', '97420', 'nord', 'De Quartier-Français aux hauteurs de Bagatelle, Sainte-Suzanne est une commune en pleine croissance où se côtoient maisons récentes et habitat plus ancien.'],
    ['La Possession', '97419', '97408', 'ouest', 'Porte d’entrée de l’Ouest, La Possession s’étend du littoral jusqu’aux Hauts de Dos d’Âne, avec de nombreux lotissements récents.'],
    ['Le Port', '97420', '97407', 'ouest', 'Ville portuaire et industrielle, Le Port concentre habitations, commerces et bâtiments professionnels directement exposés à l’air marin.'],
    ['Saint-Paul', '97460', '97415', 'ouest', 'Plus vaste commune de l’île, Saint-Paul va du littoral de Saint-Gilles et Boucan Canot jusqu’aux Hauts du Guillaume et de Bellemène.'],
    ['Les Trois-Bassins', '97426', '97423', 'ouest', 'Petite commune étagée de l’Ouest, Trois-Bassins grimpe du bord de mer jusqu’aux hauteurs, avec un habitat surtout individuel.'],
    ['Saint-Leu', '97436', '97413', 'ouest', 'Réputée pour son lagon et ses spots de surf, Saint-Leu s’étend jusqu’aux Hauts comme Le Plate ou Les Colimaçons, entre soleil intense et air salin.'],
    ['Les Avirons', '97425', '97401', 'ouest', 'Commune résidentielle entre mer et montagne, Les Avirons voit se développer de nombreux quartiers de maisons individuelles sur ses pentes.'],
    ['L’Étang-Salé', '97427', '97404', 'ouest', 'Connue pour sa plage de sable noir et sa forêt, L’Étang-Salé réunit villas du littoral et quartiers des Hauts plus frais.'],
    ['Saint-Louis', '97450', '97414', 'sud', 'De la ville basse à La Rivière et aux Makes, Saint-Louis présente des toitures très variées, du littoral aux quartiers d’altitude.'],
    ['Saint-Pierre', '97410', '97416', 'sud', 'Capitale du Sud, Saint-Pierre associe centre-ville historique, front de mer et quartiers résidentiels comme Terre-Sainte, Ravine des Cabris ou Mont-Vert.'],
    ['Le Tampon', '97430', '97422', 'hauts', 'Grande ville des Hauts, Le Tampon s’étage du 12e au 27e kilomètre jusqu’à la Plaine des Cafres, avec un climat plus frais et humide en altitude.'],
    ['Entre-Deux', '97414', '97403', 'hauts', 'Village créole blotti entre deux rivières, l’Entre-Deux est réputé pour ses cases traditionnelles dont les toitures méritent un savoir-faire respectueux.'],
    ['Petite-Île', '97429', '97405', 'sud', 'Commune agricole du Sud, Petite-Île s’étend du littoral de Grande Anse jusqu’aux Hauts, entre champs de canne et habitat individuel.'],
    ['Saint-Joseph', '97480', '97412', 'sud', 'De Manapany à Vincendo et jusqu’à la Rivière des Remparts, Saint-Joseph est une vaste commune du Sud sauvage, exposée aux pluies et au vent.'],
    ['Saint-Philippe', '97442', '97417', 'sud', 'Au pied du Piton de la Fournaise, Saint-Philippe est l’une des communes les plus arrosées du Sud sauvage, où l’étanchéité des toitures est primordiale.'],
    ['Sainte-Rose', '97439', '97419', 'est', 'Entre coulées de lave et littoral, Sainte-Rose est une commune très pluvieuse où les toitures doivent supporter des précipitations intenses.'],
    ['Saint-Benoît', '97470', '97410', 'est', 'Porte de l’Est traversée par la Rivière des Marsouins, Saint-Benoît reçoit de fortes pluies une grande partie de l’année.'],
    ['Bras-Panon', '97412', '97402', 'est', 'Commune de la vanille, Bras-Panon s’étend du littoral jusqu’aux hauteurs, dans l’une des zones les plus humides de l’île.'],
    ['Saint-André', '97440', '97409', 'est', 'Troisième ville de l’île, Saint-André mêle centre urbain, zones d’activités et quartiers résidentiels au milieu des champs de canne.'],
    ['La Plaine-des-Palmistes', '97431', '97406', 'hauts', 'Située entre les deux plaines sur la route du Volcan, la Plaine-des-Palmistes connaît un climat frais et très pluvieux.'],
    ['Salazie', '97433', '97421', 'hauts', 'Cirque verdoyant aux innombrables cascades, Salazie et Hell-Bourg abritent de belles cases créoles dans un environnement très humide.'],
    ['Cilaos', '97413', '97424', 'hauts', 'Au cœur du cirque du même nom, Cilaos est accessible par la route aux 400 virages : un environnement de montagne où les chantiers demandent une bonne organisation.'],
];

$dept = Department::first(['code' => '974']);
$primarySlug = 'saint-denis';
$serviceLinks = implode(', ', array_map(static fn ($s) => mb_strtolower($s['name']), $services));
foreach ($communes as $index => [$name, $postal, $insee, $zoneKey, $context]) {
    $slug = Str::slug($name);
    $city = City::first(['insee_code' => $insee]) ?? new City();
    $city->fill(['department_id' => $dept?->id(), 'name' => $name, 'slug' => $slug, 'postal_code' => $postal, 'insee_code' => $insee]);
    $city->save();

    $location = CompanyLocation::first(['company_id' => $companyId, 'city_id' => $city->id()]) ?? new CompanyLocation();
    $location->fill(['company_id' => $companyId, 'city_id' => $city->id(), 'is_primary' => $slug === $primarySlug, 'is_active' => true, 'seo_priority' => 8]);
    $location->save();

    $zone = $zones[$zoneKey];
    $pageSlug = 'couvreur-' . $slug;
    $page = Page::findBySlug($pageSlug) ?? new Page();
    $page->fill([
        'type' => 'local', 'slug' => $pageSlug, 'title' => 'Couvreur à ' . $name,
        'h1' => 'Couvreur à ' . $name . ' : fuite, rénovation, étanchéité',
        'meta_title' => 'Couvreur à ' . $name . ' (' . $postal . ') — fuite, rénovation, étanchéité',
        'meta_description' => HART_NAME . ', couvreur à ' . $name . ' : recherche de fuite et urgence 24 h/24, réparation et rénovation de toiture, traitement antirouille, peinture et étanchéité. Déplacement et devis gratuits.',
        'status' => 'published', 'indexable' => true, 'content_is_placeholder' => false,
        'last_generated_at' => date('Y-m-d H:i:s'),
        'published_at' => $page->getAttribute('published_at') ?: date('Y-m-d H:i:s'),
    ]);
    $page->save();
    foreach (PageBlock::where(['page_id' => $page->id()]) as $old) $old->delete();
    $position = 0;
    PageBlock::create(['page_id' => $page->id(), 'type' => 'text', 'position' => $position++, 'data' => ['content' => $context . ' ' . HART_NAME . ', couvreur basé à Saint-Denis, intervient à ' . $name . ' pour vos travaux de toiture en tôle ou en dalle béton : ' . $serviceLinks . '.'], 'is_active' => true]);
    PageBlock::create(['page_id' => $page->id(), 'type' => 'text', 'position' => $position++, 'data' => ['heading' => 'Des toitures adaptées au climat de ' . $zone['label'], 'content' => $zone['climate']], 'is_active' => true]);
    PageBlock::create(['page_id' => $page->id(), 'type' => 'faq', 'position' => $position++, 'data' => ['items' => [
        ['question' => 'Intervenez-vous à ' . $name . ' (' . $postal . ') ?', 'answer' => 'Oui. ' . HART_NAME . ' intervient partout à La Réunion, y compris à ' . $name . '. Le déplacement et le devis sont gratuits.'],
        ['question' => 'Le devis est-il gratuit ?', 'answer' => 'Oui : le déplacement et le devis sont gratuits et sans engagement. Le devis détaille les travaux proposés après examen de votre toiture.'],
        ['question' => 'Que faire en cas de fuite ?', 'answer' => 'Appelez le ' . HART_PHONE . ' : nous intervenons 24 h/24 pour sécuriser votre habitation, stopper temporairement la fuite puis réaliser la réparation définitive.'],
    ]], 'is_active' => true]);
}


/* ------------------------------------------------------------------ */
/* Pages d’atterrissage « service × ville » (mots-clés locaux)         */
/* ------------------------------------------------------------------ */
$coords = ['97401' => [-21.2019, 55.3629], '97402' => [-21.0316, 55.6265], '97403' => [-21.2108, 55.4937], '97404' => [-21.2348, 55.3580], '97405' => [-21.3330, 55.5706], '97406' => [-21.1530, 55.6442], '97407' => [-20.9459, 55.3038], '97408' => [-20.9965, 55.3918], '97409' => [-20.9568, 55.6374], '97410' => [-21.0887, 55.6313], '97411' => [-20.9434, 55.4444], '97412' => [-21.2921, 55.6440], '97413' => [-21.1816, 55.3432], '97414' => [-21.2254, 55.4224], '97415' => [-21.0366, 55.3402], '97416' => [-21.3090, 55.5044], '97417' => [-21.3084, 55.7436], '97418' => [-20.9463, 55.5234], '97419' => [-21.1778, 55.7418], '97420' => [-20.9507, 55.5936], '97421' => [-21.0524, 55.5145], '97422' => [-21.2250, 55.5701], '97423' => [-21.1138, 55.3378], '97424' => [-21.1403, 55.4555]];

$keywords = [
    'recherche-fuite-et-urgence' => [
        'slug' => 'recherche-fuite-toiture', 'label' => 'Recherche de fuite toiture', 'h1' => 'Recherche de fuite et urgence toiture à %s',
        'meta' => 'Fuite de toiture à %s (%s) ? HART TOITURE intervient 24 h/24 : détection de la fuite, mise en sécurité et réparation durable. Déplacement et devis gratuits.',
        'intro' => 'Une tache au plafond, des gouttes après une averse ou une tôle soulevée par le vent ? À %s, HART TOITURE intervient rapidement pour localiser l’origine de la fuite, sécuriser votre habitation et réaliser une réparation durable, sur toiture en tôle comme sur toit-terrasse béton.',
        'zone' => [
            'nord' => 'Dans le Nord, les fuites viennent souvent des fixations corrodées par l’air salin et des jonctions de tôle fatiguées sur les maisons des hauteurs, très exposées aux fortes pluies.',
            'est' => 'Sur la côte au vent, les pluies sont parmi les plus abondantes de l’île : un raccord de tôle ou un joint défaillant se transforme vite en infiltration importante. Une intervention rapide limite les dégâts.',
            'ouest' => 'Sur la côte sous le vent, la chaleur dilate les tôles et fatigue les joints ; les averses soudaines révèlent alors les points faibles. Le sel accélère aussi la corrosion autour des vis.',
            'sud' => 'Dans le Sud, entre littoral venté et quartiers qui montent vers les Hauts, les fuites apparaissent souvent aux faîtages et aux recouvrements de tôle après les épisodes de pluie et de vent.',
            'hauts' => 'Dans les Hauts, l’humidité, le brouillard et la condensation fragilisent les toitures ; une fuite peut rester longtemps invisible et abîmer l’isolation ou la charpente.',
        ],
        'faq' => [['Intervenez-vous en urgence à %s ?', 'Oui. HART TOITURE est disponible 24 h/24 et 7 j/7 pour les urgences à %s : nous sécurisons l’habitation et stoppons temporairement la fuite, puis réalisons la réparation définitive.'], ['Comment trouvez-vous l’origine d’une fuite ?', 'Par une inspection visuelle minutieuse de la couverture : joints, visserie, raccords de tôle, faîtages, relevés et évacuations. L’eau apparaît souvent loin de son point d’entrée.']],
    ],
    'reparation-renovation' => [
        'slug' => 'renovation-toiture', 'label' => 'Réparation et rénovation de toiture', 'h1' => 'Réparation et rénovation de toiture à %s',
        'meta' => 'Réparation et rénovation de toiture à %s (%s) : tôles abîmées, fissures, fixations corrodées, rénovation complète. HART TOITURE, devis gratuit.',
        'intro' => 'Fissures, trous, tôles oxydées ou fixations corrodées : à %s, HART TOITURE répare les zones endommagées de votre toiture et, si nécessaire, la rénove entièrement pour la préparer au climat réunionnais.',
        'zone' => [
            'nord' => 'Dans le Nord, les maisons du littoral subissent l’air salin tandis que celles des hauteurs encaissent de fortes pluies : nous remplaçons les tôles abîmées et posons des vis en acier inoxydable pour une tenue durable.',
            'est' => 'Dans l’Est, très arrosé, une toiture doit évacuer d’importants volumes d’eau : reprise des recouvrements, remplacement des tôles fatiguées et traitements de surface protecteurs sont essentiels.',
            'ouest' => 'Dans l’Ouest ensoleillé, les UV et la chaleur usent les couvertures : la rénovation passe par le remplacement des éléments défectueux et une peinture de protection adaptée.',
            'sud' => 'Dans le Sud, le vent et les embruns fragilisent faîtages et fixations : nous renforçons la couverture et remplaçons les pièces corrodées pour préparer la saison cyclonique.',
            'hauts' => 'Dans les Hauts, l’humidité attaque la charpente et l’isolation : une rénovation complète peut inclure la reprise de la charpente et le remplacement de l’isolation avant la pose d’une nouvelle couverture.',
        ],
        'faq' => [['Faut-il refaire toute la toiture à %s ?', 'Pas forcément. Après inspection, nous privilégions la réparation ciblée lorsque la couverture est encore saine, et ne proposons une rénovation complète que si elle est nécessaire.'], ['Rénovez-vous aussi les toits-terrasses ?', 'Oui, nous intervenons sur les toitures en tôle comme sur les toitures-terrasses en dalle de béton.']],
    ],
    'traitement-anti-rouille' => [
        'slug' => 'traitement-anti-rouille-toiture', 'label' => 'Traitement antirouille de toiture', 'h1' => 'Traitement antirouille de toiture à %s',
        'meta' => 'Toiture en tôle rouillée à %s (%s) ? Brossage, décapage, traitement antioxydant et peinture spéciale Zolpan ou Sika par HART TOITURE. Devis gratuit.',
        'intro' => 'La rouille est l’ennemie des toitures métalliques. À %s, HART TOITURE élimine la corrosion par un nettoyage préparatoire rigoureux, puis applique un traitement antioxydant et une peinture spéciale qui protègent durablement la tôle.',
        'zone' => [
            'nord' => 'Dans le Nord, l’air marin dépose du sel sur les toitures proches du littoral : têtes de vis, recouvrements et chéneaux rouillent vite sans traitement.',
            'est' => 'Dans l’Est, l’humidité quasi permanente entretient la corrosion ; un traitement antirouille suivi d’une peinture protectrice prolonge nettement la durée de vie de la tôle.',
            'ouest' => 'Dans l’Ouest, soleil intense et embruns forment un duo redoutable pour la tôle : la peinture s’use, puis la rouille s’installe. Un traitement à temps évite le remplacement.',
            'sud' => 'Dans le Sud, les toitures exposées au vent marin se corrodent en priorité sur les rives et les fixations ; nous traitons ces points avant de repeindre l’ensemble.',
            'hauts' => 'Dans les Hauts, la condensation et le brouillard favorisent la rouille, parfois par l’intérieur de la tôle : un contrôle régulier est indispensable.',
        ],
        'faq' => [['Ma toiture rouillée à %s peut-elle être sauvée ?', 'Si la tôle n’est pas percée, oui : brossage, décapage, traitement antioxydant et peinture spéciale la protègent durablement. Si elle est percée, nous la remplaçons.'], ['Quels produits utilisez-vous ?', 'Des produits de marques reconnues comme Zolpan et Sika, adaptés aux conditions tropicales de La Réunion.']],
    ],
    'nettoyage-peinture' => [
        'slug' => 'peinture-toiture', 'label' => 'Nettoyage et peinture de toiture', 'h1' => 'Nettoyage et peinture de toiture à %s',
        'meta' => 'Nettoyage et peinture de toiture à %s (%s) : nettoyage complet, peinture de protection anti-UV et anti-humidité. HART TOITURE, déplacement et devis gratuits.',
        'intro' => 'Saletés, mousses et poussières abîment votre toiture. À %s, HART TOITURE réalise un nettoyage complet puis applique une peinture de protection conçue pour résister aux UV et à l’humidité : votre toit est protégé et votre maison retrouve une nouvelle jeunesse.',
        'zone' => [
            'nord' => 'Dans le Nord, pollution urbaine, air salin et pluies laissent des dépôts sur les toitures : un nettoyage complet avant peinture garantit une bonne adhérence.',
            'est' => 'Dans l’Est, l’humidité favorise les mousses et les salissures ; une peinture de protection limite leur retour et renforce l’étanchéité.',
            'ouest' => 'Dans l’Ouest, le soleil décolore et farine les peintures : une peinture adaptée, de préférence claire, protège la tôle et limite la chaleur sous la toiture.',
            'sud' => 'Dans le Sud, poussières, embruns et soleil encrassent les toitures : un nettoyage régulier et une peinture protectrice prolongent la durée de vie de la couverture.',
            'hauts' => 'Dans les Hauts, les mousses et lichens s’installent facilement ; un nettoyage approfondi et une peinture anti-humidité sont les meilleurs remparts.',
        ],
        'faq' => [['Pourquoi nettoyer avant de peindre ?', 'Une surface propre et sèche est indispensable à l’adhérence de la peinture ou de la résine. C’est ce qui garantit la durabilité du résultat.'], ['Tous les combien repeindre sa toiture à %s ?', 'Nous conseillons une couche de peinture protectrice environ tous les deux ans, avec un contrôle après chaque saison des pluies.']],
    ],
    'etancheite' => [
        'slug' => 'etancheite-toiture', 'label' => 'Étanchéité de toiture et toit-terrasse', 'h1' => 'Étanchéité de toiture et toit-terrasse à %s',
        'meta' => 'Étanchéité de toiture et toit-terrasse à %s (%s) : résine d’étanchéité, membranes, systèmes multicouches. HART TOITURE, diagnostic et devis gratuits.',
        'intro' => 'Humidité, infiltrations, dalle poreuse : à %s, HART TOITURE repère les zones fragiles de votre toiture en tôle ou de votre toit-terrasse béton et met en place la solution d’étanchéité adaptée : résine entoilée, membrane ou système multicouche.',
        'zone' => [
            'nord' => 'Dans le Nord, de nombreuses maisons disposent de toits-terrasses en dalle béton ; relevés, évacuations et fissures doivent être traités avant la saison des pluies.',
            'est' => 'Dans l’Est, les pluies intenses mettent les étanchéités à rude épreuve : une résine entoilée ou une membrane bien posée évite les infiltrations dans la dalle.',
            'ouest' => 'Dans l’Ouest, les écarts de température fissurent les revêtements ; nous appliquons des revêtements protecteurs résistants aux UV et aux intempéries.',
            'sud' => 'Dans le Sud, les toits-terrasses exposés au vent et aux averses demandent une étanchéité soignée, notamment au niveau des acrotères et des évacuations.',
            'hauts' => 'Dans les Hauts, un pare-vapeur limite la condensation et protège durablement la structure, en complément de l’étanchéité de surface.',
        ],
        'faq' => [['Quelle solution pour mon toit-terrasse à %s ?', 'Selon l’état du support, nous proposons une résine d’étanchéité entoilée ou une membrane. Le diagnostic gratuit permet de choisir la plus adaptée.'], ['Combien de temps dure le chantier ?', 'Cela dépend de la surface et des temps de séchage de la résine, que nous respectons scrupuleusement. La durée est précisée dans le devis.']],
    ],
];

$cityByInsee = [];
foreach ($communes as [$cName, $cPostal, $cInsee, $cZone, $cContext]) {
    $cityByInsee[$cInsee] = ['name' => $cName, 'postal' => $cPostal, 'zone' => $cZone, 'context' => $cContext, 'city' => City::first(['insee_code' => $cInsee])];
}
$nearest = static function (string $insee) use ($coords, $cityByInsee): array {
    [$la, $lo] = $coords[$insee];
    $d = [];
    foreach ($coords as $code => [$la2, $lo2]) {
        if ((string) $code === $insee || !isset($cityByInsee[$code])) continue;
        $d[$code] = ($la - $la2) ** 2 + (($lo - $lo2) * cos(deg2rad(21.1))) ** 2;
    }
    asort($d);
    return array_map(static fn ($c) => $cityByInsee[$c]['name'], array_slice(array_keys($d), 0, 3));
};

$landingCount = 0;
foreach ($keywords as $serviceSlug => $kw) {
    $serviceId = $serviceIds[$serviceSlug] ?? null;
    if (!$serviceId) continue;
    $servicePoints = [];
    foreach ($services as $s) if ($s['slug'] === $serviceSlug) $servicePoints = $s['points'];
    foreach ($cityByInsee as $insee => $c) {
        if (!$c['city']) continue;
        $cName = $c['name'];
        $near = $nearest((string) $insee);
        $slug = $kw['slug'] . '-' . Str::slug($cName);
        $page = Page::findBySlug($slug) ?? new Page();
        $page->fill([
            'type' => 'local', 'company_service_id' => $serviceId, 'slug' => $slug,
            'title' => $kw['label'] . ' à ' . $cName,
            'h1' => sprintf($kw['h1'], $cName),
            'meta_title' => $kw['label'] . ' à ' . $cName . ' (' . $c['postal'] . ')',
            'meta_description' => sprintf($kw['meta'], $cName, $c['postal']),
            'schema_type' => 'Service', 'status' => 'published', 'indexable' => true, 'content_is_placeholder' => false,
            'last_generated_at' => date('Y-m-d H:i:s'),
            'published_at' => $page->getAttribute('published_at') ?: date('Y-m-d H:i:s'),
        ]);
        $page->save();
        foreach (PageBlock::where(['page_id' => $page->id()]) as $old) $old->delete();
        $position = 0;
        $blocks = [
            ['text', ['content' => sprintf($kw['intro'], $cName)]],
            ['text', ['heading' => $kw['label'] . ' dans ' . $zones[$c['zone']]['label'], 'content' => $kw['zone'][$c['zone']] . "\n" . $c['context']]],
            ['list', ['heading' => 'Notre intervention à ' . $cName, 'items' => $servicePoints]],
            ['text', ['heading' => 'Un couvreur proche de chez vous', 'content' => 'Basée à Saint-Denis, HART TOITURE intervient à ' . $cName . ' (' . $c['postal'] . ') comme dans les communes voisines : ' . implode(', ', $near) . '. Le déplacement et le devis sont gratuits, pour les particuliers, les professionnels et les collectivités.']],
            ['faq', ['items' => array_map(static fn ($f) => ['question' => sprintf($f[0], $cName), 'answer' => sprintf($f[1], $cName)], $kw['faq'])]],
        ];
        foreach ($blocks as [$type, $data]) {
            PageBlock::create(['page_id' => $page->id(), 'type' => $type, 'position' => $position++, 'data' => $data, 'is_active' => true]);
        }
        $local = LocalPage::first(['company_service_id' => $serviceId, 'city_id' => $c['city']->id()]) ?? new LocalPage();
        $local->fill([
            'company_service_id' => $serviceId, 'city_id' => $c['city']->id(), 'page_id' => $page->id(), 'is_active' => true,
            'content_mode' => 'manual', 'content_payload' => ['introduction' => sprintf($kw['intro'], $cName)],
            'status' => 'published', 'generation_status' => 'generated', 'published_at' => $local->getAttribute('published_at') ?: date('Y-m-d H:i:s'),
            'last_generated_at' => date('Y-m-d H:i:s'),
        ]);
        $local->save();
        $landingCount++;
    }
}
echo "Pages service × ville : {$landingCount}.\n";

/* ------------------------------------------------------------------ */
/* Pages structurelles                                                 */
/* ------------------------------------------------------------------ */
$home = Page::findBySlug('accueil') ?? new Page();
$home->fill([
    'type' => 'home', 'slug' => 'accueil', 'title' => HART_NAME,
    'h1' => 'Votre toiture, notre expertise *à La Réunion*',
    'meta_title' => 'HART TOITURE — Couvreur à Saint-Denis, La Réunion : fuite, rénovation, étanchéité',
    'meta_description' => 'HART TOITURE, couvreur à Saint-Denis : recherche de fuite et urgence 24 h/24, réparation et rénovation de toitures en tôle et béton, traitement antirouille, peinture, étanchéité. Toute La Réunion, devis gratuit.',
    'schema_type' => 'RoofingContractor', 'status' => 'published', 'indexable' => true, 'content_is_placeholder' => false,
    'published_at' => $home->getAttribute('published_at') ?: date('Y-m-d H:i:s'),
]);
$home->save();

$legal = "Raison sociale : HART TOITURE\nForme juridique : entrepreneur individuel\nRCS : 912 159 290 R.C.S. Saint-Denis-de-la-Réunion\nSIRET : 912 159 290 00010\nTVA intracommunautaire : FR18912159290\nDirecteur de la publication : Guy HART\nAdresse : 1 route du Moufia, 97490 Saint-Denis, La Réunion\nTéléphone : " . HART_PHONE . "\nE-mail : " . HART_EMAIL
    . "\nHébergement : Hostinger International Ltd, 61 Lordou Vironos Street, 6023 Larnaca, Chypre — www.hostinger.fr"
    . "\nLes contenus de ce site (textes, logo, photographies) sont la propriété de HART TOITURE et protégés par le droit de la propriété intellectuelle.";
$pages = [
    ['contact', 'contact', 'Contactez HART TOITURE', 'Contactez HART TOITURE à Saint-Denis : devis gratuit, urgence 24 h/24, intervention partout à La Réunion.', [['text', ['content' => 'Pour toute demande d’intervention, de devis ou de renseignement, contactez HART TOITURE. Nous répondons rapidement et intervenons partout à La Réunion, 7 j/7 de 7 h à 19 h, et 24 h/24 pour les urgences.']], ['form', ['form_type' => 'contact']]]],
    ['realizations', 'realisations', 'Nos réalisations', 'Chantiers de rénovation, peinture, traitement antirouille et étanchéité de toitures réalisés par HART TOITURE à La Réunion.', [['projects', []]]],
    ['about', 'a-propos', 'Entreprise de couverture à Saint-Denis', 'HART TOITURE, entreprise de couverture fondée par Guy Hart : trente ans d’expérience au service des toitures de La Réunion.', []],
    ['legal_mentions', 'mentions-legales', 'Mentions légales', 'Mentions légales du site HART TOITURE.', [['text', ['heading' => 'Informations légales', 'content' => $legal]]]],
    ['privacy', 'politique-confidentialite', 'Politique de confidentialité', 'Protection de vos données personnelles.', [['text', ['heading' => 'Données personnelles', 'content' => 'Les données personnelles que vous nous communiquez sont utilisées par HART TOITURE aux seules fins de répondre à votre demande. Elles ne sont jamais cédées à des tiers.' . "\nConformément au RGPD, vous disposez d’un droit d’accès, de rectification et de suppression de vos données. Pour l’exercer : " . HART_EMAIL . '. Vous pouvez également vous inscrire gratuitement sur la liste d’opposition au démarchage téléphonique Bloctel.']]]],
    ['cookies', 'politique-cookies', 'Politique cookies', 'Utilisation des cookies sur le site HART TOITURE.', [['text', ['heading' => 'Cookies', 'content' => 'Ce site utilise uniquement des cookies techniques nécessaires à son fonctionnement (session, sécurité des formulaires). Aucun cookie publicitaire n’est déposé sans votre consentement.']]]],
];
foreach ($pages as [$type, $slug, $title, $description, $blocks]) {
    $page = Page::findBySlug($slug) ?? new Page();
    $page->fill([
        'type' => $type, 'slug' => $slug, 'title' => $title, 'h1' => $title, 'meta_title' => $title,
        'meta_description' => $description, 'status' => 'published', 'indexable' => !in_array($type, ['legal_mentions', 'privacy', 'cookies'], true),
        'content_is_placeholder' => false, 'published_at' => $page->getAttribute('published_at') ?: date('Y-m-d H:i:s'),
    ]);
    $page->save();
    foreach (PageBlock::where(['page_id' => $page->id()]) as $old) $old->delete();
    foreach ($blocks as $position => [$blockType, $blockData]) {
        PageBlock::create(['page_id' => $page->id(), 'type' => $blockType, 'position' => $position, 'data' => $blockData, 'is_active' => true]);
    }
}

// Anciennes URL du site toiture-reunion-hart.re.
foreach (['/page-avis' => '/avis-clients', '/vie-privee' => '/politique-confidentialite', '/privacy' => '/politique-cookies'] as $from => $to) {
    $redirect = Redirect::first(['from_path' => $from]) ?? new Redirect();
    $redirect->fill(['from_path' => $from, 'to_path' => $to, 'status_code' => 301, 'is_active' => true]);
    $redirect->save();
}

/* ------------------------------------------------------------------ */
/* Réglages                                                            */
/* ------------------------------------------------------------------ */
$photo = static fn (string $name): string => '/assets/images/hart/photos/' . $name . '.webp';
$settings = new SettingsRepository(Database::instance());
$settings->set('site_installed', true);
$settings->set('business_category_ids', [$category?->id()]);
$settings->set('active_theme', 'professional');
$settings->set('visual.theme_preset', 'hart');
$settings->set('visual.home_template', 'home_hart');
$settings->set('tracking.enabled', true);
$settings->set('wordpress.url', rtrim((string) config('app.url'), '/') . '/wordpress');
$settings->set('visual.zone_map', '/assets/images/hart/carte-reunion-communes.svg');
$settings->set('content.home_about_media_id', (int) $logoFull->id());
$settings->set('content.service_copy_map', $serviceCopyMap);
$settings->set('content.home_copy', [
    'hero_kicker' => 'Couvreur à Saint-Denis · Toute La Réunion',
    'hero_lead' => 'Rénovation, étanchéité et protection des toitures en tôle et en béton. Intervention rapide, diagnostic précis et solutions durables contre les fuites, la rouille et les intempéries.',
    'hero_trust' => ['Urgences 24 h/24, 7 j/7', 'Déplacement et devis gratuits', '30 ans d’expérience'],
    'hero_card_title' => 'Une fuite ? Une urgence ?',
    'hero_card_text' => 'Nous intervenons 24 h/24 pour sécuriser votre habitation et stopper la fuite.',
    'photos' => [
        'hero' => $photo('toiture-tole-rouge-renovee-la-reunion'),
        'why' => $photo('couvreur-hart-sur-toiture-maison'),
        'why_small' => $photo('toiture-tole-bleue-peinture-saint-denis'),
        'emergency' => $photo('nettoyage-toiture-apres'),
    ],
    'badges' => [
        ['icon' => 'tool', 'title' => 'Savoir-faire', 'text' => '30 ans d’expérience en toiture'],
        ['icon' => 'clock', 'title' => 'Réactivité', 'text' => 'Urgences 24 h/24, 7 j/7'],
        ['icon' => 'shield', 'title' => 'Travail soigné', 'text' => 'Un ouvrage soigné et durable'],
        ['icon' => 'pin', 'title' => 'Proximité', 'text' => 'Basés à Saint-Denis, partout sur l’île'],
    ],
    'services_eyebrow' => 'Nos services',
    'services_title' => 'Protéger votre toiture, de la fuite à l’étanchéité',
    'services_text' => 'Toitures en tôle ou en dalle béton : des solutions adaptées aux contraintes tropicales de La Réunion.',
    'seal_value' => '30 ans',
    'seal_label' => 'd’expérience',
    'about_eyebrow' => 'Qui sommes-nous',
    'about_title' => 'Une entreprise locale, une équipe soudée',
    'about_lead' => 'Fondée par Guy Hart, installé à La Réunion depuis l’enfance et fort de trente ans d’expérience, HART TOITURE a adapté des techniques éprouvées aux particularités climatiques de l’île. Avec Fabien, son collaborateur, il forme une équipe soudée dédiée à la rénovation et à l’étanchéité des toitures.',
    'why' => [
        ['icon' => 'search', 'title' => 'Un diagnostic honnête', 'text' => 'Nous vous expliquons clairement l’origine du problème avant toute intervention.'],
        ['icon' => 'shield', 'title' => 'Des matériaux reconnus', 'text' => 'Produits Zolpan et Sika adaptés au climat tropical.'],
        ['icon' => 'doc', 'title' => 'Déplacement et devis gratuits', 'text' => 'Pour les particuliers, les professionnels et les collectivités.'],
        ['icon' => 'clock', 'title' => 'Disponibles 7 j/7', 'text' => 'De 7 h à 19 h, et 24 h/24 pour les urgences.'],
    ],
    'emergency_kicker' => 'Intervention 24 h/24',
    'emergency_title' => 'Une fuite ne prévient pas. Nous non plus, on n’attend pas.',
    'emergency_text' => 'Pluies tropicales, cyclone, tôle arrachée : nous intervenons immédiatement pour sécuriser votre habitation et limiter les dégâts en attendant la réparation définitive.',
    'steps_title' => 'Votre projet en 4 étapes',
    'steps' => [
        ['icon' => 'phone', 'title' => 'Vous nous appelez', 'text' => 'Par téléphone ou via le formulaire, 7 j/7.'],
        ['icon' => 'search', 'title' => 'Diagnostic sur place', 'text' => 'Déplacement gratuit et inspection minutieuse de la toiture.'],
        ['icon' => 'doc', 'title' => 'Devis gratuit', 'text' => 'Une proposition claire, adaptée à votre toiture et à votre budget.'],
        ['icon' => 'tool', 'title' => 'Travaux soignés', 'text' => 'Un ouvrage durable, réalisé avec sérieux et suivi.'],
    ],
    'zone_groups' => [
        ['label' => 'Nord', 'cities' => ['saint-denis', 'sainte-marie', 'sainte-suzanne']],
        ['label' => 'Est', 'cities' => ['saint-andre', 'bras-panon', 'saint-benoit', 'sainte-rose']],
        ['label' => 'Ouest', 'cities' => ['la-possession', 'le-port', 'saint-paul', 'les-trois-bassins', 'saint-leu', 'les-avirons', 'l-etang-sale']],
        ['label' => 'Sud', 'cities' => ['saint-louis', 'saint-pierre', 'petite-ile', 'saint-joseph', 'saint-philippe']],
        ['label' => 'Les Hauts', 'cities' => ['le-tampon', 'entre-deux', 'la-plaine-des-palmistes', 'salazie', 'cilaos']],
    ],
    'zone_eyebrow' => 'Zone d’intervention',
    'zone_title' => 'Basés à Saint-Denis, présents dans toute l’île',
    'zone_text' => 'Nord, Est, Ouest, Sud et les Hauts : HART TOITURE se déplace dans les 24 communes de La Réunion.',
    'faq' => [
        ['q' => 'Intervenez-vous en urgence ?', 'a' => 'Oui. Nous sommes disponibles 24 h/24 et 7 j/7 pour les urgences : fuite, infiltration ou tôle endommagée après un épisode de pluie ou un cyclone.'],
        ['q' => 'Le déplacement et le devis sont-ils payants ?', 'a' => 'Non. Le déplacement et le devis sont gratuits et sans engagement, pour les particuliers comme pour les professionnels et les collectivités.'],
        ['q' => 'Travaillez-vous sur les toits-terrasses en béton ?', 'a' => 'Oui. Nous intervenons sur les toitures en tôle et sur les toitures-terrasses en dalle de béton, notamment pour l’étanchéité par résine ou membrane.'],
        ['q' => 'Comment éviter le retour de la rouille ?', 'a' => 'Contrôlez la toiture après chaque saison des pluies, dégagez les gouttières et appliquez une peinture protectrice environ tous les deux ans. Nous pouvons nous en charger.'],
        ['q' => 'Dans quelles communes intervenez-vous ?', 'a' => 'Basés à Saint-Denis, nous intervenons dans toute l’île de La Réunion, du Nord au Sud et jusque dans les Hauts.'],
    ],
]);
file_put_contents(storage_path('installed.lock'), date(DATE_ATOM));

/* Sitemap XML (régénérable ensuite depuis /admin/sitemap). */
$siteUrl = rtrim((string) config('app.url'), '/');
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
$urlCount = 0;
foreach (Page::where(['status' => 'published']) as $sitemapPage) {
    if (!$sitemapPage->getAttribute('indexable')) continue;
    $type = (string) $sitemapPage->getAttribute('type');
    $path = $type === 'home' ? '/' : '/' . trim((string) $sitemapPage->getAttribute('slug'), '/');
    $priority = $type === 'home' ? '1.0' : ($type === 'service' ? '0.9' : '0.8');
    $xml .= '  <url><loc>' . htmlspecialchars($siteUrl . $path, ENT_XML1) . '</loc><lastmod>' . date('Y-m-d') . '</lastmod><priority>' . $priority . "</priority></url>\n";
    $urlCount++;
}
try {
    $blogPosts = (new \App\Services\Content\WordPressBlogService($settings))->posts(1, 100);
    if ($blogPosts) {
        $xml .= '  <url><loc>' . htmlspecialchars($siteUrl . '/blog', ENT_XML1) . '</loc><priority>0.7</priority></url>' . "\n";
        $urlCount++;
    }
    foreach ($blogPosts as $post) {
        $xml .= '  <url><loc>' . htmlspecialchars($siteUrl . '/blog/' . ($post['slug'] ?? ''), ENT_XML1) . '</loc><lastmod>' . substr((string) ($post['modified'] ?? date('Y-m-d')), 0, 10) . '</lastmod><priority>0.6</priority></url>' . "\n";
        $urlCount++;
    }
} catch (\Throwable) {
    // Blog WordPress facultatif.
}
file_put_contents(public_path('sitemap.xml'), $xml . "</urlset>\n", LOCK_EX);
echo "Sitemap : {$urlCount} URL(s).\n";
Cache::flush();

echo 'HART configuré : ' . count($services) . ' services, ' . count($communes) . " communes (pages locales).\n";
