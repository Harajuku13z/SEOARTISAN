<?php
use App\Models\CompanyService;
use App\Models\Media;
use App\Models\Project;
use App\Models\Testimonial;

$name=(string)($company?->getAttribute('trade_name')?:config('app.name','Votre artisan'));
$phone=(string)($company?->getAttribute('phone')??'');
$email=(string)($company?->getAttribute('public_email')??'');
$address=trim(implode(' ',array_filter([(string)$company?->getAttribute('address'),(string)$company?->getAttribute('postal_code'),(string)$company?->getAttribute('city')])));
$description=(string)($company?->getAttribute('editorial_presentation')?:$company?->getAttribute('short_description'));
$social=(array)($company?->getAttribute('social_links')??[]);
$services=CompanyService::all('sort_order ASC');
$projects=Project::visible();
$reviews=array_slice(Testimonial::visible(),0,3);
$mediaUrl=static fn($id)=>$id?Media::find((int)$id)?->getAttribute('url'):null;
?>
<main class="contact-reference">
  <section class="contact-reference-inner">
    <span class="contact-eyebrow">Prise de contact</span>
    <h1>Devis gratuit sous 24h<br>et sans engagement</h1>
    <p class="contact-intro">Rendez-vous avec nos experts afin d’évaluer au mieux vos besoins réels.</p>

    <?php /*
    <div class="contact-layout">
      <div class="contact-form-card">
        <div data-form-success class="contact-success" style="display:none"><strong>Merci !</strong><p>Votre demande a bien été envoyée. Notre équipe vous recontacte rapidement.</p></div>
        <form method="post" action="/contact" data-ajax-form>
          <?= csrf_field() ?><input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">
          <div data-form-error class="alert-error" style="display:none"></div>
          <div class="contact-form-row"><label>Nom<input name="name" placeholder="Votre nom" required></label><label>Téléphone<input type="tel" name="phone" placeholder="Votre téléphone" required></label></div>
          <label>Email<input type="email" name="email" placeholder="Votre email" required></label>
          <div class="contact-form-row"><label>Code postal<input name="postal_code" placeholder="Votre code postal"></label><label>Ville<input name="city" placeholder="Votre ville"></label></div>
          <label>Quelle prestation souhaitez-vous ?<select name="company_service_id"><option value="">Choisissez une prestation</option><?php
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
foreach($services as $service): if(!$service->getAttribute('is_active'))continue; if(!in_array($service->getAttribute('public_name'),$allowedNeeds,true))continue; ?><option value="<?= (int)$service->id() ?>"><?= e($service->getAttribute('public_name')) ?></option><?php endforeach; ?><option value="">Autre demande</option></select></label>
          <label>Votre projet<textarea name="message" rows="4" placeholder="Décrivez votre projet"></textarea></label>
          <label>Créneau de rappel souhaité<select name="time_slot" required><option value="">Choisissez un créneau</option><option value="8h30-10h">8h30 - 10h</option><option value="10h-13h">10h - 13h</option><option value="13h-15h">13h - 15h</option><option value="15h-18h">15h - 18h</option></select></label>
          <button type="submit">Envoyer votre demande</button>
        </form>
      </div>

      <aside class="contact-details">
        <div><h2><?= e($name) ?></h2><p><?= e($description) ?></p><p>Nous proposons des équipements de marques reconnues et garanties en France afin de sécuriser votre investissement sur le long terme.</p></div>
        <div class="contact-coordinates">
          <?php if($address): ?><div><span>⌖</span><p><?= e($address) ?></p></div><?php endif; ?>
          <?php if($phone): ?><div><span>☎</span><a href="tel:<?= e(preg_replace('/\D+/','',$phone)) ?>"><?= e($phone) ?><small>Installation · entretien · dépannage</small></a></div><?php endif; ?>
          <?php if($email): ?><div><span>✉</span><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div><?php endif; ?>
        </div>
        <?php if(!empty($social['facebook'])||!empty($social['instagram'])): ?><div class="contact-socials"><?php if(!empty($social['facebook'])): ?><a href="<?= e($social['facebook']) ?>" target="_blank" rel="noopener">f</a><?php endif; ?><?php if(!empty($social['instagram'])): ?><a href="<?= e($social['instagram']) ?>" target="_blank" rel="noopener">◎</a><?php endif; ?></div><?php endif; ?>
      </aside>
    </div>
    */ ?>
    <?= view('public.partials.quote_card', ['company' => $company, 'currentService' => null]) ?>
  </section>

  <?php if($projects): ?>
  <section class="contact-showcase contact-projects">
    <div class="contact-showcase-inner"><span class="contact-eyebrow">Nos réalisations</span><h2>Découvrez nos derniers chantiers</h2>
      <div class="contact-project-grid"><?php foreach(array_slice($projects,0,6) as $project): $image=$mediaUrl($project->getAttribute('after_media_id'))?:$mediaUrl($project->getAttribute('before_media_id')); if(!$image)continue; ?><figure><img src="<?= e($image) ?>" alt="<?= e($project->getAttribute('title')) ?>" loading="lazy"><figcaption><?= e($project->getAttribute('title')) ?></figcaption></figure><?php endforeach; ?></div>
      <a class="contact-more" href="/realisations">Voir toutes nos réalisations →</a>
    </div>
  </section>
  <?php endif; ?>

  <?php if($reviews): ?>
  <section class="contact-showcase contact-reviews">
    <div class="contact-showcase-inner"><span class="contact-eyebrow">Avis clients</span><h2>Ils nous ont fait confiance</h2>
      <div class="contact-review-grid"><?php foreach($reviews as $review): ?><article><div class="contact-stars"><?= str_repeat('★',max(1,(int)$review->getAttribute('rating'))) ?></div><p>« <?= e($review->getAttribute('content')) ?> »</p><strong><?= e($review->getAttribute('author_name')) ?></strong></article><?php endforeach; ?></div><a class="reviews-more-button" href="/avis-clients">Voir tous les avis →</a>
    </div>
  </section>
  <?php endif; ?>
</main>
