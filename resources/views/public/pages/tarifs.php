<?php
/**
 * Page Grille tarifaire - HART TOITURE
 * Conforme à l’arrêté du 24 janvier 2017 & obligations d’affichage des prix.
 */
use App\Models\Media;

$name = (string)($company?->getAttribute('trade_name') ?: config('app.name', 'HART TOITURE'));
$phone = (string)($company?->getAttribute('phone') ?? '0693 83 80 54');
$phoneHref = preg_replace('/\D+/', '', $phone);
$city = (string)($company?->getAttribute('city') ?? 'Saint-Denis');
$hero = $company?->getAttribute('hero_media_id') ? Media::find((int)$company->getAttribute('hero_media_id'))?->getAttribute('url') : null;
?>
<?= view('public.partials.breadcrumbs', ['items' => $breadcrumbs ?? [
    ['label' => 'Accueil', 'url' => '/'],
    ['label' => 'Grille tarifaire', 'url' => null],
]]) ?>

<main class="tarifs-page">

  <!-- HÉRO DE LA PAGE -->
  <section class="tarifs-hero">
    <div class="tarifs-hero-bg">
      <?php if ($hero): ?><img src="<?= e($hero) ?>" alt="Travaux de toiture à La Réunion — <?= e($name) ?>" fetchpriority="high"><?php endif; ?>
    </div>
    <div class="tarifs-hero-shade" aria-hidden="true"></div>
    <div class="tarifs-hero-inner">
      <span class="hh-kicker">Transparence &amp; Clarté</span>
      <h1>Grille tarifaire des prestations de toiture à La Réunion</h1>
      <p class="tarifs-hero-lead">
        Chez <?= e($name) ?>, la transparence est totale : déplacement gratuit sur toute l'île, devis gratuit sans engagement et des tarifs indicatifs clairs avant toute intervention.
      </p>
      <div class="tarifs-highlights">
        <div class="th-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          <div><strong>Déplacement 100% gratuit</strong><span>Partout à La Réunion</span></div>
        </div>
        <div class="th-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          <div><strong>Devis détaillé sans engagement</strong><span>Remis sous 24h à 48h</span></div>
        </div>
        <div class="th-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          <div><strong>Urgences 24 h/24 &amp; 7 j/7</strong><span>Mise en sécurité rapide</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTENU DE LA GRILLE TARIFAIRE -->
  <section class="tarifs-section">
    <div class="container">

      <header class="tarifs-header">
        <span class="ph-eyebrow blue">Barème indicatif</span>
        <h2>Nos tarifs pour vos travaux de couverture et d’étanchéité</h2>
        <p>Les prix ci-dessous sont exprimés en euros TTC. Chaque toiture ayant ses spécificités (surface, pente, hauteur, état du support), un devis gratuit et précis est établi sur place avant le début de tout chantier.</p>
      </header>

      <div class="tarifs-cards-grid">

        <!-- Carte 1 : Diagnostic & Urgence -->
        <article class="tarifs-card">
          <div class="tc-header tc-header-urg">
            <span class="tc-badge">Disponible 24h/24</span>
            <h3>Diagnostic &amp; Recherche de fuite</h3>
            <p>Intervention rapide en cas d'infiltration d'eau ou de sinistre.</p>
          </div>
          <ul class="tc-list">
            <li>
              <div>
                <strong>Déplacement à domicile / sur site</strong>
                <small>Dans les 24 communes de La Réunion</small>
              </div>
              <span class="tc-price tc-free">Gratuit</span>
            </li>
            <li>
              <div>
                <strong>Diagnostic toiture &amp; Devis détaillé</strong>
                <small>Inspection minutieuse de la couverture</small>
              </div>
              <span class="tc-price tc-free">Gratuit</span>
            </li>
            <li>
              <div>
                <strong>Recherche de fuite &amp; mise en sécurité</strong>
                <small>Bâchage d'urgence, colmatage provisoire</small>
              </div>
              <span class="tc-price">Dès 150 € TTC</span>
            </li>
            <li>
              <div>
                <strong>Réparation ponctuelle de tôle</strong>
                <small>Remplacement de fixations, pontets, reprises ciblées</small>
              </div>
              <span class="tc-price">Dès 180 € TTC</span>
            </li>
          </ul>
          <div class="tc-footer">
            <a href="#demande-devis" class="tc-btn">Demander un diagnostic</a>
          </div>
        </article>

        <!-- Carte 2 : Nettoyage & Démoussage -->
        <article class="tarifs-card">
          <div class="tc-header">
            <span class="tc-badge tc-badge-blue">Entretien courant</span>
            <h3>Nettoyage &amp; Démoussage</h3>
            <p>Élimination des mousses, lichens, débris végétaux et salissures.</p>
          </div>
          <ul class="tc-list">
            <li>
              <div>
                <strong>Nettoyage toiture en tôle</strong>
                <small>Basse pression adaptée pour préserver le support</small>
              </div>
              <span class="tc-price">Dès 18 € / m² TTC</span>
            </li>
            <li>
              <div>
                <strong>Traitement fongicide &amp; anti-mousse</strong>
                <small>Pulvérisation produit curatif et préventif longue durée</small>
              </div>
              <span class="tc-price">Dès 15 € / m² TTC</span>
            </li>
            <li>
              <div>
                <strong>Nettoyage toiture-terrasse / dalle béton</strong>
                <small>Décrassage et préparation pour étanchéité</small>
              </div>
              <span class="tc-price">Dès 20 € / m² TTC</span>
            </li>
            <li>
              <div>
                <strong>Nettoyage et dégagement de gouttières</strong>
                <small>Évacuation des feuilles, vérification écoulement</small>
              </div>
              <span class="tc-price">Dès 120 € TTC</span>
            </li>
          </ul>
          <div class="tc-footer">
            <a href="#demande-devis" class="tc-btn">Obtenir un devis nettoyage</a>
          </div>
        </article>

        <!-- Carte 3 : Traitement Antirouille & Peinture -->
        <article class="tarifs-card">
          <div class="tc-header tc-header-gold">
            <span class="tc-badge tc-badge-gold">Protection longue durée</span>
            <h3>Antirouille &amp; Peinture toiture</h3>
            <p>Protection contre la corrosion marine, les UV et les fortes pluies.</p>
          </div>
          <ul class="tc-list">
            <li>
              <div>
                <strong>Traitement curatif de la rouille</strong>
                <small>Brossage métallique + application convertisseur de rouille</small>
              </div>
              <span class="tc-price">Dès 22 € / m² TTC</span>
            </li>
            <li>
              <div>
                <strong>Peinture protectrice pour toiture tôle</strong>
                <small>Primaire d'accroche + 2 couches de finition résistante</small>
              </div>
              <span class="tc-price">Dès 28 € / m² TTC</span>
            </li>
            <li>
              <div>
                <strong>Pack Rénovation Complète Tôle</strong>
                <small>Nettoyage + Antirouille + Peinture haute protection</small>
              </div>
              <span class="tc-price">Dès 45 € / m² TTC</span>
            </li>
          </ul>
          <div class="tc-footer">
            <a href="#demande-devis" class="tc-btn">Chiffrer ma rénovation</a>
          </div>
        </article>

        <!-- Carte 4 : Étanchéité & Gros Travaux -->
        <article class="tarifs-card">
          <div class="tc-header">
            <span class="tc-badge tc-badge-blue">Garantie étanchéité</span>
            <h3>Étanchéité toits-terrasses</h3>
            <p>Protection des dalles béton, terrasses et acrotères contre les infiltrations.</p>
          </div>
          <ul class="tc-list">
            <li>
              <div>
                <strong>Traitement des fissures &amp; points singuliers</strong>
                <small>Pontage résine armée et mastics d'étanchéité</small>
              </div>
              <span class="tc-price">Dès 190 € TTC</span>
            </li>
            <li>
              <div>
                <strong>Système d'étanchéité liquide (SEL / résine)</strong>
                <small>Application membrane continue sans joint</small>
              </div>
              <span class="tc-price">Dès 48 € / m² TTC</span>
            </li>
            <li>
              <div>
                <strong>Rénovation complète d’étanchéité</strong>
                <small>Dépose ancien complexe, pare-vapeur, membrane</small>
              </div>
              <span class="tc-price">Sur devis gratuit</span>
            </li>
          </ul>
          <div class="tc-footer">
            <a href="#demande-devis" class="tc-btn">Étudier mon projet d’étanchéité</a>
          </div>
        </article>

      </div>

      <!-- TAUX HORAIRE DE MAIN D'ŒUVRE -->
      <div class="tarifs-taux-horaire">
        <div class="th-content">
          <div class="th-title">
            <span class="th-ico">⏱️</span>
            <div>
              <h3>Taux horaire de main d’œuvre</h3>
              <p>Applicable aux interventions hors forfait ou travaux spécifiques au temps passé.</p>
            </div>
          </div>
          <div class="th-rate">
            <span class="rate-val">55 € TTC</span>
            <span class="rate-unit">/ heure (hors fournitures)</span>
          </div>
        </div>
      </div>

      <!-- CADRE RÉGLEMENTAIRE & OBLIGATIONS LÉGALES -->
      <div class="tarifs-reglementation">
        <h3>Information préalable relative aux prix (Arrêté du 24 janvier 2017)</h3>
        <p>Conformément aux dispositions de l'arrêté du 24 janvier 2017 relatif à la publicité des prix des prestations de dépannage, de réparation et d'entretien dans le secteur du bâtiment et de l'équipement de la maison :</p>
        <ul>
          <li><strong>Gratuité du devis :</strong> Un devis détaillé, personnalisé et gratuit est obligatoirement remis au client préalablement à toute intervention. Aucun travail n'est engagé sans votre validation préalable et signée.</li>
          <li><strong>Frais de déplacement :</strong> Nos déplacements pour l'établissement de diagnostics et devis sont gratuits sur l'ensemble de l'île de La Réunion.</li>
          <li><strong>Modalités de décompte du temps :</strong> Toute heure entamée pour des travaux au taux horaire fait l'objet d'une estimation préalable communiquée au client. Les travaux forfaitaires sont facturés sur la base du montant convenu au devis.</li>
          <li><strong>Prix TTC :</strong> L'ensemble des prix présentés sont exprimés Toutes Taxes Comprises (TTC).</li>
        </ul>
      </div>

      <!-- BLOC MÉDIATEUR DE LA CONSOMMATION -->
      <div class="tarifs-mediateur" id="mediateur">
        <div class="tm-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div class="tm-content">
          <span class="ph-eyebrow blue">Dispositif légal</span>
          <h3>Médiation de la consommation</h3>
          <p>
            Conformément aux articles L. 616-1 et R. 616-1 du Code de la consommation, notre entreprise est affiliée au dispositif de médiation de la consommation auprès du médiateur agréé par la CECMC (Commission d'Évaluation et de Contrôle de la Médiation de la Consommation) :
          </p>
          <div class="tm-details">
            <div class="tm-entity">
              <strong>Société Médiation Professionnelle (SMP)</strong>
              <span>Médiation de la consommation — Président : Jean-Louis Lascoux</span>
              <span>24 rue Albert de Mun, 33000 Bordeaux (SIRET : 814 385 357 00011)</span>
            </div>
            <div class="tm-actions">
              <a href="https://www.mediateur-consommation-smp.fr" target="_blank" rel="noopener noreferrer" class="tm-btn">
                Saisir le médiateur en ligne →
              </a>
            </div>
          </div>
          <small class="tm-note">
            En cas de litige qui n'aurait pu être résolu dans le cadre d'une réclamation préalable écrite adressée à <?= e($name) ?>, le consommateur peut faire appel gratuitement au médiateur de la consommation par voie électronique sur son site internet ou par courrier postal à l'adresse ci-dessus.
          </small>
        </div>
      </div>

    </div>
  </section>

  <!-- FORMULAIRE DE DEVIS -->
  <section class="tarifs-form-section" id="demande-devis">
    <?= view('public.partials.quote_card', ['company' => $company]) ?>
  </section>

</main>
