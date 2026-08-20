<?php
use App\Core\Database;
use App\Models\Media;
use App\Models\Project;
use App\Models\Testimonial;
use App\Repositories\CompanyServiceRepository;

$name = (string) ($company?->getAttribute('trade_name') ?: config('app.name'));
$phone = (string) ($company?->getAttribute('phone') ?? '');
$phoneHref = preg_replace('/\D+/', '', $phone);
$email = (string) ($company?->getAttribute('public_email') ?? '');
$address = trim(implode(', ', array_filter([
    $company?->getAttribute('address'),
    trim((string) $company?->getAttribute('postal_code') . ' ' . (string) $company?->getAttribute('city')),
])));
$h1 = (string) ($page->getAttribute('h1') ?: $company?->getAttribute('slogan') ?: $name);
$intro = (string) ($company?->getAttribute('short_description') ?: $company?->getAttribute('long_description') ?: 'Une expertise locale et des interventions soignees pour tous vos projets.');
$about = (string) ($company?->getAttribute('editorial_presentation') ?: $company?->getAttribute('long_description') ?: $intro);
$services = $company ? (new CompanyServiceRepository(Database::instance()))->forCompany((int) $company->id(), true) : [];
$projects = Project::visible();
$testimonials = Testimonial::visibleGoogle();
$reviews = array_slice($testimonials, 0, 3);
$rated = array_values(array_filter($testimonials, static fn ($t) => (int) $t->getAttribute('rating') > 0));
$average = $rated ? array_sum(array_map(static fn ($t) => (int) $t->getAttribute('rating'), $rated)) / count($rated) : null;
$googleCount = count(array_filter($testimonials, static fn ($t) => $t->getAttribute('source') === 'google'));
$certifications = (array) ($company?->getAttribute('certifications') ?? []);
$brands = array_values(array_filter(array_map('trim', preg_split('/[,;\n]/', (string) ($company?->getAttribute('editorial_brands_used') ?? '')) ?: [])));
$years = $company?->getAttribute('founded_year') ? max(0, (int) date('Y') - (int) $company->getAttribute('founded_year')) : null;
$radius = $company?->getAttribute('service_radius_km');

$mediaUrl = static fn ($id): ?string => $id ? Media::find((int) $id)?->getAttribute('url') : null;
$logo = $mediaUrl($company?->getAttribute('logo_main_media_id'))
    ?: $mediaUrl($company?->getAttribute('logo_dark_media_id'))
    ?: $mediaUrl($company?->getAttribute('logo_light_media_id'));
$slides = [];
if ($url = $mediaUrl($company?->getAttribute('hero_media_id'))) $slides[] = ['url' => $url, 'caption' => $name];
foreach (Media::where(['type' => 'gallery'], 'created_at DESC', 3) as $media) {
    $url = $media->getAttribute('url');
    if ($url && !in_array($url, array_column($slides, 'url'), true)) $slides[] = ['url' => $url, 'caption' => $media->getAttribute('alt_text') ?: $name];
}
$projectMedia = [];
foreach ($projects as $project) {
    $before = $mediaUrl($project->getAttribute('before_media_id'));
    $after = $mediaUrl($project->getAttribute('after_media_id'));
    if ($before || $after) $projectMedia[] = compact('project', 'before', 'after');
}
$trust = [];
if ($company?->getAttribute('offers_free_quote')) $trust[] = 'Devis gratuit et sans engagement';
if ($certifications) $trust[] = 'Artisan certifie ' . $certifications[0];
if ($company?->getAttribute('offers_emergency')) $trust[] = 'Intervention rapide';
if ($years) $trust[] = $years . " ans d'experience";
?>
<div class="design-home">
  <header class="dh-header">
    <a href="#top" class="dh-brand"><span><?php if ($logo): ?><img src="<?= e($logo) ?>" alt="Logo <?= e($name) ?>"><?php else: ?><?= e(mb_strtoupper(mb_substr($name, 0, 2))) ?><?php endif; ?></span><strong><?= e($name) ?></strong></a>
    <nav class="site-menu"><?php if (!empty($siteMenu)): foreach ($siteMenu as $item): ?><div class="nav-item <?= !empty($item['children'])?'has-children':'' ?>"><a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a><?php if (!empty($item['children'])): ?><div class="sub-menu"><?php foreach ($item['children'] as $child): ?><a href="<?= e($child['url']) ?>"><?= e($child['label']) ?></a><?php endforeach; ?></div><?php endif; ?></div><?php endforeach; else: ?><a href="#services">Prestations</a><a href="#realisations">Réalisations</a><a href="#avis">Avis clients</a><a href="#contact">Contact</a><?php endif; ?></nav>
    <?php if ($phone): ?><a class="dh-phone" href="tel:<?= e($phoneHref) ?>"><i></i><?= e($phone) ?></a><?php endif; ?>
  </header>

  <section class="dh-hero" id="top">
    <div class="dh-hero-copy">
      <?php if ($average !== null): ?><div class="dh-rating"><span><?= str_repeat('★', 5) ?></span><b><?= number_format($average, 1, ',', '') ?>/5<?= $googleCount ? ' — ' . $googleCount . ' avis Google' : '' ?></b></div><?php endif; ?>
      <h1><?= e($h1) ?></h1>
      <p><?= e($intro) ?></p>
      <?php if ($services): ?>
      <div class="dh-project-picker"><label for="project-search">Quel est votre projet ?</label><div><input id="project-search" list="service-suggestions" placeholder="Par exemple : <?= e($services[0]['public_name']) ?>"><a href="#contact">→</a></div><datalist id="service-suggestions"><?php foreach ($services as $service): ?><option value="<?= e($service['public_name']) ?>"><?php endforeach; ?></datalist></div>
      <?php endif; ?>
      <div class="dh-actions"><a class="dh-btn-dark" href="#contact">Demander un devis gratuit</a><?php if ($company?->getAttribute('offers_emergency') && $phone): ?><a class="dh-btn-light" href="tel:<?= e($phoneHref) ?>">Urgence dépannage</a><?php endif; ?></div>
      <div class="dh-stats"><?php if ($years): ?><div><b><?= $years ?>+</b><span>ans d’expérience</span></div><?php endif; ?><?php if ($radius): ?><div><b><?= (int) $radius ?> km</b><span>zone d’intervention</span></div><?php endif; ?><?php if ($certifications): ?><div><b><?= e($certifications[0]) ?></b><span>artisan certifié</span></div><?php endif; ?></div>
    </div>
    <div class="dh-slider" data-hero-slider>
      <?php foreach ($slides as $i => $slide): ?><div class="slide <?= $i === 0 ? 'active' : '' ?>"><img src="<?= e($slide['url']) ?>" alt="<?= e($slide['caption']) ?>"><span><?= e($slide['caption']) ?></span></div><?php endforeach; ?>
      <?php if (!$slides): ?><div class="dh-image-placeholder">Ajoutez vos photos depuis la médiathèque</div><?php endif; ?>
      <?php if (count($slides) > 1): ?><div class="dots"><?php foreach ($slides as $i => $_): ?><button type="button" data-slide-index="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Photo <?= $i + 1 ?>"></button><?php endforeach; ?></div><?php endif; ?>
    </div>
  </section>

  <?php if ($trust): ?><section class="dh-trust"><?php foreach ($trust as $item): ?><span><i></i><?= e($item) ?></span><?php endforeach; ?></section><?php endif; ?>

  <?php if ($services): ?><section class="dh-section" id="services"><div class="dh-section-head"><div><small>Nos prestations</small><h2>Nos expertises, un seul interlocuteur</h2></div><p>Une équipe qualifiée, technique et proche de vous, de l’installation à l’entretien.</p></div><div class="dh-services"><?php foreach (array_slice($services, 0, 4) as $service): ?><a href="/<?= e($service['slug']) ?>"><div class="dh-card-image"><?php if ($service['image_url']): ?><img src="<?= e($service['image_url']) ?>" alt="<?= e($service['image_alt'] ?: $service['public_name']) ?>"><?php endif; ?></div><div><strong><?= e($service['public_name']) ?></strong><p><?= e($service['description'] ?? '') ?></p><span>En savoir plus →</span></div></a><?php endforeach; ?></div>
    <div class="dh-about"><div><small>À propos, express</small><h3>Une entreprise locale, à taille humaine</h3><p><?= e($about) ?></p><div class="dh-stats"><?php if ($company?->getAttribute('offers_emergency')): ?><div><b>24/24</b><span>dépannage & entretien</span></div><?php endif; ?><?php if ($radius): ?><div><b><?= (int) $radius ?> km</b><span>autour de <?= e($company?->getAttribute('city')) ?></span></div><?php endif; ?></div></div><div><?php if ($slides): ?><img src="<?= e($slides[0]['url']) ?>" alt="<?= e($name) ?> en intervention"><?php endif; ?></div></div>
  </section><?php endif; ?>

  <?php if ($projectMedia): ?><section class="dh-section" id="realisations"><small>Réalisations</small><h2>Des chantiers menés près de chez vous</h2><p class="dh-intro">Avant / après et aperçu de nos interventions récentes.</p><div class="dh-projects"><?php foreach (array_slice($projectMedia, 0, 6) as $item): ?><figure><?php if ($item['before'] && $item['after']): ?><div class="dh-before-after"><span><img src="<?= e($item['before']) ?>" alt="Avant"><b>Avant</b></span><span><img src="<?= e($item['after']) ?>" alt="Après"><b>Après</b></span></div><?php else: ?><img src="<?= e($item['after'] ?: $item['before']) ?>" alt="<?= e($item['project']->getAttribute('title')) ?>"><?php endif; ?><figcaption><?= e($item['project']->getAttribute('title')) ?></figcaption></figure><?php endforeach; ?></div></section><?php endif; ?>

  <?php if ($reviews): ?><section class="dh-reviews" id="avis"><div class="dh-section-head"><div><small>Avis clients</small><h2>Ce que nos clients en pensent</h2></div><?php if ($average !== null): ?><div class="dh-google"><b>G</b><span class="stars"><?= str_repeat('★', 5) ?></span><strong><?= number_format($average, 1, ',', '') ?>/5</strong><span>· <?= count($testimonials) ?> avis</span></div><?php endif; ?></div><div class="dh-review-grid"><?php foreach ($reviews as $review): $rating=(int)$review->getAttribute('rating'); ?><article><?php if ($rating): ?><div class="stars"><?= str_repeat('★', $rating) ?></div><?php endif; ?><p>“<?= e($review->getAttribute('content')) ?>”</p><div class="dh-reviewer"><b><?= e(mb_strtoupper(mb_substr((string)$review->getAttribute('author_name'),0,1))) ?></b><span><strong><?= e($review->getAttribute('author_name')) ?></strong><small><?= e($review->getAttribute('role_or_service') ?: ($review->getAttribute('source') === 'google' ? 'Avis Google' : 'Client')) ?></small></span></div></article><?php endforeach; ?></div><a class="reviews-more-button" href="/avis-clients">Voir tous les avis →</a></section><?php endif; ?>

  <section class="dh-contact" id="contact"><div><small>Devis gratuit</small><h2>Un projet ? Parlons-en.</h2><p>Décrivez votre besoin, nous revenons vers vous rapidement avec une réponse claire et sans engagement.</p><ul><li>Réponse rapide</li><li>Devis gratuit et sans engagement</li><?php if ($company?->getAttribute('offers_emergency')): ?><li>Urgences prises en charge</li><?php endif; ?></ul><?php if ($phone): ?><a href="tel:<?= e($phoneHref) ?>">☎ <?= e($phone) ?></a><?php endif; ?></div><div class="form-card"><div data-form-success style="display:none" class="form-success"><div class="check">✓</div><h3>Merci, votre demande est envoyée !</h3><p>Nous vous recontactons rapidement.</p></div><form method="post" action="/devis" data-ajax-form><?= csrf_field() ?><input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off"><h3>Recevoir mon devis gratuit</h3><div data-form-error class="alert-error" style="display:none"></div><div class="form-row"><input name="name" placeholder="Nom et prénom" required><input type="tel" name="phone" placeholder="Téléphone" required></div><input type="email" name="email" placeholder="Adresse email"><div class="form-row"><input name="postal_code" placeholder="Code postal"><input name="city" placeholder="Ville"></div><select name="company_service_id"><option value="">Service souhaité</option><?php
$allowedNeeds = [
    "Installation adoucisseur Sel ou CO2",
    "Entretient adoucisseur sel ou CO2",
    "Installation pompe a chaleur air/air",
    "Entretien pompe a chaleur air/air",
    "Remplacement pompe a chaleur air/air",
    "Dépannage pompe a chaleur air/air",
    "Installation pompe a chaleur air/eau",
    "entretien pompe a chaleur air/eau",
    "Dépannage pompe a chaleur air/eau",
    "Remplacement pompe a chaleur air/eau",
    "Installation chaudiere",
    "Dépannage chaudiere",
    "Entretien chaudiere",
    "Remplacement chaudière",
    "Installation ballon thermodynamique",
    "Entretien ballon thermodynamique",
    "Dépannage ballon thermodynamique",
    "Remplacement ballon thermodynamique",
    "Contrat d’entretien pompe a chaleur air/air",
    "Contrat d’entretien pompe a chaleur air/eau",
    "Contrat d’entretien chaudière"
];
foreach ($services as $service): if(!in_array($service['public_name'],$allowedNeeds,true))continue; ?><option value="<?= (int)$service['id'] ?>"><?= e($service['public_name']) ?></option><?php endforeach; ?></select><select name="time_slot"><option value="">Être rappelé(e) plutôt…</option><option value="8h30-10h">8h30 - 10h</option><option value="10h-13h">10h - 13h</option><option value="13h-15h">13h - 15h</option><option value="15h-18h">15h - 18h</option></select><textarea name="message" rows="3" placeholder="Décrivez votre projet…"></textarea><button type="submit">Recevoir mon devis gratuit</button><small>Vos données ne sont utilisées que pour vous répondre.</small></form></div></section>

  <?php if ($brands): ?><section class="dh-brands"><small>Marques & partenaires installés</small><div><?php foreach ($brands as $brand): ?><strong><?= e($brand) ?></strong><?php endforeach; ?></div></section><?php endif; ?>
  <?php if ($certifications): ?><section class="dh-certs"><?php foreach ($certifications as $cert): ?><article><b><?= e(mb_strtoupper(mb_substr((string)$cert,0,2))) ?></b><span><strong><?= e($cert) ?></strong><small>Qualification professionnelle</small></span></article><?php endforeach; ?></section><?php endif; ?>
  <footer class="dh-footer"><div><div><div class="dh-brand"><span><?php if ($logo): ?><img src="<?= e($logo) ?>" alt="Logo <?= e($name) ?>"><?php else: ?><?= e(mb_strtoupper(mb_substr($name,0,2))) ?><?php endif; ?></span><strong><?= e($name) ?></strong></div><p><?= e($address) ?></p></div><div><strong>Nos catégories</strong><?php foreach (($siteMenu ?? []) as $category): ?><a href="<?= e($category['url']) ?>"><?= e($category['label']) ?></a><?php endforeach; ?></div><div><strong>Contact</strong><?php if ($phone): ?><a href="tel:<?= e($phoneHref) ?>"><?= e($phone) ?></a><?php endif; ?><?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?></div></div><p>© <?= date('Y') ?> <?= e($name) ?> — Tous droits réservés</p></footer>
</div>
