<?php
declare(strict_types=1);

use App\Core\Database;
use App\Models\Company;
use App\Models\CompanyService;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Page;
use App\Repositories\SettingsRepository;

require_once dirname(__DIR__) . '/bootstrap.php';
Database::configure(config('database'));

function guillaumeMedia(string $relative, string $type, string $alt): Media {
    $url = '/' . ltrim($relative, '/');
    if ($existing = Media::first(['url' => $url])) return $existing;
    $path = public_path(ltrim($relative, '/'));
    [$width, $height] = getimagesize($path) ?: [null, null];
    return Media::create(['disk_path'=>ltrim($relative,'/'),'url'=>$url,'mime_type'=>mime_content_type($path) ?: 'image/png','size_bytes'=>filesize($path) ?: 0,'width'=>$width,'height'=>$height,'alt_text'=>$alt,'type'=>$type]);
}

$company = Company::current();
if (!$company) throw new RuntimeException('Entreprise introuvable.');
$logo = guillaumeMedia('assets/images/ets-guillaume/logo-officiel.png', 'logo', 'Logo officiel ETS Guillaume');
$company->fill(['logo_main_media_id'=>$logo->id(),'logo_dark_media_id'=>$logo->id(),'primary_color'=>'#111111','secondary_color'=>'#8f1118','accent_color'=>'#d21f2b','theme_style'=>'moderne']);
$company->save();

foreach ([
    'nettoyage-toiture-facade'=>['service-nettoyage-toiture-facade-v2.png','Nettoyage de toiture et de façade'],
    'renovation-remplacement-tuiles'=>['service-renovation-tuiles-v2.png','Rénovation et remplacement de tuiles'],
    'habillage-rives-aluminium'=>['service-rives-aluminium-v2.png','Habillage de rives en aluminium'],
    'reparation-faitage-pose-velux'=>['service-faitage-velux-v2.png','Réparation de faîtage et pose de fenêtre de toit'],
    'peinture-renovation-facade'=>['service-peinture-facade-v2.png','Peinture et rénovation de façade'],
    'zinguerie-gouttieres'=>['service-zinguerie-gouttieres-v2.png','Zinguerie et pose de gouttières'],
    'nettoyage-dalles-exterieurs'=>['service-nettoyage-dalles-v2.png','Nettoyage de dalles et extérieurs'],
] as $slug=>[$filename,$alt]) {
    $media = guillaumeMedia('assets/images/ets-guillaume/'.$filename, 'service', $alt);
    $service = CompanyService::first(['company_id'=>$company->id(),'slug'=>$slug]);
    if ($service && $media) { $service->fill(['image_media_id'=>$media->id(),'is_featured'=>true]); $service->save(); }
}

$settings = new SettingsRepository(Database::instance());
$settings->set('content.home_copy', ['services_eyebrow'=>'Services toiture & façade','services_title'=>'Nos services les plus demandés','services_text'=>'Nettoyage, rénovation, peinture et zinguerie : découvrez nos interventions dans le Jura et le Rhône.','zone_eyebrow'=>'Jura (39) · Rhône (69)','zone_title'=>'Une zone d’intervention clairement définie','zone_text'=>'ETS Guillaume intervient autour de Petit-Noir, Dole et Lons-le-Saunier ainsi que dans le Rhône autour de Lyon.','zone_cities'=>'Petit-Noir, Dole, Lons-le-Saunier, Lyon, Villeurbanne, Vénissieux, Saint-Priest']);

$seo = [
 'nettoyage-toiture-facade'=>[
  'hero'=>'Nettoyage professionnel · Jura 39 et Rhône 69','intro'=>'Retrouvez une toiture et une façade propres grâce à une méthode adaptée à chaque support, sans traitement agressif inutile.','title'=>'Nettoyage de toiture et de façade : protéger durablement vos extérieurs','text'=>'<p>Les mousses, lichens, traces noires et dépôts atmosphériques retiennent l’humidité et dégradent progressivement l’aspect des tuiles et des façades. ETS Guillaume réalise un diagnostic visuel avant toute intervention afin de choisir une méthode compatible avec la nature et l’état du support.</p><h3>Une méthode adaptée à votre toiture ou votre façade</h3><p>Le chantier commence par la protection des abords, des menuiseries et des évacuations. Le nettoyage est ensuite effectué avec une pression maîtrisée et les outils appropriés. Selon le résultat du diagnostic, un traitement complémentaire peut être conseillé pour ralentir la réapparition des micro-organismes.</p><h3>Pourquoi faire nettoyer vos extérieurs ?</h3><p>Un entretien réalisé au bon moment améliore l’apparence du bâtiment, facilite l’écoulement de l’eau et permet de repérer plus tôt une tuile cassée, une fissure ou une zone poreuse. Nous intervenons à Petit-Noir, Dole, Lons-le-Saunier, Lyon et dans les communes couvertes du Jura et du Rhône.</p>',
  'meta'=>'Nettoyage de toiture et façade dans le Jura (39) et le Rhône (69). Traitement adapté aux tuiles et enduits. Diagnostic et devis gratuit.',
  'faq'=>[['Quand faut-il nettoyer une toiture ?','Un contrôle après l’hiver ou après de fortes intempéries est utile. La présence de mousse, de lichens ou un écoulement difficile des eaux indique qu’un diagnostic est recommandé.'],['Utilisez-vous systématiquement la haute pression ?','Non. La pression et la méthode sont choisies selon le matériau et son état afin de ne pas fragiliser les tuiles ou l’enduit.'],['Intervenez-vous aussi sur les façades ?','Oui, ETS Guillaume nettoie les toitures et les façades avec une préparation et une protection adaptées à chaque chantier.']]],
 'renovation-remplacement-tuiles'=>[
  'hero'=>'Rénovation de couverture · Jura 39 et Rhône 69','intro'=>'Remplacement des tuiles cassées ou déplacées et remise en état ciblée de votre couverture.','title'=>'Rénovation et remplacement de tuiles pour une toiture étanche','text'=>'<p>Une tuile fissurée, cassée ou déplacée peut laisser l’eau pénétrer sous la couverture. ETS Guillaume contrôle la zone concernée, recherche les défauts visibles et remplace les éléments endommagés par des tuiles compatibles avec la toiture existante.</p><h3>Une réparation ciblée et expliquée</h3><p>Nous vérifions l’alignement des tuiles, leur fixation, l’état des supports accessibles et les points sensibles proches des raccords. Les éléments défectueux sont déposés proprement puis remplacés en respectant le recouvrement nécessaire à l’évacuation des eaux.</p><h3>Préserver la couverture existante</h3><p>Intervenir rapidement limite le risque d’infiltration et évite qu’une petite dégradation s’étende. Pour une toiture vieillissante, nous pouvons également étudier une rénovation plus globale après diagnostic. Devis gratuit dans le Jura et le Rhône.</p>',
  'meta'=>'Remplacement de tuiles cassées et rénovation de toiture dans le Jura et le Rhône. Recherche des défauts, réparation ciblée et devis gratuit.',
  'faq'=>[['Comment reconnaître une tuile à remplacer ?','Une tuile fissurée, cassée, fortement déplacée ou devenue poreuse doit être contrôlée. Des traces d’humidité sous toiture peuvent aussi signaler un défaut.'],['Peut-on remplacer seulement quelques tuiles ?','Oui, lorsque le reste de la couverture est en bon état, une réparation localisée peut suffire après vérification de la zone.'],['Pouvez-vous rechercher l’origine d’une infiltration ?','Nous examinons les tuiles et les points singuliers accessibles afin d’identifier les défauts visibles avant de proposer les travaux adaptés.']]],
 'habillage-rives-aluminium'=>[
  'hero'=>'Protection des rives · Jura 39 et Rhône 69','intro'=>'Un habillage aluminium sur mesure pour protéger les planches de rive et obtenir une finition nette et durable.','title'=>'Habillage de rives en aluminium : protection et finition sans entretien','text'=>'<p>Les planches de rive sont directement exposées à la pluie, au soleil et aux variations de température. Lorsqu’elles ne sont plus suffisamment protégées, la peinture s’écaille et le bois demande un entretien régulier. L’habillage aluminium crée une enveloppe durable tout en soignant la finition du toit.</p><h3>Une finition façonnée pour votre toiture</h3><p>Les profils sont préparés selon les dimensions de la rive, puis posés avec soin en respectant les raccords, les angles et la ventilation existante. Plusieurs teintes permettent d’accorder l’habillage aux gouttières, aux menuiseries ou à la couleur de la couverture.</p><h3>Limiter l’entretien des débords de toit</h3><p>L’aluminium protège le support des intempéries et ne nécessite pas de remise en peinture régulière. ETS Guillaume réalise la pose et la rénovation des habillages de rive dans le Jura (39) et le Rhône (69).</p>',
  'meta'=>'Habillage de rives en aluminium dans le Jura et le Rhône. Protection des planches de rive, finition sur mesure et devis gratuit.',
  'faq'=>[['Pourquoi habiller une planche de rive en aluminium ?','L’habillage protège le support des intempéries et évite les remises en peinture fréquentes tout en apportant une finition régulière.'],['Peut-on choisir la couleur de l’aluminium ?','Oui, la teinte peut être choisie pour s’accorder avec la couverture, les gouttières ou les menuiseries.'],['Faut-il remplacer la planche de rive avant la pose ?','Le support est contrôlé avant l’habillage. Une partie trop dégradée doit être réparée ou remplacée avant d’être recouverte.']]],
 'reparation-faitage-pose-velux'=>[
  'hero'=>'Faîtage et fenêtre de toit · Jura 39 et Rhône 69','intro'=>'Réparation du faîtage et pose de fenêtre de toit avec une attention particulière portée aux raccords d’étanchéité.','title'=>'Réparation de faîtage et pose de fenêtre de toit en toute sécurité','text'=>'<p>Le faîtage protège la jonction supérieure des deux pans de toiture. Des tuiles faîtières descellées, un mortier fissuré ou un closoir dégradé peuvent favoriser les infiltrations. ETS Guillaume contrôle la ligne de faîtage et remet en état les éléments défectueux.</p><h3>Des raccords de toiture particulièrement soignés</h3><p>Pour une fenêtre de toit, l’ouverture, le chevêtre existant ou à créer et le raccord extérieur doivent être étudiés avant la pose. Le système d’étanchéité est installé selon le type de couverture afin de guider correctement l’eau autour de la fenêtre.</p><h3>Apporter de la lumière sans fragiliser la toiture</h3><p>Une pose maîtrisée améliore la luminosité des combles tout en préservant la continuité de la couverture. Nous intervenons dans le Jura et le Rhône pour les réparations de faîtage et les projets de fenêtres de toit.</p>',
  'meta'=>'Réparation de faîtage et pose de fenêtre de toit dans le Jura et le Rhône. Raccords d’étanchéité soignés et devis gratuit.',
  'faq'=>[['Quels signes indiquent un faîtage dégradé ?','Des tuiles faîtières déplacées, un mortier fissuré ou des traces d’humidité sous la ligne de faîtage justifient un contrôle.'],['Posez-vous des fenêtres de toit sur une couverture existante ?','Oui, après étude de la toiture, de la structure et du type de couverture afin de prévoir l’ouverture et les raccords adaptés.'],['La pose comprend-elle l’étanchéité extérieure ?','Oui, les raccords extérieurs font partie des points essentiels de la pose et sont adaptés au matériau de couverture.']]],
 'peinture-renovation-facade'=>[
  'hero'=>'Rénovation de façade · Jura 39 et Rhône 69','intro'=>'Préparation du support et mise en peinture extérieure pour retrouver une façade propre, uniforme et protégée.','title'=>'Peinture et rénovation de façade : une préparation avant tout','text'=>'<p>Une peinture extérieure durable dépend directement de la préparation du support. Avant la mise en couleur, ETS Guillaume examine l’adhérence de l’ancienne finition, les salissures, les microfissures et les zones qui nécessitent une reprise.</p><h3>Nettoyer, réparer et préparer la façade</h3><p>Les surfaces sont protégées et nettoyées, les parties non adhérentes sont retirées et les défauts compatibles avec la prestation sont repris. Une sous-couche peut être appliquée lorsque le support l’exige, avant la peinture de finition adaptée à l’extérieur.</p><h3>Valoriser et protéger votre maison</h3><p>La rénovation redonne une apparence homogène au bâtiment et renforce la protection de la façade contre les intempéries. Nous réalisons vos travaux de peinture extérieure à Petit-Noir, dans le Jura et dans le Rhône.</p>',
  'meta'=>'Peinture et rénovation de façade dans le Jura (39) et le Rhône (69). Préparation, traitement des défauts et devis gratuit.',
  'faq'=>[['Pourquoi préparer la façade avant de peindre ?','Une surface propre, saine et suffisamment adhérente est indispensable pour obtenir une finition régulière et durable.'],['Traitez-vous les fissures avant peinture ?','Les défauts sont examinés avant le chantier. Les fissures compatibles avec une reprise de façade sont préparées avant la finition.'],['Comment choisir la couleur de façade ?','Le choix dépend de vos préférences, de l’environnement du bâtiment et des éventuelles règles d’urbanisme de la commune.']]],
 'zinguerie-gouttieres'=>[
  'hero'=>'Évacuation des eaux pluviales · Jura 39 et Rhône 69','intro'=>'Pose et réparation de gouttières et d’éléments de zinguerie pour guider efficacement l’eau de pluie.','title'=>'Zinguerie et gouttières : protéger toiture, façade et fondations','text'=>'<p>La zinguerie assure l’évacuation de l’eau et l’étanchéité de plusieurs points sensibles de la toiture. Une gouttière percée, déformée ou mal fixée peut provoquer des débordements sur la façade et concentrer l’humidité au pied du bâtiment.</p><h3>Pose, réparation et contrôle des évacuations</h3><p>ETS Guillaume vérifie les pentes, les fixations, les jonctions et les descentes avant de proposer une réparation ou un remplacement. Les pièces sont ajustées avec soin pour accompagner les mouvements du métal et assurer un écoulement régulier.</p><h3>Des finitions adaptées à chaque couverture</h3><p>Nous intervenons également sur les raccords et éléments de finition en zinc autour des zones sensibles. Demandez votre diagnostic et votre devis gratuit dans le Jura (39) ou le Rhône (69).</p>',
  'meta'=>'Pose et réparation de gouttières et zinguerie dans le Jura et le Rhône. Évacuation des eaux pluviales, raccords et devis gratuit.',
  'faq'=>[['Quand faut-il remplacer une gouttière ?','Une gouttière fortement déformée, percée à plusieurs endroits ou dont les jonctions restent fuyardes peut nécessiter un remplacement.'],['Réparez-vous les descentes d’eau pluviale ?','Oui, nous contrôlons les jonctions, les fixations et les descentes afin de rétablir une évacuation correcte.'],['Pourquoi une gouttière déborde-t-elle ?','Un bouchon, une pente insuffisante, une fixation déformée ou une capacité inadaptée peuvent provoquer un débordement.']]],
 'nettoyage-dalles-exterieurs'=>[
  'hero'=>'Nettoyage des extérieurs · Jura 39 et Rhône 69','intro'=>'Nettoyage de terrasses, dallages et surfaces extérieures avec une méthode choisie selon le matériau.','title'=>'Nettoyage de dalles et terrasses : retrouver des extérieurs propres','text'=>'<p>Les terrasses et allées accumulent avec le temps des traces noires, des mousses et des salissures qui ternissent le matériau et peuvent rendre la surface glissante. ETS Guillaume adapte le nettoyage à la nature des dalles et à leur niveau d’encrassement.</p><h3>Un nettoyage régulier et maîtrisé</h3><p>Les abords sont préparés avant l’intervention. La pression, les accessoires et les éventuels produits sont choisis pour décoller les dépôts sans attaquer inutilement les joints ou la surface. Un essai peut être réalisé sur une zone discrète lorsque le support l’exige.</p><h3>Terrasses, allées et cours extérieures</h3><p>Le nettoyage améliore immédiatement l’aspect des extérieurs et facilite leur entretien courant. Nous intervenons auprès des particuliers dans le Jura et le Rhône, sur devis gratuit.</p>',
  'meta'=>'Nettoyage de dalles, terrasses et allées dans le Jura et le Rhône. Méthode adaptée au support et devis gratuit.',
  'faq'=>[['Quels types de surfaces nettoyez-vous ?','Nous intervenons sur différents dallages, terrasses et allées après vérification de la nature et de l’état du support.'],['La haute pression convient-elle à toutes les dalles ?','Non. La pression et l’accessoire doivent être adaptés pour éviter d’endommager la surface ou les joints.'],['Le nettoyage retire-t-il les mousses ?','Le nettoyage permet d’enlever les dépôts présents. Selon le support et l’exposition, un entretien périodique peut rester nécessaire.']]],
];

$copyMap=[];
foreach($seo as $slug=>$content){
 $service=CompanyService::first(['company_id'=>$company->id(),'slug'=>$slug]); if(!$service)continue;
 $service->fill(['description'=>$content['intro'],'meta_title'=>$service->getAttribute('public_name').' | ETS Guillaume','meta_description'=>$content['meta']]);$service->save();
 $page=Page::findBySlug($slug);if($page){$page->fill(['meta_title'=>$service->getAttribute('public_name'),'meta_description'=>$content['meta'],'h1'=>$service->getAttribute('public_name').' dans le Jura (39) et le Rhône (69)']);$page->save();}
 $copyMap[(string)$service->id()]=['hero_eyebrow'=>$content['hero'],'hero_intro'=>$content['intro'],'about_title'=>$content['title'],'about_text'=>$content['text']];
 foreach(Faq::forSubject('CompanyService',(int)$service->id()) as $old)$old->delete();
 foreach($content['faq'] as $i=>[$question,$answer])Faq::create(['faqable_type'=>'CompanyService','faqable_id'=>$service->id(),'question'=>$question,'answer'=>$answer,'sort_order'=>$i+1]);
}
$settings->set('content.service_copy_map',$copyMap);
echo "Logo, couleurs et photos mis à jour.\n";
