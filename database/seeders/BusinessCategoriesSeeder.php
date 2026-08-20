<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BusinessCategory;
use App\Models\CategoryService;
use App\Models\Service;

/**
 * Seeds the 12 starter metiers (prompt.md section 15) and their services.
 * Couvreur, elagueur and climaticien/chauffagiste service lists are taken
 * verbatim from prompt.md section 3; the others are built at the same
 * density (8-20 services) and tone. Editable afterwards from the admin -
 * this only runs once, when the `business_categories` table is empty.
 */
final class BusinessCategoriesSeeder
{
    public static function run(): void
    {
        if (BusinessCategory::count() > 0) {
            return;
        }

        foreach (self::catalog() as $sortOrder => $definition) {
            $category = BusinessCategory::create([
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'schema_org_type' => $definition['schema_org_type'],
                'is_active' => true,
                'sort_order' => $sortOrder + 1,
            ]);

            foreach ($definition['services'] as $serviceSortOrder => $serviceDefinition) {
                $service = Service::first(['slug' => $serviceDefinition['slug']]) ?? Service::create([
                    'slug' => $serviceDefinition['slug'],
                    'name' => $serviceDefinition['name'],
                    'default_description' => $serviceDefinition['description'],
                    'is_active' => true,
                ]);

                CategoryService::create([
                    'business_category_id' => $category->id(),
                    'service_id' => $service->id(),
                    'sort_order' => $serviceSortOrder + 1,
                ]);
            }
        }
    }

    /** @return array<int,array{slug:string,name:string,schema_org_type:string,services:array<int,array{slug:string,name:string,description:string}>}> */
    private static function catalog(): array
    {
        return [
            [
                'slug' => 'couvreur',
                'name' => 'Couvreur',
                'schema_org_type' => 'RoofingContractor',
                'services' => self::services([
                    'Renovation de toiture' => 'Remise a neuf complete de la toiture, structure et couverture.',
                    'Reparation de toiture' => "Intervention ciblee sur les zones endommagees de la toiture.",
                    'Recherche de fuite' => "Diagnostic precis de l'origine d'une infiltration en toiture.",
                    'Remplacement de tuiles' => 'Depose et pose de tuiles cassees, fissurees ou deplacees.',
                    'Nettoyage de toiture' => 'Elimination des mousses, lichens et debris accumules sur le toit.',
                    'Demoussage' => 'Traitement anti-mousse pour proteger et assainir la toiture.',
                    'Traitement hydrofuge' => "Application d'un traitement qui protege la toiture de l'humidite.",
                    'Zinguerie' => 'Fabrication et pose des elements de zinguerie (chenaux, noues, closoirs).',
                    'Pose de gouttieres' => "Installation de gouttieres pour l'evacuation des eaux pluviales.",
                    'Reparation de gouttieres' => 'Remise en etat de gouttieres percees, bouchees ou desolidarisees.',
                    'Isolation des combles' => "Isolation thermique des combles perdus ou amenages.",
                    'Pose de fenetres de toit' => 'Installation de fenetres de toit pour eclairer les combles.',
                    'Etancheite de toiture' => "Traitement de l'etancheite des toitures plates ou terrasses.",
                    'Intervention apres intemperies' => 'Reparation en urgence apres tempete, grele ou fortes pluies.',
                ]),
            ],
            [
                'slug' => 'plombier',
                'name' => 'Plombier',
                'schema_org_type' => 'Plumber',
                'services' => self::services([
                    'Depannage plomberie urgence' => "Intervention rapide en cas de panne ou fuite urgente.",
                    'Recherche de fuite' => "Detection precise de l'origine d'une fuite d'eau.",
                    'Debouchage de canalisation' => 'Debouchage des canalisations et evacuations obstruees.',
                    'Installation sanitaire' => "Pose d'equipements sanitaires (lavabo, douche, WC).",
                    'Renovation de salle de bain' => 'Renovation complete de salle de bain, plomberie comprise.',
                    'Installation de chauffe-eau' => "Pose d'un chauffe-eau electrique ou thermodynamique.",
                    'Remplacement de chauffe-eau' => "Remplacement d'un chauffe-eau ancien ou en panne.",
                    'Pose de robinetterie' => 'Installation et remplacement de robinets et mitigeurs.',
                    'Installation de WC' => "Pose ou remplacement de toilettes, WC suspendus compris.",
                    'Installation adoucisseur d\'eau' => "Pose d'un adoucisseur pour traiter le calcaire.",
                    'Entretien de plomberie' => "Verification et entretien preventif de l'installation.",
                    'Mise aux normes plomberie' => "Mise en conformite d'une installation de plomberie.",
                ]),
            ],
            [
                'slug' => 'chauffagiste',
                'name' => 'Chauffagiste',
                'schema_org_type' => 'HVACBusiness',
                'services' => self::services([
                    'Installation de chaudiere' => "Pose d'une chaudiere gaz, fioul ou biomasse.",
                    'Remplacement de chaudiere' => "Remplacement d'une chaudiere ancienne par un modele performant.",
                    'Entretien de chaudiere' => 'Entretien annuel obligatoire de la chaudiere.',
                    'Depannage de chauffage' => 'Intervention rapide en cas de panne de chauffage.',
                    'Installation de pompe a chaleur' => "Pose d'une pompe a chaleur air/eau ou air/air.",
                    'Entretien de pompe a chaleur' => 'Entretien periodique pour garantir la performance de la PAC.',
                    'Desembouage' => "Nettoyage du circuit de chauffage pour retrouver son efficacite.",
                    'Installation de plancher chauffant' => "Pose d'un plancher chauffant hydraulique ou electrique.",
                    'Installation de chauffe-eau' => "Pose d'un chauffe-eau couple au systeme de chauffage.",
                    'Entretien de chauffe-eau' => "Verification et entretien du chauffe-eau.",
                    'Ramonage' => "Ramonage des conduits pour la securite de l'installation.",
                    'Mise aux normes gaz' => "Mise en conformite d'une installation au gaz.",
                ]),
            ],
            [
                'slug' => 'climaticien',
                'name' => 'Climaticien',
                'schema_org_type' => 'HVACBusiness',
                'services' => self::services([
                    'Installation de climatisation' => "Pose d'un systeme de climatisation adapte au logement.",
                    'Entretien de climatisation' => 'Entretien annuel pour prolonger la duree de vie du systeme.',
                    'Depannage de climatisation' => 'Intervention rapide en cas de panne de climatisation.',
                    'Installation de pompe a chaleur air/air' => 'Pose d\'une PAC air/air reversible chaud et froid.',
                    'Recharge de gaz refrigerant' => 'Recharge du fluide frigorigene d\'un climatiseur.',
                    'Installation de VMC' => "Pose d'une ventilation mecanique controlee.",
                    'Entretien de VMC' => "Nettoyage et verification d'une VMC existante.",
                    'Climatisation reversible' => 'Installation de systemes reversibles chaud/froid.',
                    'Climatisation gainable' => 'Pose de climatisation gainable discrete et performante.',
                    'Rafraichissement de bureaux' => 'Solutions de climatisation pour locaux professionnels.',
                    'Etude thermique' => "Etude des besoins avant installation d'un systeme.",
                    'Maintenance annuelle' => 'Contrat de maintenance pour un fonctionnement optimal.',
                ]),
            ],
            [
                'slug' => 'elagueur',
                'name' => 'Elagueur',
                'schema_org_type' => 'HomeAndConstructionBusiness',
                'services' => self::services([
                    'Elagage d\'arbres' => "Taille raisonnee des branches pour la sante de l'arbre.",
                    'Abattage d\'arbres' => "Abattage securise d'arbres, y compris en milieu difficile.",
                    'Taille de haies' => 'Taille et mise en forme de haies de toutes tailles.',
                    'Dessouchage' => "Extraction complete d'une souche apres abattage.",
                    'Rognage de souche' => "Broyage de la souche sans extraction complete.",
                    'Debroussaillage' => 'Debroussaillage de terrains et zones a risque incendie.',
                    'Entretien de jardin' => "Entretien regulier des espaces verts.",
                    'Evacuation des dechets verts' => 'Enlevement et evacuation des dechets de coupe.',
                    'Taille sanitaire' => "Taille visant a retirer les parties malades ou mortes.",
                    'Taille de formation' => "Taille destinee a guider la croissance d'un jeune arbre.",
                    'Demontage d\'arbre' => "Demontage progressif d'un arbre en espace contraint.",
                    'Intervention sur arbre dangereux' => "Intervention d'urgence sur un arbre menacant de tomber.",
                ]),
            ],
            [
                'slug' => 'paysagiste',
                'name' => 'Paysagiste',
                'schema_org_type' => 'HomeAndConstructionBusiness',
                'services' => self::services([
                    'Creation de jardin' => "Conception et amenagement complet d'un jardin.",
                    'Entretien de jardin' => "Entretien regulier des espaces verts.",
                    'Tonte de pelouse' => 'Tonte reguliere ou ponctuelle de la pelouse.',
                    'Taille de haies' => 'Taille et entretien des haies du jardin.',
                    'Plantation d\'arbres et arbustes' => "Selection et plantation d'especes adaptees au terrain.",
                    'Engazonnement' => "Creation d'une pelouse par semis ou placage de gazon.",
                    'Pose de cloture' => "Installation de clotures et de portillons.",
                    'Creation de terrasse' => "Amenagement de terrasses exterieures.",
                    'Installation d\'arrosage automatique' => "Pose d'un systeme d'arrosage automatise.",
                    'Amenagement paysager' => "Conception globale d'espaces exterieurs.",
                    'Evacuation de dechets verts' => 'Enlevement des dechets issus de l\'entretien du jardin.',
                    'Pose de dallage exterieur' => "Pose d'allees et de dallages exterieurs.",
                ]),
            ],
            [
                'slug' => 'facadier',
                'name' => 'Facadier',
                'schema_org_type' => 'GeneralContractor',
                'services' => self::services([
                    'Ravalement de facade' => "Renovation complete de l'aspect exterieur de la facade.",
                    'Nettoyage de facade' => 'Nettoyage haute pression ou biologique de la facade.',
                    'Traitement hydrofuge de facade' => "Protection de la facade contre l'humidite.",
                    'Peinture de facade' => 'Application de peinture exterieure adaptee.',
                    'Reparation de fissures' => 'Traitement des fissures superficielles ou structurelles.',
                    'Isolation thermique par l\'exterieur' => 'Pose d\'une isolation ITE avec finition de facade.',
                    'Enduit de facade' => "Application d'un enduit de protection et de finition.",
                    'Traitement anti-mousse' => 'Traitement contre les mousses et micro-organismes.',
                    'Refection de facade' => "Remise en etat complete d'une facade degradee.",
                    'Application d\'enduit decoratif' => "Pose d'enduits decoratifs pour sublimer la facade.",
                    'Traitement de l\'humidite' => "Diagnostic et traitement des remontees d'humidite.",
                ]),
            ],
            [
                'slug' => 'electricien',
                'name' => 'Electricien',
                'schema_org_type' => 'Electrician',
                'services' => self::services([
                    'Installation electrique' => "Installation electrique complete pour logement neuf.",
                    'Mise aux normes electriques' => 'Mise en conformite avec la norme NF C 15-100.',
                    'Depannage electrique' => "Intervention rapide en cas de panne electrique.",
                    'Renovation electrique' => "Renovation complete d'une installation vetuste.",
                    'Installation de tableau electrique' => "Pose ou remplacement d'un tableau electrique.",
                    'Pose de prises et interrupteurs' => "Installation et remplacement de prises et interrupteurs.",
                    'Installation de luminaires' => 'Pose de luminaires interieurs et exterieurs.',
                    'Borne de recharge vehicule electrique' => "Installation d'une borne de recharge a domicile.",
                    'Domotique' => "Installation de solutions domotiques pour la maison.",
                    'Diagnostic electrique' => "Verification complete de l'installation electrique.",
                    'Recherche de panne electrique' => "Localisation precise de l'origine d'une panne.",
                    'Mise en securite electrique' => "Intervention d'urgence pour securiser une installation.",
                ]),
            ],
            [
                'slug' => 'macon',
                'name' => 'Macon',
                'schema_org_type' => 'GeneralContractor',
                'services' => self::services([
                    'Construction de maison' => "Construction de maison individuelle de A a Z.",
                    'Extension de maison' => "Agrandissement de la surface habitable existante.",
                    'Construction de mur' => 'Construction de murs porteurs ou de cloture.',
                    'Renovation de maconnerie' => 'Reprise et renovation de structures maconnees.',
                    'Creation d\'ouverture' => "Creation d'ouvertures dans un mur porteur.",
                    'Construction de terrasse' => 'Realisation de terrasses maconnees.',
                    'Coulage de dalle beton' => 'Coulage de dalles beton pour sols et fondations.',
                    'Construction de garage' => "Construction de garages maconnes.",
                    'Pose de parpaings' => 'Montage de murs en parpaings.',
                    'Fondations' => "Realisation de fondations adaptees au terrain.",
                    'Reparation de fissures structurelles' => 'Diagnostic et reparation de fissures sur mur porteur.',
                ]),
            ],
            [
                'slug' => 'menuisier',
                'name' => 'Menuisier',
                'schema_org_type' => 'HomeAndConstructionBusiness',
                'services' => self::services([
                    'Pose de fenetres' => 'Installation de fenetres bois, PVC ou aluminium.',
                    'Pose de portes' => "Installation de portes interieures et exterieures.",
                    'Fabrication sur mesure' => "Conception et fabrication de menuiseries sur mesure.",
                    'Pose de volets' => 'Installation de volets battants ou roulants.',
                    'Installation de cuisine' => "Pose de cuisine equipee sur mesure.",
                    'Pose de parquet' => 'Installation de parquet massif, contrecolle ou stratifie.',
                    'Fabrication de mobilier' => 'Creation de mobilier bois sur mesure.',
                    'Pose de placards' => "Installation de placards et dressings sur mesure.",
                    'Installation de portails' => 'Pose de portails bois, PVC ou aluminium.',
                    'Renovation de menuiserie bois' => "Remise en etat de menuiseries bois existantes.",
                    'Pose de veranda' => "Installation de verandas sur mesure.",
                    'Installation d\'escalier' => "Fabrication et pose d'escaliers.",
                ]),
            ],
            [
                'slug' => 'isolation',
                'name' => "Specialiste de l'isolation",
                'schema_org_type' => 'HomeAndConstructionBusiness',
                'services' => self::services([
                    'Isolation des combles' => "Isolation thermique des combles perdus ou amenages.",
                    'Isolation des murs' => 'Isolation thermique des murs interieurs ou exterieurs.',
                    'Isolation par l\'exterieur' => 'Pose d\'une isolation thermique par l\'exterieur (ITE).',
                    'Isolation par l\'interieur' => "Pose d'une isolation thermique par l'interieur (ITI).",
                    'Isolation des planchers' => 'Isolation thermique des planchers bas.',
                    'Soufflage d\'isolant' => "Isolation par soufflage de laine minerale ou vegetale.",
                    'Isolation phonique' => 'Traitement acoustique des parois et plafonds.',
                    'Traitement des ponts thermiques' => 'Correction des points de deperdition de chaleur.',
                    'Isolation de toiture' => 'Isolation thermique sous toiture ou en sarking.',
                    'Isolation de garage' => "Isolation d'un garage attenant a l'habitation.",
                    'Diagnostic thermique' => "Etude des deperditions energetiques du logement.",
                ]),
            ],
            [
                'slug' => 'renovation-generale',
                'name' => 'Entreprise generale de renovation',
                'schema_org_type' => 'GeneralContractor',
                'services' => self::services([
                    'Renovation complete de maison' => "Renovation totale d'une maison, tous corps de metier.",
                    'Renovation d\'appartement' => "Renovation complete d'un appartement.",
                    'Renovation de cuisine' => "Renovation complete d'une cuisine.",
                    'Renovation de salle de bain' => "Renovation complete d'une salle de bain.",
                    'Renovation energetique' => 'Travaux visant a ameliorer la performance energetique.',
                    'Extension de maison' => "Agrandissement de la surface habitable.",
                    'Amenagement de combles' => 'Transformation de combles en piece habitable.',
                    'Renovation de sol' => 'Remplacement ou renovation des revetements de sol.',
                    'Peinture interieure' => "Travaux de peinture pour l'interieur du logement.",
                    'Coordination de travaux' => 'Pilotage et coordination de plusieurs corps de metier.',
                    'Renovation cle en main' => "Prise en charge complete du projet de renovation.",
                    'Transformation de garage' => "Amenagement d'un garage en piece habitable.",
                ]),
            ],
        ];
    }

    /**
     * @param array<string,string> $nameToDescription
     * @return array<int,array{slug:string,name:string,description:string}>
     */
    private static function services(array $nameToDescription): array
    {
        $services = [];
        foreach ($nameToDescription as $name => $description) {
            $services[] = [
                'slug' => \App\Support\Str::slug($name),
                'name' => $name,
                'description' => $description,
            ];
        }

        return $services;
    }
}
