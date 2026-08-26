<?php

declare(strict_types=1);

/**
 * Créateur de landing pages locales « Couvreur à {Commune} ».
 *
 * - Insère les communes manquantes (+ départements, rattachement entreprise).
 * - Crée/met à jour une page locale publiée et indexable par commune,
 *   avec un contenu rédigé UNIQUE par commune (anti doorway-pages).
 * - Régénère le sitemap.xml et purge le cache de pages.
 *
 * Usage : php scripts/create-commune-landing-pages.php [--dry-run]
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Cache;
use App\Models\City;
use App\Models\Page;
use App\Models\PageBlock;

$isDryRun = in_array('--dry-run', $argv, true);
$companyId = 1;

/**
 * Communes desservies — contenu rédigé à la main, unique par commune.
 */
$communes = [
    [
        'name' => 'Jouy-le-Moutier', 'slug' => 'jouy-le-moutier', 'postal' => '95280', 'insee' => '95323',
        'lat' => 49.0386, 'lng' => 2.0786, 'population' => 19000, 'distance' => 0, 'priority' => 10, 'dept' => 95,
        'h1' => 'Couvreur à Jouy-le-Moutier — JC TOITURES, votre artisan local',
        'meta_title' => 'Couvreur Jouy-le-Moutier (95280) | JC TOITURES — Devis gratuit',
        'meta_description' => 'JC TOITURES, couvreur installé à Jouy-le-Moutier : nettoyage, démoussage, hydrofuge, couverture, gouttières et ravalement dans tout le 95. Devis gratuit.',
        'intro' => 'C’est à Jouy-le-Moutier que JC TOITURES a posé ses outils. Implantés au cœur de la commune, nous connaissons chaque rue, du vieux village aux lotissements récents en passant par le hameau de Jouy-le-Comte. Toitures exposées aux vents d’ouest le long de l’Oise, tuiles mécaniques des pavillons des années 1980 ou couvertures anciennes du centre : nous adaptons chaque intervention aux réalités de votre maison. Notre équipe intervient chez vos voisins chaque semaine, ce qui garantit une réactivité immédiate et un devis gratuit établi sur place.',
        'interventions_heading' => 'Nos interventions à Jouy-le-Moutier',
        'interventions' => [
            'Nettoyage et démoussage complet de toiture avec traitement hydrofuge',
            'Réparation de couvertures : tuiles cassées, faîtières, solins et arêtiers',
            'Recherche de fuite précise, y compris sur les maisons du bord de l’Oise',
            'Pose, réparation et remplacement de gouttières aluminium ou zinc',
            'Ravalement et peinture de façades avec réparation des fissures',
            'Nettoyage de dallages, murets, pignons et façades encrassées',
        ],
        'habitat_heading' => 'Un savoir-faire adapté à l’habitat jouacois',
        'habitat' => 'Le parc immobilier de Jouy-le-Moutier mêle maisons individuelles construites dans les années 1970-1990, pavillons récents des extensions urbaines et habitations plus anciennes autour du village et de Jouy-le-Comte. Cette diversité demande un vrai sens du diagnostic : sous-toiture parfois absente sur les constructions anciennes, liteaunage à reprendre, tuiles mécaniques décolorées par la mousse près des berges humides de l’Oise. Nous intervenons avec le matériel adapté à chaque configuration et nous respectons la quiétude des quartiers résidentiels : bâches de protection, nettoyage du chantier et évacuation complète des gravats.',
        'faq' => [
            ['question' => 'Êtes-vous vraiment basés à Jouy-le-Moutier ?', 'answer' => 'Oui : notre atelier et notre siège se situent à Jouy-le-Moutier même, allée des Éguerets. Pour les habitants de la commune, nous pouvons souvent passer étudier votre toiture dans la journée et démarrer un chantier très rapidement.'],
            ['question' => 'Combien coûte un nettoyage de toiture à Jouy-le-Moutier ?', 'answer' => 'Le tarif dépend de la surface, de la pente et de l’état d’encrassement. Le devis, gratuit et sans engagement, est établi après visite : vous recevez un prix ferme détaillé poste par poste, sans surprise à la fin du chantier.'],
            ['question' => 'Pouvez-vous traiter la mousse qui revient chaque année près de l’Oise ?', 'answer' => 'Oui. L’humidité de la vallée favorise la repousse. Après nettoyage et démoussage, nous appliquons un traitement hydrofuge qui limite l’absorption d’eau et retarde durablement le retour des mousses et lichens.'],
            ['question' => 'Intervenez-vous en cas d’urgence, fuite ou tempête ?', 'answer' => 'Oui. Une fuite ne s’arrête pas toute seule : contactez-nous au 06 27 29 69 06. Nous sécurisons la zone, identifions l’origine de la fuite et réalisons la réparation nécessaire pour protéger votre intérieur.'],
        ],
        'cta_title' => 'Jouy-le-Moutier : votre toiture entre les mains d’un voisin',
        'cta_text' => 'Faites confiance à l’artisan de votre commune : diagnostic honnête, devis gratuit et travail soigné, du village jusqu’aux derniers lotissements.',
    ],
    [
        'name' => 'Vauréal', 'slug' => 'vaureal', 'postal' => '95490', 'insee' => '95654',
        'lat' => 49.0400, 'lng' => 2.0489, 'population' => 16000, 'distance' => 4, 'priority' => 7, 'dept' => 95,
        'h1' => 'Couvreur à Vauréal — entretien et rénovation de toiture',
        'meta_title' => 'Couvreur Vauréal (95490) | Nettoyage & réparation de toit — JC TOITURES',
        'meta_description' => 'Artisan couvreur à Vauréal : nettoyage, démoussage, hydrofuge, réparation de faîtières et gouttières. Intervention rapide dans tout le 95. Devis gratuit.',
        'intro' => 'À quelques minutes de notre base de Jouy-le-Moutier, Vauréal fait partie des communes où nous intervenons le plus souvent. Ses nombreux pavillons, souvent dotés de grandes toitures en tuiles mécaniques, cumulent volontiers mousses et dépôts verts dès que l’entretien s’est espacé. JC TOITURES propose aux Vauréaliens un accompagnement complet : nettoyage doux ou haute pression selon l’état du support, démoussage avec traitement curatif, puis hydrofuge protecteur. Réparations de faîtières, remplacement de tuiles cassées après tempête ou pose de gouttières : tout est réalisé par notre équipe, avec devis gratuit détaillé avant travaux.',
        'interventions_heading' => 'Ce que nous faisons à Vauréal',
        'interventions' => [
            'Démoussage et traitement anti-mousse longue durée des toitures de pavillons',
            'Hydrofuge de toiture pour protéger des pluies fréquentes de la vallée de l’Oise',
            'Remplacement de tuiles cassées et réfection de faîtières scellées ou à sec',
            'Débouchage et remplacement de gouttières engorgées par les feuilles',
            'Recherche et réparation de fuites, même difficiles d’accès',
            'Peinture et ravalement de façades avec traitement des fissures',
        ],
        'habitat_heading' => 'Les toitures de Vauréal : nos constats sur le terrain',
        'habitat' => 'Les constructions vauréaliennes, majoritairement des maisons individuelles des décennies 1980-2000, présentent des toitures à faible pente ou à versants larges où l’eau stagne davantage : c’est la porte ouverte aux mousses. Beaucoup de faîtières d’origine arrivent aussi en fin de vie, avec un mortier qui se fissure et laisse passer l’eau. Nous proposons la reprise complète des faîtières, le contrôle des points singuliers (chatières, noues, sorties de ventilation) et, quand la couverture est trop fatiguée, une étude de rénovation claire et chiffrée. L’objectif reste toujours le même : prolonger la vie de votre toiture sans travaux inutiles.',
        'faq' => [
            ['question' => 'Intervenez-vous rapidement à Vauréal ?', 'answer' => 'Oui : Vauréal est limitrophe de Jouy-le-Moutier, notre commune d’implantation. Nous pouvons généralement proposer une visite gratuite sous quelques jours et un chantier rapide, planifié avec vous.'],
            ['question' => 'Mon faîtage perd ses morceaux de mortier, faut-il tout refaire ?', 'answer' => 'Pas forcément. Si les faîtières sont encore saines, un rescellement partiel suffit parfois. Quand le mortier est pulvérulent sur plusieurs mètres, nous recommandons une reprise propre du faîtage. Le diagnostic est gratuit.'],
            ['question' => 'Le nettoyage abîme-t-il mes tuiles ?', 'answer' => 'Non, pas chez nous. Nous choisissons la méthode selon l’état du support : brosassage et produits professionnels lorsque les tuiles sont fragiles, haute pression maîtrisée lorsqu’elles le tolèrent. Puis un traitement fongicide et, si besoin, un hydrofuge protègent durablement.'],
            ['question' => 'Le devis est-il vraiment gratuit à Vauréal ?', 'answer' => 'Oui, la visite et le devis sont gratuits et sans engagement partout où nous intervenons, y compris à Vauréal. Vous recevez un document clair, avec les prestations détaillées et un prix ferme.'],
        ],
        'cta_title' => 'Vauréal : redonnez un coup de neuf à votre toiture',
        'cta_text' => 'Mousse, tuiles déplacées, gouttière qui déborde ? Décrivez-nous votre projet : nous passons gratuitement chez vous et vous recevez un devis clair sous 48 h.',
    ],
    [
        'name' => 'Menucourt', 'slug' => 'menucourt', 'postal' => '95180', 'insee' => '95395',
        'lat' => 49.0286, 'lng' => 1.9833, 'population' => 5300, 'distance' => 7, 'priority' => 6, 'dept' => 95,
        'h1' => 'Couvreur à Menucourt — artisan toiture et ravalement',
        'meta_title' => 'Couvreur Menucourt (95180) | JC TOITURES — Devis gratuit 95 & 78',
        'meta_description' => 'JC TOITURES intervient à Menucourt : nettoyage, démoussage, hydrofuge de toiture, couverture, gouttières et ravalement. Artisan local, devis gratuit.',
        'intro' => 'Nichée sur les premiers plateaux du Vexin français, Menucourt conserve un caractère paisible que nous apprécions particulièrement : maisons individuelles soignées, jardins arborés et toitures visibles de loin. Dans ce paysage ouvert et venté, les tuiles subissent de plein fouet les intempéries ; la mousse s’installe côté nord et les joints de mortier vieillissent. JC TOITURES accompagne les Mencourtois avec le même sérieux qu’à Jouy-le-Moutier : inspection complète, conseils transparents, nettoyage et démoussage soigneux, traitements protecteurs durables. Nos équipes passent régulièrement à Menucourt : profitez-en pour obtenir votre devis gratuit sans attendre.',
        'interventions_heading' => 'Nos prestations à Menucourt',
        'interventions' => [
            'Nettoyage de toiture adapté aux tuiles exposées aux vents du plateau',
            'Démoussage avec traitement fongicide et protection hydrofuge',
            'Réfection partielle ou complète de couvertures en tuiles mécaniques',
            'Réparation de faîtières, solins et points d’étanchéité sensibles',
            'Gouttières : nettoyage, réparation, remplacement sur mesure',
            'Ravalement de façades et nettoyage de murets et dallages extérieurs',
        ],
        'habitat_heading' => 'Menucourt : un environnement qui marque les toitures',
        'habitat' => 'Située en limite du Parc naturel régional du Vexin français, Menucourt présente un habitat dispersé de maisons individuelles, parfois en hauteur, où la météo frappe fort : pluies battantes, gel en hiver, soleil direct en été. Résultat : microfissurations, joints fatigués et mousses tenaces sur les expositions humides. Nous connaissons ces mécanismes et préparons chaque chantier en conséquence : protection des végétaux du jardin, choix de produits compatibles avec vos matériaux, finitions propres. Les maisons plus anciennes du bourg bénéficient en outre d’un regard attentif sur leur charme d’origine : nous préservons les matériaux traditionnels autant que possible.',
        'faq' => [
            ['question' => 'Venir à Menucourt ne pose-t-il pas de frais de déplacement ?', 'answer' => 'Aucun supplément : Menucourt fait partie de notre secteur habituel, à quelques kilomètres de Jouy-le-Moutier. Le déplacement pour l’étude et le devis est inclus, comme le chantier lui-même.'],
            ['question' => 'Quelle période choisir pour nettoyer ma toiture ?', 'answer' => 'Le printemps et l’été offrent les meilleures conditions de séchage pour les traitements, mais nous intervenons toute l’année hors épisodes de gel intense. Nous vous conseillons la meilleure fenêtre selon l’état de votre toit.'],
            ['question' => 'Mes tuiles ont pris une teinte verdâtre, est-ce grave ?', 'answer' => 'C’est le signe que mousses et lichens se sont installés : ils retiennent l’humidité qui gèle puis éclate les tuiles en hiver. Un démoussage suivi d’un traitement hydrofuge stoppe le phénomène et évite des réparations lourdes plus tard.'],
            ['question' => 'Travaillez-vous aussi sur les murets et terrasses ?', 'answer' => 'Oui : nous nettoyons dallages, murets, façades et pignons. À Menucourt comme ailleurs, beaucoup de clients font réaliser l’ensemble en une seule fois, avec un budget optimisé.'],
        ],
        'cta_title' => 'Menucourt : un toit protégé toute l’année',
        'cta_text' => 'Demandez votre devis gratuit : nous passons chez vous, examinons votre toiture et vous remettons une proposition détaillée, sans aucun engagement.',
    ],
    [
        'name' => 'Courdimanche', 'slug' => 'courdimanche', 'postal' => '95800', 'insee' => '95179',
        'lat' => 49.0486, 'lng' => 1.9972, 'population' => 7000, 'distance' => 5, 'priority' => 6, 'dept' => 95,
        'h1' => 'Couvreur à Courdimanche — toiture, démoussage & gouttières',
        'meta_title' => 'Couvreur Courdimanche (95800) | JC TOITURES — Artisan local 95',
        'meta_description' => 'Artisan couvreur à Courdimanche : nettoyage et démoussage de toiture, hydrofuge, réparation de couverture, gouttières, ravalement. Devis gratuit et rapide.',
        'intro' => 'Entre le vieux village perché et les résidences plus contemporaines, Courdimanche offre une belle variété de toitures que nous entretenons régulièrement depuis Jouy-le-Moutier tout proche. Tuiles anciennes patinées au charme certain autour de l’église, couvertures modernes dans les lotissements : chaque style demande son approche. JC TOITURES privilégie toujours la solution la plus respectueuse du support : brosage doux et produits professionnels sur les tuiles fragiles, nettoyage haute pression raisonné ailleurs, systématiquement suivis d’un traitement anti-mousse et, sur demande, d’un hydrofuge imperméabilisant. Le devis gratuit vous est remis après visite, avec un planning respecté.',
        'interventions_heading' => 'Nos services à Courdimanche',
        'interventions' => [
            'Nettoyage et démoussage de toitures anciennes et contemporaines',
            'Traitement hydrofuge pour enduire la toiture d’une protection déperlante',
            'Réparations de couverture : tuiles, ardoises, noues, arêtiers, faîtières',
            'Installation et remplacement de gouttières pendantes ou rampantes',
            'Recherche de fuite avec rapport clair et réparation ciblée',
            'Ravalement, peinture de façade et nettoyage d’extérieurs',
        ],
        'habitat_heading' => 'Courdimanche : deux univers, une même exigence',
        'habitat' => 'Autour du vieux village, les maisons anciennes de Courdimanche affichent des petites surfaces de tuiles, des pentes marquées et des cheminées multiples : autant de points sensibles à contrôler un par un. Dans les secteurs résidentiels plus récents, ce sont les grandes pentes de tuiles mécaniques et les gouttières longues à entretenir qui dominent. Notre équipe sait passer de l’un à l’autre sans forcer sur les techniques : chaque matériau reçoit le traitement adéquat, chaque point singulier est vérifié. Cette rigueur explique pourquoi une grande partie de nos clients courdimançais vient du bouche-à-oreille entre voisins.',
        'faq' => [
            ['question' => 'Ma maison du village a des tuiles anciennes, saurez-vous les traiter ?', 'answer' => 'Oui. Sur les couvertures anciennes, nous privilégions le nettoyage doux et les produits adaptés afin de ne pas fragiliser des tuiles déjà patinées. Nous remplaçons uniquement les pièces irrécupérables pour garder l’esprit d’origine.'],
            ['question' => 'Faites-vous les gouttières sur mesure ?', 'answer' => 'Oui : pose, remplacement et réparation de gouttières aluminium laqué ou zinc, aux bonnes dimensions et avec descentes correctement positionnées pour éviter les débordements contre la façade.'],
            ['question' => 'Quand saurai-je combien coûtent les travaux ?', 'answer' => 'Dès la visite gratuite : nous mesurons, vérifions l’état réel de la couverture et vous remettons un devis chiffré détaillé. Aucun travail n’est lancé sans votre accord écrit.'],
            ['question' => 'Vous occupe-t-on aussi du ravalement à Courdimanche ?', 'answer' => 'Bien sûr. Ravalement et peinture de façades font partie de nos métiers, avec réparation des fissures et finition dans la teinte de votre choix. Beaucoup de clients combinent toiture + façade en un seul chantier.'],
        ],
        'cta_title' => 'Courdimanche : l’artisan que vos voisins recommandent',
        'cta_text' => 'Rejoignez les nombreux clients du secteur qui nous ont confié leur toiture. Contactez-nous pour une visite gratuite et un devis personnalisé.',
    ],
    [
        'name' => 'Neuville-sur-Oise', 'slug' => 'neuville-sur-oise', 'postal' => '95000', 'insee' => '95450',
        'lat' => 49.0269, 'lng' => 2.0453, 'population' => 11600, 'distance' => 5, 'priority' => 6, 'dept' => 95,
        'h1' => 'Couvreur à Neuville-sur-Oise — nettoyage et réparation de toit',
        'meta_title' => 'Couvreur Neuville-sur-Oise (95000) | JC TOITURES — Devis gratuit',
        'meta_description' => 'JC TOITURES, couvreur à Neuville-sur-Oise : démoussage, hydrofuge, couverture, faîtières, gouttières et ravalement. Artisan proche et disponible. Devis gratuit.',
        'intro' => 'Au bord de l’Oise, Neuville-sur-Oise combine maisons de ville, résidences récentes et belles propriétés riveraines dont les toitures subissent l’humidité ambiante du fleuve. C’est précisément le terrain d’expertise de JC TOITURES : élimination des mousses incrustées, traitement curatif puis préventif, et hydrofuge qui rend la toiture déperlante face aux pluies et aux brouillards matinaux du val. Nous intervenons chez les Neuvillois depuis Jouy-le-Moutier, à cinq minutes de route : disponibilité maximale, devis gratuit rapide et artisans qui travaillent proprement, du premier échafaudage jusqu’au dernier balayage du trottoir.',
        'interventions_heading' => 'Nos interventions à Neuville-sur-Oise',
        'interventions' => [
            'Démoussage complet et traitement anti-retour des mousses liés à l’humidité de l’Oise',
            'Hydrofuge de toiture incolore, effet perlant longue durée',
            'Réparation et rénovation de couvertures en tuiles mécaniques et ardoises',
            'Faîtières, rives et solins : reprise et étanchéité renforcée',
            'Gouttières zinc ou aluminium : pose, réparation, remplacement',
            'Ravalement de façades et nettoyage de dallages et murets',
        ],
        'habitat_heading' => 'L’effet fleuve : ce que l’Oise fait à vos toitures',
        'habitat' => 'Vivre près de l’eau a un revers : l’air ambiant reste chargé d’humidité, surtout en automne et en hiver. À Neuville-sur-Oise, cela accélère la colonisation des toitures par mousses et algues vertes, notamment sur les versants peu ensoleillés. Ces végétations retiennent l’eau contre les tuiles, favorisent le gel éclatant les bords et finissent par créer des infiltrations. Notre protocole répond point par point : élimination mécanique douce, traitement fongicide qui descend dans les pores, hydrofuge final qui fait glisser l’eau. Renouvelé tous les dix ans environ, ce trio maintient une toiture saine des années durant.',
        'faq' => [
            ['question' => 'Pourquoi la mousse revient-elle vite chez moi, près de l’Oise ?', 'answer' => 'L’humidité du fleuve crée un climat idéal pour les spores de mousse. Un simple nettoyage ne suffit pas : il faut un traitement fongicide puis un hydrofuge pour retarder sérieusement la repousse. C’est notre standard d’intervention.'],
            ['question' => 'Quelle différence entre démoussage et hydrofuge ?', 'answer' => 'Le démoussage élimine végétations et salissures ; l’hydrofuge applique ensuite une protection invisible qui rend la toiture déperlante. Combinés, ils assainissent puis protègent : c’est la bonne séquence pour durer.'],
            ['question' => 'Pouvez-vous gérer un sinistre tempête à Neuville ?', 'answer' => 'Oui : tuiles arrachées, faîtière déplacée, infiltration soudaine. Appelez-nous au 06 27 29 69 06, nous priorisons les urgences et sécurisons votre toiture au plus vite.'],
            ['question' => 'Le devis inclut-il l’évacuation des déchets ?', 'answer' => 'Toujours. Nos devis sont tous compris : protection du site, matériel, main-d’œuvre, nettoyage complet et évacuation en décharge agréée. Ce qui est écrit est ce que vous payez.'],
        ],
        'cta_title' => 'Neuville-sur-Oise : protégez votre toiture de l’humidité',
        'cta_text' => 'Un diagnostic gratuit suffit pour savoir où en est votre toiture. Contactez JC TOITURES : réponse rapide, passage sous quelques jours, devis sans engagement.',
    ],
    [
        'name' => 'Éragny', 'slug' => 'eragny', 'postal' => '95610', 'insee' => '95206',
        'lat' => 49.0136, 'lng' => 2.0939, 'population' => 16700, 'distance' => 7, 'priority' => 7, 'dept' => 95,
        'h1' => 'Couvreur à Éragny — artisan toiture, façades & gouttières',
        'meta_title' => 'Couvreur Éragny (95610) | Nettoyage toiture & ravalement — JC TOITURES',
        'meta_description' => 'Couvreur professionnel à Éragny : nettoyage, démoussage, hydrofuge de toiture, réparation de couverture, gouttières et ravalement de façade. Devis gratuit.',
        'intro' => 'Éragny-sur-Oise séduit par ses pavillons bien tenus et ses rues calmes entre rivière et plateaux. Pour autant, aucune toiture n’échappe au temps : tuiles poreuses, joints de faîtage fatigués, gouttières qui fuient aux angles. JC TOITURES met son expérience au service des Éragniens avec une méthode constante : examen complet de la couverture et de la zinguerie, explication claire de ce qui est urgent et de ce qui peut attendre, devis gratuit précis, puis exécution soignée par notre équipe. Nettoyage, démoussage, hydrofuge, réparations ou ravalement : vous savez toujours ce que vous payez et pourquoi.',
        'interventions_heading' => 'À Éragny, nous prenons en charge',
        'interventions' => [
            'Le nettoyage et le démoussage de toutes surfaces de toiture',
            'L’application d’hydrofuges colorés ou incolores selon vos préférences',
            'La réparation de couvertures après intempéries ou usure normale',
            'La reprise d’étanchéité : faîtières, noues, solins, raccords de fenêtres de toit',
            'Le remplacement de gouttières et la correction des problèmes d’écoulement',
            'Le ravalement et la peinture des façades, avec traitement des fissures',
        ],
        'habitat_heading' => 'Un parc pavillonnaire qui mérite un entretien régulier',
        'habitat' => 'Beaucoup de maisons éragniennes datent des grandes vagues de construction des années 1980-1990 : beaux volumes, mais aussi premières générations de tuiles mécaniques grand format dont certains lots vieillissent moins bien. Nous observons fréquemment des bords de tuiles fripés et des faîtières dont le mortier s’effrite. Intervenir tôt coûte peu ; attendre la première tache d’eau au plafond coûte cher. C’est pourquoi nous encourageons les propriétaires d’Éragny à demander un contrôle gratuit : nous photographions les points fragiles et vous recevez un avis franc, même lorsque rien d’urgent n’est à prévoir.',
        'faq' => [
            ['question' => 'Proposez-vous un contrôle gratuit de toiture à Éragny ?', 'answer' => 'Oui. La visite d’inspection est gratuite et sans engagement : nous montons voir la couverture, contrôlons les points sensibles et vous rendons un avis honnête, photos à l’appui.'],
            ['question' => 'Comment savoir si ma toiture doit être refaite entièrement ?', 'answer' => 'Des indices ne trompent pas : tuiles qui se dédoublent en grand nombre, sous-toiture visible depuis les combles, infiltrations répétées. Nous vous disons franchement si une réparation suffit ou si une rénovation se justifie, chiffres comparés.'],
            ['question' => 'Quel hydrofuge utilisez-vous ?', 'answer' => 'Des hydrofuges professionnels de qualité, incolores ou teintés, qui laissent le support respirer tout en le rendant déperlant. Le choix exact dépend de vos tuiles et de vos attentes esthétiques ; il est précisé dans le devis.'],
            ['question' => 'Respectez-vous les horaires et la tranquillité du voisinage ?', 'answer' => 'Oui : horaires classiques de chantier, matériel bruyant utilisé en journée, stationnement organisé et chantier nettoyé chaque soir. Nos clients nous le reprochent rarement, nos voisins jamais.'],
        ],
        'cta_title' => 'Éragny : demandez votre inspection gratuite',
        'cta_text' => 'Un doute sur l’état de votre toiture ? Nous passons, nous regardons, nous vous disons la vérité. Devis gratuit si des travaux se révèlent nécessaires.',
    ],
    [
        'name' => 'Cergy', 'slug' => 'cergy', 'postal' => '95000', 'insee' => '95127',
        'lat' => 49.0364, 'lng' => 2.0656, 'population' => 66000, 'distance' => 7, 'priority' => 8, 'dept' => 95,
        'h1' => 'Couvreur à Cergy — JC TOITURES, expert toiture Val-d’Oise',
        'meta_title' => 'Couvreur Cergy (95000/95800) | Toiture, démoussage, ravalement — JC TOITURES',
        'meta_description' => 'Couvreur à Cergy : nettoyage, démoussage, hydrofuge, réparation et rénovation de toiture, gouttières, ravalement. Préfecture du 95, notre terrain de jeu. Devis gratuit.',
        'intro' => 'Préfecture du Val-d’Oise et ville voisine de Jouy-le-Moutier, Cergy concentre une immense diversité de toitures : villas des bords d’Oise et du port, pavillons de Cergy-le-Haut et des Linandes, immeubles et maisons de ville autour de la préfecture. JC TOITURES y intervient quotidiennement. Notre promesse tient en trois points : un diagnostic gratuit réalisé par quelqu’un qui monte réellement sur le toit, un devis limpide remis rapidement, et un chantier exécuté par notre équipe dédiée — pas de sous-traitance anonyme. Du simple nettoyage-démoussage à la rénovation complète de couverture, nous apportons à Cergy le niveau d’exigence d’une grande commune.',
        'interventions_heading' => 'Tous nos services disponibles à Cergy',
        'interventions' => [
            'Nettoyage et démoussage de toiture avec traitement fongicide professionnel',
            'Hydrofuge de toiture : protection déperlante incolore ou teintée',
            'Réparation de couverture : tuiles cassées, faîtières, noues, solins',
            'Rénovation complète de couverture quand la réparation ne suffit plus',
            'Gouttières : débouchage, réparation, pose neuve aluminium ou zinc',
            'Recherche de fuite, ravalement de façade, nettoyage de dallages et murets',
        ],
        'habitat_heading' => 'Cergy : mille toitures, mille configurations',
        'habitat' => 'De l’ancien village perché aux quartiers de la ville nouvelle, chaque secteur cergyen raconte une époque différente — et impose ses contraintes techniques. Les maisons des années 1970-1980 montrent souvent des faîtières scellées vieillissantes et des tuiles à remplacer pièce par pièce ; les constructions plus récentes, des grandes pentes exigeantes en sécurité d’accès. Autour du port et des quais, l’humidité accélère mousses et lichens. Notre organisation s’adapte : échafaudages conformes, protections collectives, gestion fine des accès et du stationnement urbain. Cergy mérite un artisan structuré : c’est exactement ce que nous sommes.',
        'faq' => [
            ['question' => 'Intervenez-vous dans tous les quartiers de Cergy ?', 'answer' => 'Oui : Centre, Préfecture, Cergy-le-Haut, les Linandes, Saint-Christophe, les bords d’Oise… Notre proximité avec Jouy-le-Moutier nous permet de couvrir toute la commune avec des délais courts.'],
            ['question' => 'Travaillez-vous aussi pour les copropriétés ?', 'answer' => 'Oui, nous étudions les besoins des copropriétés : rapports d’inspection, devis détaillés présentables en assemblée générale et planning respecté. Contactez votre syndic et faites-nous venir.'],
            ['question' => 'Quel est le délai moyen pour un chantier à Cergy ?', 'answer' => 'Après acceptation du devis, nous planifions généralement sous deux à quatre semaines selon la saison ; les urgences (fuite, tempête) sont traitées en priorité absolue.'],
            ['question' => 'Vos devis sont-ils fermes et définitifs ?', 'answer' => 'Oui. Le prix indiqué dans le devis est celui que vous payez, sauf découverte imprévisible explicitement validée avec vous par avenant. Pas de supplément surprise en fin de chantier.'],
        ],
        'cta_title' => 'Cergy : la qualité d’un artisan structuré, au prix juste',
        'cta_text' => 'Toiture, gouttières, façade : décrivez votre projet en deux minutes. Nous revenons vers vous très vite avec une visite gratuite et un devis détaillé.',
    ],
    [
        'name' => 'Pontoise', 'slug' => 'pontoise', 'postal' => '95300', 'insee' => '95500',
        'lat' => 49.0483, 'lng' => 2.1000, 'population' => 30000, 'distance' => 8, 'priority' => 8, 'dept' => 95,
        'h1' => 'Couvreur à Pontoise — toitures anciennes et modernes',
        'meta_title' => 'Couvreur Pontoise (95300) | Rénovation & nettoyage de toiture — JC TOITURES',
        'meta_description' => 'Artisan couvreur à Pontoise : entretien et rénovation de toiture, démoussage, hydrofuge, faîtières, gouttières, ravalement. Spécialiste du bâti ancien. Devis gratuit.',
        'intro' => 'Pontoise, ville d’art et d’histoire, aligne des toitures qui ont traversé les siècles autour de sa cathédrale, mais aussi des quartiers résidentiels plus contemporains sur les coteaux. Cette double personnalité exige un couvreur polyvalent : comprendre les contraintes du bâti ancien — tuiles plates, charpentes vétustes, accès délicats — tout en maîtrisant les codes du moderne. JC TOITURES intervient régulièrement à Pontoise : nettoyage et démoussage respectueux, hydrofuges respirants adaptés aux matériaux historiques, réparations minutieuses de zinguerie, rénovations complètes documentées. Chaque intervention commence par une visite gratuite et se termine par un chantier propre.',
        'interventions_heading' => 'Nos interventions pontoisiennes',
        'interventions' => [
            'Entretien de toitures anciennes : tuiles plates, petits éléments, charpentes sensibles',
            'Démoussage doux et traitements adaptés aux supports historiques',
            'Hydrofuge respirant pour protéger sans enfermer l’humidité',
            'Zinguerie : noues, vallees, gouttières pendantes et rampantes en zinc',
            'Réparations d’urgence et recherche de fuite sur tout type de couverture',
            'Ravalement de façades en centre urbain, avec protections soignées du voisinage',
        ],
        'habitat_heading' => 'Pontoise : respecter l’ancien, réussir le moderne',
        'habitat' => 'Les maisons anciennes de la ville haute et des ruelles près de l’Oise demandent de la retenue : nettoyer trop fort un support fragile, boucher des ventilations nécessaires ou remplacer un zinc par un matériau inadapté créent des dommages invisibles pendant des années. Notre règle est simple : diagnostiquer avant de toucher, choisir des solutions compatibles avec l’âge du bâti et expliquer chaque décision au propriétaire. Dans les quartiers plus récents des coteaux, la problématique inverse domine — grandes pentes, sécurité, rapidité — et là encore notre organisation fait la différence. Deux mondes, une seule exigence de résultat.',
        'faq' => [
            ['question' => 'Ma maison pontoisienne est ancienne, vos méthodes conviennent-elles ?', 'answer' => 'C’est notre spécialité : nettoyage doux, produits pH neutre, hydrofuges microporeux qui laissent respirer les supports anciens. Nous remplaçons uniquement ce qui est irrécupérable, en matériaux cohérents avec l’existant.'],
            ['question' => 'Peut-on travailler à Pontoise sans gêner le voisinage serré ?', 'answer' => 'Oui : nous organisons le stationnement, protégeons les façades mitoyennes, limitons les nuisances sonores aux heures autorisées et nettoyons chaque jour. Le centre urbain dense ne nous fait pas peur.'],
            ['question' => 'Ma zinguerie en zinc est piquée par endroits, faut-il tout changer ?', 'answer' => 'Pas nécessairement : selon l’étendue, un remplacement partiel bien soudé peut suffire. Nous inspectons noues et gouttières et vous proposons la solution la plus économique qui reste durable.'],
            ['question' => 'Le devis tient-il compte des accès difficiles ?', 'answer' => 'Oui : échafaudage, nacelle ou toiture-échelle, chaque configuration est chiffrée dans le devis après visite gratuite. Vous connaissez le coût total avant de vous engager.'],
        ],
        'cta_title' => 'Pontoise : confiez votre toiture à des mains expertes',
        'cta_text' => 'Ancienne ou moderne, votre toiture mérite un diagnostic sérieux. Demandez votre visite gratuite : conseils clairs et devis détaillé garantis.',
    ],
    [
        'name' => 'Osny', 'slug' => 'osny', 'postal' => '95520', 'insee' => '95349',
        'lat' => 49.0586, 'lng' => 2.0667, 'population' => 17000, 'distance' => 9, 'priority' => 7, 'dept' => 95,
        'h1' => 'Couvreur à Osny — entretien, réparation et rénovation de toiture',
        'meta_title' => 'Couvreur Osny (95520) | Démoussage, hydrofuge, gouttières — JC TOITURES',
        'meta_description' => 'JC TOITURES à Osny : nettoyage et démoussage de toiture, hydrofuge, réparation de couverture et faîtières, gouttières, ravalement. Artisan proche. Devis gratuit.',
        'intro' => 'Entre le château de Grouchy et les quartiers pavillonnaires, Osny offre un cadre résidentiel apprécié dont les toitures méritent un entretien suivi. JC TOITURES connaît bien la commune : nous y travaillons chaque mois. Notre approche reste celle qui a fait notre réputation dans tout le sud du Val-d’Oise : inspection gratuite et sincère, devis détaillé sous 48 h, exécution propre et ponctuelle. Que votre maison ait besoin d’un simple démoussage printanier, d’une reprise de faîtière ou d’une rénovation complète de couverture, vous bénéficiez du même soin, avec des matériaux de marques reconnues et une équipe qui respecte votre jardin comme le sien.',
        'interventions_heading' => 'Nos prestations à Osny',
        'interventions' => [
            'Nettoyage de toiture haute pression maîtrisée ou brosage doux',
            'Démoussage chimique professionnel et traitement anti-regrowth longue durée',
            'Hydrofuge de couverture : effet perlant, protection anti-pluie et anti-taches',
            'Réparation de tuiles, faîtières, arêtiers et points singuliers',
            'Pose et remplacement de gouttières, descentes et dauphins',
            'Ravalement de façade, peinture, nettoyage de dallage et murets',
        ],
        'habitat_heading' => 'Osny : des quartiers verts, des toitures exposées',
        'habitat' => 'L’abondance de végétation autour des habitations osniennes — jardins, parcs, boisements — a un corollaire direct sur les toits : spores de mousse omniprésentes, ombre portée qui garde l’humidité, feuilles qui comblent les gouttières à chaque automne. Nos passages réguliers dans la commune nous permettent de conseiller justement : certains toits gagnent à être traités tous les huit à dix ans, d’autres exigent un contrôle annuel des gouttières seulement. Lors de la visite gratuite, nous établissons ce calendrier avec vous plutôt que de vendre un maximum : un client qui nous refait confiance dans dix ans vaut mieux qu’un chantier mal dimensionné aujourd’hui.',
        'faq' => [
            ['question' => 'À quelle fréquence faire nettoyer sa toiture à Osny ?', 'answer' => 'Environ tous les dix ans pour un nettoyage-démoussage avec traitement, davantage si votre maison est entourée d’arbres. Les gouttières, elles, méritent un contrôle chaque automne. Nous fixons le rythme idéal lors de la première visite.'],
            ['question' => 'Ramassez-vous les feuilles et débris des gouttières ?', 'answer' => 'Oui : débouchage complet, contrôle des descentes, test d’écoulement à l’eau. Si la gouttière est percée ou désaxée, nous la réparons ou la remplaçons — devis séparé et clair.'],
            ['question' => 'Utilisez-vous des produits dangereux pour les plantes du jardin ?', 'answer' => 'Nous protégeons systématiquement les massifs et récupérons les eaux de rinçage selon les préconisations fabricants. Les produits utilisés sont professionnels, homologués, et appliqués dans les règles.'],
            ['question' => 'Êtes-vous assurés pour ce type de travaux ?', 'answer' => 'Oui : responsabilité civile professionnelle et garanties obligatoires en vigueur pour nos activités. Les attestations figurent dans chaque dossier de devis, n’hésitez pas à les demander.'],
        ],
        'cta_title' => 'Osny : un devis clair, une toiture impeccable',
        'cta_text' => 'Contactez JC TOITURES dès aujourd’hui : visite gratuite à Osny, devis détaillé sous 48 h, chantier mené proprement du début à la fin.',
    ],
    [
        'name' => 'Auvers-sur-Oise', 'slug' => 'auvers-sur-oise', 'postal' => '95430', 'insee' => '95031',
        'lat' => 49.0703, 'lng' => 2.1697, 'population' => 6900, 'distance' => 13, 'priority' => 6, 'dept' => 95,
        'h1' => 'Couvreur à Auvers-sur-Oise — préservation et rénovation de toitures',
        'meta_title' => 'Couvreur Auvers-sur-Oise (95430) | Toitures anciennes — JC TOITURES',
        'meta_description' => 'Couvreur à Auvers-sur-Oise : restauration respectueuse des toitures du village des peintres, démoussage, hydrofuge, gouttières, ravalement. Devis gratuit.',
        'intro' => 'Village mondialement connu grâce à Van Gogh et Cézanne, Auvers-sur-Oise possède un patrimoine bâti charmant mais exigeant : maisons à colombages, tuiles plates anciennes, façades mixtes briques et enduits, ruelles étroites. Restaurer ces toitures demande autre chose qu’un karcher : il faut lire le bâtiment, respecter ses matériaux et intervenir avec précision. JC TOITURES apporte aux Auversois cette attention de restaurateur associée à l’efficacité d’un artisan moderne : diagnostics détaillés, nettoyages doux, traitements microporeux, zinguerie soignée. Et parce que le tourisme anime la commune, nous travaillons proprement et discrètement, pour vos voisins comme pour vos visiteurs.',
        'interventions_heading' => 'Nos interventions au cœur du village des peintres',
        'interventions' => [
            'Restauration d’éléments de couverture anciens : tuiles plates, faîtières poitevinnes, rives',
            'Nettoyage doux et démoussage adapté aux supports fragiles et aux colombages',
            'Traitements hydrofuges microporeux qui laissent vivre les matériaux',
            'Zinguerie traditionnelle : gouttières zinc, noues et raccords soudés',
            'Réparation de fuites sur toitures complexes à multiple pans',
            'Ravalement de façades anciennes, enduits et joints, dans le respect du caractère du village',
        ],
        'habitat_heading' => 'Auvers : un patrimoine qui commande la finesse',
        'habitat' => 'Les maisons d’Auvers-sur-Oise racontent trois cents ans d’histoire vigneronne et picturale. Leurs toitures — petites tuiles plates, croupes généreuses, lucarnes variées — vieillissent avec noblesse si on les entretient, et dramatiquement si on les brutalise. Notre philosophie sur la commune tient en une phrase : intervenir au minimum nécessaire, au bon endroit. Un démoussage trop agressif décape la patine et ouvre les pores ; un hydrofuge filmogane emprisonne l’humidité. Nous choisissons des systèmes respirants, remplaçons les pièces fatiguées par des matériaux cohérents et documentons chaque intervention avec photos avant/après — utile pour votre mémoire et pour la valeur de votre bien.',
        'faq' => [
            ['question' => 'Mes tuiles plates anciennes peuvent-elles être nettoyées sans risque ?', 'answer' => 'Oui, avec la méthode douce : brossage, produits adaptés, rinçage modéré. Nous testons toujours sur une petite zone et vous montrons le résultat avant de traiter l’ensemble de la toiture.'],
            ['question' => 'Mon habitation est à colombages, cela pose-t-il problème ?', 'answer' => 'Non, c’est même l’occasion de contrôler les points critiques : liaisons toiture-façade, solins, écoulements. Nous adaptons échafaudages et protections pour intervenir sans stress sur ce type de bâti.'],
            ['question' => 'Puis-je avoir un hydrofuge invisible sur ma façade en pierre ?', 'answer' => 'Oui : des hydrofuges microporeux incolores existent spécialement pour pierres et enduits anciens. Ils protègent des pluies battantes tout en laissant le mur respirer — exactement ce qu’il faut à Auvers.'],
            ['question' => 'Travaillez-vous en dehors de la haute saison touristique ?', 'answer' => 'Nous nous adaptons au calendrier de la commune et à vos contraintes : beaucoup de travaux se programment aux périodes calmes pour préserver le confort de tous, y compris le vôtre.'],
        ],
        'cta_title' => 'Auvers-sur-Oise : votre toiture a une histoire, protégeons-la',
        'cta_text' => 'Demandez une visite gratuite : nous évaluerons votre toiture avec le regard d’un amoureux du patrimoine et l’outillage d’un professionnel moderne.',
    ],
    [
        'name' => 'Pierrelaye', 'slug' => 'pierrelaye', 'postal' => '95480', 'insee' => '95487',
        'lat' => 49.0097, 'lng' => 2.1753, 'population' => 9200, 'distance' => 12, 'priority' => 6, 'dept' => 95,
        'h1' => 'Couvreur à Pierrelaye — nettoyage, démoussage et réparations',
        'meta_title' => 'Couvreur Pierrelaye (95480) | JC TOITURES — Toiture & ravalement 95',
        'meta_description' => 'Artisan couvreur à Pierrelaye : démoussage, hydrofuge, réparation de toiture, faîtières, gouttières, ravalement de façade. Proche et réactif. Devis gratuit.',
        'intro' => 'Pierrelaye a grandi par vagues successives : maisons de plaisance d’avant-guerre transformées en résidences principales, lotissements des Trente Glorieuses, constructions plus récentes vers la forêt. Résultat : un patchwork de toitures d’âges différents que nous savons tous traiter. Les plus anciennes montrent parfois des structures fatiguées sous des recouvrements multiples ; les lotissements des années 60-70 affichent des faîtières scellées en fin de vie ; les plus jeunes demandent surtout de la prévention. JC TOITURES passe régulièrement à Pierrelaye : visite gratuite, devis honnête, travaux nets. Votre toiture reçoit exactement ce dont elle a besoin, ni plus ni moins.',
        'interventions_heading' => 'Nos services à Pierrelaye',
        'interventions' => [
            'Diagnostic gratuit de toiture avec reportage photo complet',
            'Nettoyage et démoussage, traitement fongicide préventif et curatif',
            'Hydrofuge de toiture pour stopper absorption et micro-infiltrations',
            'Réparation de faîtières, remplacement de tuiles, reprises de noues',
            'Gouttières : réparation, remplacement, pose de protections feuilles',
            'Ravalement et peinture de façades, nettoyage d’extérieurs divers',
        ],
        'habitat_heading' => 'Pierrelaye : des toitures d’époques différentes sous le même ciel',
        'habitat' => 'Sur une même rue pierrelaysienne, une maison de villégiature des années 1930 peut côtoyer un pavillon de 1970 et une construction des années 2000. Chaque génération a ses pathologies types : charpentes légèrement affaissées et recouvrements hasardeux sur les plus anciennes, mortiers de faîtage pulvérulents et tuiles à bord fripé sur les intermédiaires, ventilation insuffisante des combles sur les récentes. Notre valeur ajoutée tient au diagnostic : identifier précisément ce qui menace votre toiture à court terme, traiter cela en priorité, et vous laisser décider du reste en connaissance de cause. La visite et le devis restent gratuits, quelle que soit l’issue.',
        'faq' => [
            ['question' => 'Ma maison ancienne a eu plusieurs toitures superposées, que faire ?', 'answer' => 'Cela arrive souvent à Pierrelaye. Parfois une dépose complète se justifie pour retrouver une charpente saine et une isolation correcte ; parfois un confortement ciblé suffit. Nous ouvrons le dossier avec vous, devis comparatif si nécessaire.'],
            ['question' => 'Une faîtière bouge quand il vente, c’est urgent ?', 'answer' => 'Oui, c’est un signal d’alerte : le mortier n’assure plus la liaison et une forte rafale peut soulever la ligne. Une reprise de faîtière coûte bien moins cher qu’une tempête de tuiles au sol.'],
            ['question' => 'Proposez-vous des grilles anti-feuilles sur gouttières ?', 'answer' => 'Oui : des crapaudines et grilles de protection efficaces, surtout si votre maison est proche d’arbres. Elles réduisent considérablement l’entretien annuel — nous les posons lors du remplacement ou de la réparation.'],
            ['question' => 'Quel délai pour démarrer chez moi ?', 'answer' => 'Après validation du devis, le délai habituel varie de quelques jours à trois semaines selon la saison et la météo. Les situations d’urgence passent devant tout le monde.'],
        ],
        'cta_title' => 'Pierrelaye : votre diagnostic gratuit vous attend',
        'cta_text' => 'Un doute, une trace d’humidité, une tuile au sol ? Appelez ou envoyez votre demande : nous passons gratuitement et vous obtenez un avis fiable.',
    ],
    [
        'name' => 'Herblay-sur-Seine', 'slug' => 'herblay-sur-seine', 'postal' => '95990', 'insee' => '95313',
        'lat' => 48.9897, 'lng' => 2.1672, 'population' => 27000, 'distance' => 14, 'priority' => 7, 'dept' => 95,
        'h1' => 'Couvreur à Herblay-sur-Seine — toiture, façade et zinguerie',
        'meta_title' => 'Couvreur Herblay-sur-Seine (95990) | JC TOITURES — Devis gratuit 95',
        'meta_description' => 'JC TOITURES à Herblay-sur-Seine : nettoyage, démoussage, hydrofuge, réparation et rénovation de toiture, gouttières, ravalement. Artisan qualifié. Devis gratuit.',
        'intro' => 'Étalée entre les coteaux et les quais de la Seine, Herblay-sur-Seine présente un paysage résidentiel agréable où les toitures se voient de loin — et se jugent vite. Mousses sur les versants ombragés du haut Herblay, gouttières mises à rude épreuve par les grands arbres des parcelles, façades qui noircissent en pied : nous connaissons les scénarios types de la commune. JC TOITURES y apporte son pack complet : nettoyage-démoussage professionnel, hydrofuge déperlant, réparations de couverture et de zinguerie, ravalement. Tout commence par une visite gratuite et un devis limpide, tout se termine par un chantier dont vous ne verrez que le résultat.',
        'interventions_heading' => 'Ce que nous réalisons à Herblay-sur-Seine',
        'interventions' => [
            'Nettoyage et démoussage de toitures sur les coteaux comme près des quais',
            'Hydrofuge anti-pluie longue durée, incolore ou teinté toiture',
            'Réparations toutes couvertures : tuiles, faîtières, noues, rives, solins',
            'Zinguerie complète : gouttières, descentes, dauphins, chéneaux',
            'Recherche de fuite méthodique et réparations ciblées durables',
            'Ravalement, peinture de façade, traitement des fissures et salissures',
        ],
        'habitat_heading' => 'Herblay : la pente et les arbres, deux facteurs clés',
        'habitat' => 'Deux caractéristiques locales structurent nos interventions herbloises. D’abord la topographie : les maisons en haut de coteau prennent le vent et la pluie de plein fouet, celles en bas subissent l’humidité remontante du fleuve. Ensuite la végétation : les belles parcelles arborées déposent feuilles, branches et spores sur les toitures, comblent les gouttières et gardent les versants humides plus longtemps. Notre diagnostic prend ces paramètres en compte pour dimensionner correctement les traitements et proposer, quand c’est utile, des protections de gouttières ou des élagages coordonnés avec vos élagueurs. Une toiture herbloise bien comprise est une toiture qui dure.',
        'faq' => [
            ['question' => 'Les maisons en haut de coteau nécessitent-elles un entretien spécial ?', 'answer' => 'Elles subissent davantage le vent, qui soulève les bords de tuiles et pénètre sous les faîtières. Nous contrôlons particulièrement ces zones lors de nos visites et conseillons des fixations renforcées si besoin.'],
            ['question' => 'Mes gouttières débordent à chaque pluie, quelle solution ?', 'answer' => 'Souvent un simple nettoyage-débouchage suffit ; sinon il faut recalculer le développement ou augmenter le diamètre. Nous testons à l’eau après intervention pour vous garantir un écoulement correct.'],
            ['question' => 'Faites-vous facade + toiture en un seul chantier ?', 'answer' => 'Oui, et c’est souvent avantageux : un seul échafaudage, une seule coordination, un budget optimisé. De nombreux clients herblois choisissent ce format pour rénover l’enveloppe complète de leur maison.'],
            ['question' => 'Comment se passe concrètement le devis gratuit ?', 'answer' => 'Vous nous appelez ou remplissez le formulaire : nous convenons d’un rendez-vous, montons inspecter la toiture, photographions les points importants et vous remettons sous 48 h un devis détaillé ligne par ligne.'],
        ],
        'cta_title' => 'Herblay-sur-Seine : l’enveloppe de votre maison, notre métier',
        'cta_text' => 'Toiture, zinguerie, façade : confiez le tout à une seule équipe responsable. Devis gratuit rapide, exécution impeccable, garantie des travaux.',
    ],
    [
        'name' => 'Montigny-lès-Cormeilles', 'slug' => 'montigny-les-cormeilles', 'postal' => '95370', 'insee' => '95420',
        'lat' => 48.9853, 'lng' => 2.2078, 'population' => 18700, 'distance' => 15, 'priority' => 6, 'dept' => 95,
        'h1' => 'Couvreur à Montigny-lès-Cormeilles — artisan toiture disponible',
        'meta_title' => 'Couvreur Montigny-lès-Cormeilles (95370) | JC TOITURES — Devis gratuit',
        'meta_description' => 'Couvreur à Montigny-lès-Cormeilles : nettoyage et démoussage de toiture, hydrofuge, réparations, gouttières, ravalement de façade. Réactif et soigné. Devis gratuit.',
        'intro' => 'Montigny-lès-Cormeilles, adossée à ses coteaux entre RN14 et Seine, rassemble un habitat pavillonnaire dense et attachant. Les toitures y travaillent dur : exposition variable selon la position sur le coteau, pollution routière qui encrasse, arbres de voisinage qui salissent. JC TOITURES dessert régulièrement la commune depuis Jouy-le-Moutier. Notre offre montignyenne couvre tout le cycle de vie d’une toiture : nettoyage et démoussage pour raviver, hydrofuge pour protéger, réparations ciblées pour corriger, rénovation pour repartir sur vingt ans. Visite gratuite, devis détaillé sous 48 h, exécution propre : la routine que nos clients du secteur connaissent bien.',
        'interventions_heading' => 'Nos interventions à Montigny-lès-Cormeilles',
        'interventions' => [
            'Nettoyage de toiture et décapage des dépôts de pollution atmosphérique',
            'Démoussage professionnel et traitement retardateur de repousse',
            'Hydrofuge déperlant pour limiter encrassement et micro-infiltrations',
            'Réparations : tuiles, faîtières, arêtiers, raccords et étanchéités',
            'Gouttières et descentes : nettoyage, réparation ou remplacement complet',
            'Ravalement et peinture de façades, nettoyage de dallages et murets',
        ],
        'habitat_heading' => 'Montigny : l’effet coteau et la pollution routière',
        'habitat' => 'Positionnée sur un relief marqué et traversée d’axes routiers importants, Montigny-lès-Cormeilles expose ses toitures à deux agresseurs particuliers. Le relief crée des microclimats : les versants nord gardent l’humidité, ceux du sud subissent les UV qui porosent les tuiles. Le trafic dépose des particules grasses qui collent aux couvertures et accélèrent la fixation des salissures. Notre protocole en tient compte : dégraissage doux avant démoussage quand le support est chargé, choix d’hydrofuges autonettoyants qui facilitent les lessivages naturels par la pluie. Le résultat se voit dès la première saison et se confirme année après année.',
        'faq' => [
            ['question' => 'Ma toiture noircit vite malgré un nettoyage récent, pourquoi ?', 'answer' => 'Probablement la combinaison humidité + particules routières qui nourrit algues et champignons. Un traitement avec hydrofuge autonettoyant change la donne : l’eau de pluie lessive elle-même la surface au lieu d’y fixer les salissures.'],
            ['question' => 'Intervenez-vous chez moi même pour une petite réparation ?', 'answer' => 'Oui : remplacer une dizaine de tuiles, refaire une noue, resserrer une gouttière… Aucun chantier n’est trop petit. Les petits travaux d’aujourd’hui sont les grandes économies de demain.'],
            ['question' => 'Quels matériaux utilisez-vous pour les remplacements ?', 'answer' => 'Des tuiles de grandes marques françaises assorties à votre modèle existant, des accessoires certifiés et des mortiers dosés professionnels. Chaque référence figure sur le devis : rien de caché.'],
            ['question' => 'Que couvre votre garantie ?', 'answer' => 'Nos travaux sont couverts par les garanties légales en vigueur, notamment la garantie de parfait achèvement. Le détail des garanties applicables à votre chantier est précisé dans le devis et la facture.'],
        ],
        'cta_title' => 'Montigny-lès-Cormeilles : redonnez de l’éclat à votre toit',
        'cta_text' => 'Contactez-nous pour une visite gratuite : nous évaluerons votre toiture sans jargon et vous proposerons le juste travail au juste prix.',
    ],
    [
        'name' => 'Cormeilles-en-Parisis', 'slug' => 'cormeilles-en-parisis', 'postal' => '95240', 'insee' => '95176',
        'lat' => 48.9744, 'lng' => 2.2022, 'population' => 22900, 'distance' => 16, 'priority' => 6, 'dept' => 95,
        'h1' => 'Couvreur à Cormeilles-en-Parisis — toiture & ravalement expert',
        'meta_title' => 'Couvreur Cormeilles-en-Parisis (95240) | JC TOITURES — Devis gratuit',
        'meta_description' => 'Artisan couvreur à Cormeilles-en-Parisis : nettoyage, démoussage, hydrofuge, réparation de toiture, zinguerie, gouttières et ravalement. Devis gratuit rapide.',
        'intro' => 'Cormeilles-en-Parisis, patrie des plâtrières d’autrefois, étage aujourd’hui ses quartiers résidentiels des coteaux jusqu’à la Seine. Maisons de ville, villas de début de siècle et pavillons d’après-guerre composent un paysage varié que nous entretenons avec plaisir. JC TOITURES propose aux Cormeillais l’éventail complet : nettoyage-démoussage, hydrofuge, réparations de couverture, zinguerie, gouttières et ravalement de façades. Notre signature : la transparence. Diagnostic gratuit argumenté, devis ligne à ligne, planning annoncé respecté. Les habitants de Cormeilles apprécient de savoir exactement ce qui sera fait sur leur toit, quand, et à quel prix — nous aussi.',
        'interventions_heading' => 'Nos prestations à Cormeilles-en-Parisis',
        'interventions' => [
            'Nettoyage et démoussage de toiture avec traitement protecteur',
            'Hydrofuge incolore ou teinté pour toitures ternies par la pollution urbaine',
            'Réparation de couvertures anciennes : tuiles mécaniques, plates, ardoises',
            'Zinguerie : faîtières, noues, gouttières zinc et aluminium, descentes',
            'Recherche de fuite et interventions d’urgence après intempéries',
            'Ravalement de façades avec réparation des fissures et finitions au choix',
        ],
        'habitat_heading' => 'Cormeilles : l’héritage du plâtre et la ville d’aujourd’hui',
        'habitat' => 'L’histoire plâtrière de Cormeilles a laissé des traces utiles : de belles maisons de maîtres et de villas dont les façades et corniches méritent un entretien compétent. Ces bâtiments, souvent dotés d’éléments décoratifs, exigent des couvreurs-façadiers capables de travailler fin — c’est notre cas. Les pavillons plus récents posent d’autres questions : ventilation de combles, performance des faîtières, choix d’hydrofuges en milieu urbain pollué. Quelle que soit votre adresse cormeillaise, la méthode reste identique : inspection gratuite complète, explication pédagogique des enjeux, devis précis puis réalisation soignée par notre équipe fidèle.',
        'faq' => [
            ['question' => 'Ma villa a des corniches et moulures, saurez-vous les préserver ?', 'answer' => 'Oui : nous protégeons soigneusement les éléments décoratifs pendant les travaux de toiture et de ravalement, et nous savons faire intervenir les techniques appropriées pour les restaurer si nécessaire.'],
            ['question' => 'Quel hydrofuge pour une maison en milieu urbain ?', 'answer' => 'Nous recommandons des hydrofuges à effet autonettoyant : la pluie entraîne les particules polluantes au lieu de les figer sur le support. La toiture garde son aspect propre beaucoup plus longtemps.'],
            ['question' => 'Une fuite est apparue hier soir, que faire ce week-end ?', 'answer' => 'Placez des protections à l’intérieur, puis appelez-nous au 06 27 29 69 06. Nous qualifions l’urgence ensemble et intervenons pour sécuriser : bâchage provisoire si nécessaire, réparation définitive aussitôt que possible.'],
            ['question' => 'Vos tarifs sont-ils compétitifs à Cormeilles ?', 'answer' => 'Nos prix reflètent un travail d’artisan qualifié sans intermédiaires ni frais de structure excessifs. Le devis détaillé vous permet de comparer objectivement : postes, quantités, matériaux référencés.'],
        ],
        'cta_title' => 'Cormeilles-en-Parisis : la transparence, du devis à la finition',
        'cta_text' => 'Demander un devis gratuit chez JC TOITURES, c’est recevoir un plan d’action clair pour votre toiture. Faites le test, sans engagement.',
    ],
    [
        'name' => 'Franconville', 'slug' => 'franconville', 'postal' => '95130', 'insee' => '95252',
        'lat' => 48.9869, 'lng' => 2.2317, 'population' => 37000, 'distance' => 18, 'priority' => 7, 'dept' => 95,
        'h1' => 'Couvreur à Franconville — nettoyage, réparation et rénovation',
        'meta_title' => 'Couvreur Franconville (95130) | JC TOITURES — Toiture & façade 95',
        'meta_description' => 'Couvreur professionnel à Franconville : démoussage, hydrofuge de toiture, réparation de couverture, faîtières, gouttières, ravalement. Devis gratuit détaillé.',
        'intro' => 'Franconville, dynamique commune du sud du Val-d’Oise aux portes de Paris, mélange harmonieusement centre-ville animé et quartiers pavillonnaires paisibles. Ses milliers de maisons individuelles constituent un vivier de toitures à entretenir — et JC TOITURES répond présent. Depuis Jouy-le-Moutier, nous servons Franconville avec la même rigueur qu’ailleurs : inspection gratuite sincère, devis limpide, chantiers menés par notre équipe permanente. Démoussage printanier, hydrofuge protecteur, faîtières à reprendre, gouttières à remplacer ou ravalement complet : chaque projet franconvillois reçoit une réponse technique précise, un prix ferme et une date tenue.',
        'interventions_heading' => 'Nos services à Franconville',
        'interventions' => [
            'Nettoyage de toiture toutes méthodes selon l’état du support',
            'Démoussage professionnel et traitement fongicide longue durée',
            'Hydrofuge de couverture pour une protection déperlante durable',
            'Réparations de couverture et de zinguerie, toutes marques de tuiles',
            'Remplacement de gouttières, pose de protections anti-feuilles',
            'Ravalement et peinture de façades, nettoyage de dallages et murets',
        ],
        'habitat_heading' => 'Franconville : densité pavillonnaire, entretien malin',
        'habitat' => 'Avec tant de pavillons construits sur des décennies proches, Franconville voit arriver en cascade des pathologies similaires : faîtières scellées des années 70-80 qui atteignent leur limite, tuiles mécaniques de première génération dont les bords s’effritent, gouttières d’origine sous-dimensionnées face aux orages récents. Notre force : avoir traité ces mêmes cas par centaines dans le département. Nous savons dire si votre faîtage peut attendre deux ans ou doit être repris maintenant, si vos gouttières survivront à l’hiver, si un hydrofuge suffira à redonner dix ans de sérénité. Cette expertise du quotidien fait la différence entre un devis et un vrai conseil.',
        'faq' => [
            ['question' => 'Mes voisins et moi avons le même modèle de maison, des tarifs groupe sont-ils possibles ?', 'answer' => 'Oui : regrouper plusieurs maisons voisines permet d’optimiser installation et logistique, et nous répercutons ces économies dans les devis. Parlez-en à vos voisins, contactez-nous ensemble.'],
            ['question' => 'Comment reconnaître une faîtière à reprendre ?', 'answer' => 'Mortier qui tombe en poussière, faîtières qui bougent au vent, nids d’oiseaux sous les joints, traces d’humidité sous combles près du faîtage. Un coup d’œil gratuit depuis notre échelle suffit à trancher.'],
            ['question' => 'L’hydrofuge change-t-il la couleur de ma toiture ?', 'answer' => 'Non, les hydrofuges incolores sont invisibles une fois secs. Il existe aussi des versions teintées pour uniformiser une toiture délavée — c’est un choix esthétique qui vous appartient, précisé au devis.'],
            ['question' => 'Quel engagement de délai prenez-vous ?', 'answer' => 'La date de chantier est fixée avec vous lors de l’acceptation du devis et nous y tenons : nos équipes sont internes, notre planning maîtrisé. En cas d’intempéries bloquantes, vous êtes prévenu immédiatement.'],
        ],
        'cta_title' => 'Franconville : un artisan fiable, enfin',
        'cta_text' => 'Marre des devis flous et des délais fantaisistes ? Testez JC TOITURES : devis clair sous 48 h, prix ferme, date tenue. Demandez votre visite gratuite.',
    ],
    [
        'name' => 'Conflans-Sainte-Honorine', 'slug' => 'conflans-sainte-honorine', 'postal' => '78700', 'insee' => '78172',
        'lat' => 48.9933, 'lng' => 2.0933, 'population' => 35000, 'distance' => 9, 'priority' => 7, 'dept' => 78,
        'h1' => 'Couvreur à Conflans-Sainte-Honorine — toiture, mousses & gouttières',
        'meta_title' => 'Couvreur Conflans-Sainte-Honorine (78700) | JC TOITURES — Devis gratuit',
        'meta_description' => 'Couvreur à Conflans-Sainte-Honorine : nettoyage, démoussage, hydrofuge de toiture, réparation de couverture, gouttières, ravalement. Au confluent, tout près. Devis gratuit.',
        'intro' => 'Capitale de la batellerie au confluent de la Seine et de l’Oise, Conflans-Sainte-Honorine vit au rythme de l’eau — et ses toitures aussi. L’humidité du confluent nourrit mousses et lichens sur les versants nord, tandis que les quartiers pavillonnaires des hauteurs subissent vents et intempéries frontales. JC TOITURES, implantée à Jouy-le-Moutier à dix minutes, sert régulièrement les Confolanais : nettoyage-démoussage complet, hydrofuges déperlants, réparations de couverture et de zinguerie, ravalements. Notre promesse locale : un artisan vraiment proche, joignable, qui monte voir votre toit gratuitement et vous dit les choses telles qu’elles sont.',
        'interventions_heading' => 'Nos interventions à Conflans-Sainte-Honorine',
        'interventions' => [
            'Nettoyage et démoussage intensifs pour toitures marquées par l’humidité du confluent',
            'Traitements fongicides et hydrofuges longue durée anti-repousse',
            'Réparations de couverture : tuiles, faîtières, noues, solins, rives',
            'Gouttières : débouchage, réparation, remplacement, protection anti-feuilles',
            'Recherche de fuite et sécurisation après tempête',
            'Ravalement et peinture de façades, nettoyage de dallages et murets',
        ],
        'habitat_heading' => 'Conflans : entre fleuve et coteaux, deux climats de toiture',
        'habitat' => 'Les maisons proches des quais de Conflans vivent dans un air saturé d’humidité qui accélère la colonisation biologique des toitures : mousses épaisses, algues vertes, lichens incrustés. Quelques centaines de mètres plus haut, sur les plateaux pavillonnaires, le problème s’inverse : vent, UV et écarts thermiques qui fissurent mortiers et bordures de tuiles. Notre diagnostic distingue systématiquement ces deux régimes pour prescrire juste : traitements biocides renforcés et hydrophobisation lourde en bas, contrôles de fixations et reprises de faîtage en haut. Cette lecture fine du territoire, acquise sur tout le secteur, garantit des travaux qui tiennent leurs promesses.',
        'faq' => [
            ['question' => 'Pourquoi choisir un couvreur de Jouy-le-Moutier plutôt qu’un Parisien ?', 'answer' => 'Proximité et réactivité : nous sommes à dix minutes de Conflans, nous connaissons les pathologies locales du confluent et nous restons disponibles après le chantier. Sans compter des frais de structure bien moindres qu’un grand groupe.'],
            ['question' => 'Ma toiture est très moussue, faut-il la remplacer ?', 'answer' => 'Presque jamais : la mousse est un problème de surface. Après nettoyage, démoussage chimique et hydrofuge, la plupart des toitures retrouvent une santé excellente. Nous ne recommandons la rénovation que si la couverture elle-même est en fin de vie.'],
            ['question' => 'Les produits anti-mousse sont-ils nocifs pour la Seine ?', 'answer' => 'Nous appliquons les traitements selon les protocoles fabricants, avec récupération et neutralisation des rinçages conformément aux bonnes pratiques professionnelles. Les produits homologués utilisés sont conçus pour un usage résidentiel responsable.'],
            ['question' => 'Puis-je programmer le chantier dans plusieurs mois ?', 'answer' => 'Bien sûr : le devis reste valable trois mois et nous réservons votre créneau à l’avance. Beaucoup de clients planifient au printemps ou à l’automne pour bénéficier des meilleures conditions de séchage.'],
        ],
        'cta_title' => 'Conflans-Sainte-Honorine : un couvreur au confluent de vos besoins',
        'cta_text' => 'Visite gratuite, devis sous 48 h, travail garanti : contactez JC TOITURES et découvrez ce qu’un artisan vraiment proche peut faire pour votre toiture.',
    ],
    [
        'name' => 'Achères', 'slug' => 'acheres', 'postal' => '78260', 'insee' => '78005',
        'lat' => 48.9600, 'lng' => 2.0667, 'population' => 21000, 'distance' => 12, 'priority' => 6, 'dept' => 78,
        'h1' => 'Couvreur à Achères — entretien et rénovation de votre toiture',
        'meta_title' => 'Couvreur Achères (78260) | JC TOITURES — Nettoyage & réparation toit',
        'meta_description' => 'JC TOITURES à Achères : démoussage, hydrofuge, réparation de toiture et faîtières, gouttières, ravalement de façade. Artisan proche du 78. Devis gratuit.',
        'intro' => 'Entre forêt nationale et boucles de la Seine, Achères offre un cadre de vie verdoyant où les maisons individuelles occupent fièrement leurs parcelles. Cette proximité de la forêt a un effet direct et mesurable sur les toitures acheresoises : ombre portée, humidité stagnante, pluie de spores et d’aiguilles qui comblent gouttières et accroches mousses. JC TOITURES connaît parfaitement ce contexte pour l’avoir traité dans tout le secteur : nettoyage adapté, démoussage curatif, hydrofuge protecteur, réparations soignées. Les habitants d’Achères disposent ainsi d’un artisan sérieux à douze minutes de route, avec visite gratuite et devis détaillé systématiques.',
        'interventions_heading' => 'Nos prestations à Achères',
        'interventions' => [
            'Nettoyage de toiture et démoussage renforcé pour environnements forestiers',
            'Traitements fongicides longue durée et hydrofuges déperlants',
            'Réparation de couvertures : tuiles, faîtières, arêtiers, étanchéités',
            'Gouttières : nettoyage complet, réparation, remplacement, grilles anti-debris',
            'Recherche de fuite et remise en état après intempéries',
            'Ravalement de façades et nettoyage d’extérieurs (dallages, murets)',
        ],
        'habitat_heading' => 'Achères : quand la forêt dicte l’entretien',
        'habitat' => 'Vivre près de la forêt d’Achères procure une qualité d’air et un calme précieux, mais impose un rituel : surveiller régulièrement sa toiture. Les débris organiques s’accumulent dans les noues et gouttières, retiennent l’eau contre les matériaux et créent des ponts humides où la mousse prospère même sur tuiles récentes. Notre recommandation locale est simple : contrôle annuel des écoulements, nettoyage-démoussage tous les sept à dix ans selon l’exposition, hydrofuge systématique après nettoyage. Nous proposons aussi des grilles anti-debris performantes qui transforment le quotidien des maisons les plus exposées. Un petit investissement pour des années de tranquillité.',
        'faq' => [
            ['question' => 'Ma maison est entourée d’arbres, mes gouttières se bouchent sans arrêt.', 'answer' => 'Classique à Achères ! Outre le nettoyage complet, nous posons des grilles anti-debris qui empêchent feuilles et aiguilles d’entrer tout en laissant passer l’eau. Fini les montées d’échelle mensuelles.'],
            ['question' => 'L’ombre des arbres abîme-t-elle vraiment les tuiles ?', 'answer' => 'Indirectement oui : l’humidité persistante des zones ombragées favorise mousses et lichens qui, en retenant l’eau, fragilisent la surface des tuiles cycle après cycle. Un traitement adapté neutralise durablement ce mécanisme.'],
            ['question' => 'À quelle distance êtes-vous d’Achères ?', 'answer' => 'Douze minutes environ depuis notre base de Jouy-le-Moutier. Cela nous permet des visites gratuites rapides et des interventions très réactives, y compris en cas d’urgence.'],
            ['question' => 'Le devis comprend-il le nettoyage final du terrain ?', 'answer' => 'Absolument : bâchage des plantations pendant les travaux, ramassage intégral des débris, passage au souffleur du jardin impacté et évacuation en centre agréé. Vous retrouvez votre extérieur impeccable.'],
        ],
        'cta_title' => 'Achères : vivez sous une toiture saine, toute l’année',
        'cta_text' => 'Demandez votre devis gratuit : nous analyserons votre situation particulière liée à l’environnement forestier et vous proposerons la protection idéale.',
    ],
];

/* ------------------------------------------------------------------ */
/* 1. Mode simulation                                                  */
/* ------------------------------------------------------------------ */
if ($isDryRun) {
    echo "Mode simulation : " . count($communes) . " commune(s) seraient traitées :\n";
    foreach ($communes as $c) {
        echo '  - /couvreur-' . $c['slug'] . ' (' . $c['name'] . ")\n";
    }
    exit(0);
}

/* ------------------------------------------------------------------ */
/* 2. Départements                                                     */
/* ------------------------------------------------------------------ */
$pdo = \App\Core\Database::instance()->pdo();
$departments = [
    95 => ['Île-de-France', 'Val-d\'Oise'],
    78 => ['Île-de-France', 'Yvelines'],
];
foreach ($departments as $code => [$region, $label]) {
    $stmt = $pdo->prepare('SELECT id FROM artisan_departments WHERE code = ?');
    $stmt->execute([(string) $code]);
    $existing = $stmt->fetchColumn();
    if ($existing === false) {
        $insert = $pdo->prepare('INSERT INTO artisan_departments (region_id, name, code) VALUES ((SELECT id FROM artisan_regions WHERE name = ? LIMIT 1), ?, ?)');
        $insert->execute([$region, $label, (string) $code]);
        echo "+ Département {$label} ({$code}) créé\n";
    }
}

/* Départements ids */
$stmt = $pdo->query("SELECT id, code FROM artisan_departments WHERE code IN ('95','78')");
$deptIds = [];
foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $deptIds[(int) $row['code']] = (int) $row['id'];
}

/* ------------------------------------------------------------------ */
/* 2. Communes + rattachement entreprise                              */
/* ------------------------------------------------------------------ */
foreach ($communes as $commune) {
    $stmt = $pdo->prepare('SELECT id FROM artisan_cities WHERE slug = ?');
    $stmt->execute([$commune['slug']]);
    $cityId = $stmt->fetchColumn();

    if ($cityId === false) {
        $insert = $pdo->prepare('INSERT INTO artisan_cities (department_id, name, slug, postal_code, insee_code, latitude, longitude, population) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([
            $deptIds[$commune['dept']] ?? null,
            $commune['name'], $commune['slug'], $commune['postal'], $commune['insee'],
            $commune['lat'], $commune['lng'], $commune['population'],
        ]);
        $cityId = (int) $pdo->lastInsertId();
        echo "+ Commune {$commune['name']} créée (#{$cityId})\n";
    } else {
        $cityId = (int) $cityId;
        echo "= Commune {$commune['name']} déjà présente (#{$cityId})\n";
    }

    $stmt = $pdo->prepare('SELECT id FROM artisan_company_locations WHERE company_id = ? AND city_id = ?');
    $stmt->execute([$companyId, $cityId]);
    if ($stmt->fetchColumn() === false) {
        $insert = $pdo->prepare('INSERT INTO artisan_company_locations (company_id, city_id, is_primary, distance_km, is_active, seo_priority) VALUES (?, ?, 0, ?, 1, ?)');
        $insert->execute([$companyId, $cityId, $commune['distance'], $commune['priority']]);
        echo "+ Rattachement entreprise ↔ {$commune['name']}\n";
    }

    $commune['city_id'] = $cityId;
}

/* ------------------------------------------------------------------ */
/* 3. Pages locales                                                    */
/* ------------------------------------------------------------------ */
$created = 0;
$updated = 0;

foreach ($communes as $c) {
    $slug = 'couvreur-' . $c['slug'];

    $page = Page::findBySlug($slug);
    if ($page === null) {
        $page = new Page();
        $page->fill(['type' => 'local', 'slug' => $slug]);
    }

    $isNew = $page->id() === null;
    $page->fill([
        'title' => 'Couvreur à ' . $c['name'],
        'h1' => $c['h1'],
        // Le layout ajoute « - {marque} » au meta_title : on ne la duplique pas ici.
        'meta_title' => trim((string) preg_replace('/\s*[|—–-]\s*JC\s*TOITURES\b/u', '', $c['meta_title'], 1)),
        'meta_description' => $c['meta_description'],
        'status' => 'published',
        'indexable' => true,
        'content_is_placeholder' => false,
        'last_generated_at' => date('Y-m-d H:i:s'),
    ]);
    if ($isNew) {
        $page->setAttribute('published_at', date('Y-m-d H:i:s'));
    }
    $page->save();
    $pageId = (int) $page->id();

    /* Reconstruction des blocs (contenu unique par commune) */
    foreach (PageBlock::where(['page_id' => $pageId]) as $block) {
        $block->delete();
    }

    $position = 0;
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'text', 'position' => $position++,
        'data' => ['content' => $c['intro']], 'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'image', 'position' => $position++,
        'data' => [
            'url' => '/uploads/homepage/hero-toiture.jpg',
            'alt' => 'Toiture en tuiles terracotta entretenue par JC TOITURES à ' . $c['name'] . ' (couvreur Val-d\'Oise)',
        ],
        'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'list', 'position' => $position++,
        'data' => ['heading' => $c['interventions_heading'], 'items' => $c['interventions']], 'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'text', 'position' => $position++,
        'data' => ['heading' => $c['habitat_heading'], 'content' => $c['habitat']], 'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'services', 'position' => $position++, 'data' => [], 'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'projects', 'position' => $position++, 'data' => [], 'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'faq', 'position' => $position++,
        'data' => ['items' => array_values(array_map(static fn ($f) => [
            'question' => $f['question'], 'answer' => $f['answer'],
        ], $c['faq']))], 'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'form', 'position' => $position++, 'data' => [], 'is_active' => true,
    ]);
    PageBlock::create([
        'page_id' => $pageId, 'type' => 'cta', 'position' => $position++,
        'data' => ['title' => $c['cta_title'], 'text' => $c['cta_text']], 'is_active' => true,
    ]);

    echo ($isNew ? '+ Page créée' : '= Page mise à jour') . " : /{$slug}\n";
    $isNew ? $created++ : $updated++;
}

/* ------------------------------------------------------------------ */
/* 4. Sitemap                                                          */
/* ------------------------------------------------------------------ */
$siteUrl = rtrim((string) config('app.url'), '/');
if ($siteUrl === '' || str_contains($siteUrl, 'localhost')) {
    $siteUrl = 'https://jc-toitures95.fr';
}

$entries = [];
$stmt = $pdo->query("SELECT slug, type, updated_at FROM artisan_pages WHERE status = 'published' AND indexable = 1 AND type != 'archived'");
foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $path = $row['type'] === 'home' || trim((string) $row['slug']) === '' ? '/' : '/' . ltrim((string) $row['slug'], '/');
    $entries[] = [
        'loc' => $siteUrl . $path,
        'lastmod' => substr((string) $row['updated_at'], 0, 10),
        'priority' => $row['type'] === 'home' ? '1.0' : ($row['type'] === 'local' ? '0.9' : '0.8'),
    ];
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($entries as $entry) {
    $xml .= "  <url>\n    <loc>" . htmlspecialchars($entry['loc'], ENT_XML1) . "</loc>\n" .
        '    <lastmod>' . $entry['lastmod'] . "</lastmod>\n" .
        '    <priority>' . $entry['priority'] . "</priority>\n  </url>\n";
}
$xml .= "</urlset>\n";

$sitemapPath = BASE_PATH . '/public/sitemap.xml';
file_put_contents($sitemapPath, $xml);
echo "Sitemap régénéré : " . count($entries) . " URL(s)\n";

/* ------------------------------------------------------------------ */
/* 5. Purge du cache de pages                                          */
/* ------------------------------------------------------------------ */
Cache::flush();
echo "Cache purgé\n";

echo "\nTerminé : {$created} page(s) créée(s), {$updated} mise(s) à jour.\n";
