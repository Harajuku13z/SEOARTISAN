<?php
use App\Models\CompanyService;use App\Models\Media;use App\Models\Page;use App\Models\Project;use App\Models\Setting;use App\Models\Testimonial;
$name=(string)($company?->getAttribute('trade_name')?:config('app.name','Votre artisan'));
$phone=(string)($company?->getAttribute('phone')??'');$phoneHref=preg_replace('/\D+/','',$phone);
$email=(string)($company?->getAttribute('public_email')??'');
$city=(string)($company?->getAttribute('city')??'');
$h1=(string)($page->getAttribute('h1')?:('Expert en rénovation et entretien de votre habitat'.($city!==''?' en Île-de-France':'')));
$intro=(string)($company?->getAttribute('short_description')?:'Une expertise locale et des interventions soignées pour tous vos projets.');
$aboutText=(string)($company->getAttribute('long_description') ?: $company->getAttribute('editorial_presentation') ?: $intro);
$hero=$company?->getAttribute('hero_media_id')?Media::find((int)$company->getAttribute('hero_media_id'))?->getAttribute('url'):null;
$mediaUrl=static fn($id)=>$id?Media::find((int)$id)?->getAttribute('url'):null;
$legacyHero=null;foreach(Media::where(['url'=>'/uploads/homepage/a-propos.jpeg']) as $m){$legacyHero=$m->getAttribute('url');break;}
$aboutImage=$legacyHero!==null?$legacyHero:$hero;
$services=array_values(array_filter(CompanyService::all('sort_order ASC'),static fn($service)=>(bool)$service->getAttribute('is_active')));
$projects=array_slice(Project::visible(),0,3);
$allReviews=Testimonial::visible();
$reviews=array_slice($allReviews,0,3);
$ratedAll=array_values(array_filter($allReviews,static fn($t)=>(int)$t->getAttribute('rating')>0));
$average=$ratedAll?array_sum(array_map(static fn($t)=>(int)$t->getAttribute('rating'),$ratedAll))/count($ratedAll):null;
$googleCount=count(array_filter($allReviews,static fn($t)=>$t->getAttribute('source')==='google'));
$certifications=(array)($company?->getAttribute('certifications')??[]);
$years=$company?->getAttribute('founded_year')?max(0,(int)date('Y')-(int)$company->getAttribute('founded_year')):null;
$radius=$company?->getAttribute('service_radius_km');
$defaults=['hero_eyebrow'=>'Artisan local','services_eyebrow'=>'Nos prestations','services_title'=>'Des services adaptés à vos besoins','about_eyebrow'=>'Qui sommes-nous','about_title'=>'Un savoir-faire de proximité','projects_eyebrow'=>'Nos réalisations','projects_title'=>'Des chantiers menés avec soin','reviews_eyebrow'=>'Avis clients','reviews_title'=>'La satisfaction de nos clients','blog_eyebrow'=>'Conseils & expertise','blog_title'=>'Nos derniers articles'];
$copy=array_merge($defaults,json_decode((string)(Setting::first(['key'=>'content.home_copy'])?->getAttribute('value')??'{}'),true)?:[]);
$stats=[];
if($years!==null&&$years>0)$stats[]=['value'=>$years.'+','label'=>'ans d\'expérience'];
if((int)$radius>0)$stats[]=['value'=>(int)$radius.' km','label'=>'de rayon d\'intervention'];
if($certifications)$stats[]=['value'=>(string)$certifications[0],'label'=>'artisan qualifié'];
if($average!==null)$stats[]=['value'=>number_format($average,1,',',' ').'/5','label'=>'avis clients Google'];
$postImage=static fn(array $post):?string=>is_string($post['_embedded']['wp:featuredmedia'][0]['source_url']??null)?$post['_embedded']['wp:featuredmedia'][0]['source_url']:null;
?>
<main class="jt-home">

  <section class="jt-hero" id="top">
    <div class="jt-wrap jt-hero-grid">
      <div class="jt-hero-copy">
        <?php if($average!==null): ?>
        <span class="jt-rating-chip"><span class="jt-stars" aria-hidden="true">★★★★★</span><b><?= e(number_format($average,1,',','')) ?>/5</b><small><?= $googleCount>0?'· '.$googleCount.' avis Google':'· avis clients' ?></small></span>
        <?php endif; ?>
        <h1><?= e($h1) ?></h1>
        <p class="jt-hero-sub"><?= e($intro) ?></p>
        <div class="jt-hero-actions">
          <a class="jt-btn jt-btn-primary" href="#devis">Demander un devis gratuit</a>
          <?php if($phone!==''): ?><a class="jt-btn jt-btn-ghost" href="tel:<?= e($phoneHref) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
            <?= e($phone) ?></a><?php endif; ?>
        </div>
        <ul class="jt-hero-trust">
          <li><span aria-hidden="true">✓</span>Devis gratuit &amp; sans engagement</li>
          <?php if($company?->getAttribute('offers_emergency')): ?><li><span aria-hidden="true">✓</span>Intervention rapide</li><?php endif; ?>
          <?php if($city!==''): ?><li><span aria-hidden="true">✓</span>Artisan local à <?= e($city) ?></li><?php endif; ?>
        </ul>
      </div>
      <div class="jt-hero-visual">
        <?php if($hero): ?>
          <img src="<?= e($hero) ?>" alt="<?= e($name) ?> — toiture traitée avec soin" fetchpriority="high">
        <?php else: ?>
          <div class="jt-hero-placeholder" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($name,0,2))) ?></div>
        <?php endif; ?>
        <div class="jt-float jt-float-quote"><span class="jt-float-icon" aria-hidden="true">✓</span><span><strong>Devis gratuit</strong><small>Réponse rapide garantie</small></span></div>
        <?php if($average!==null): ?>
        <div class="jt-float jt-float-google"><b aria-hidden="true">G</b><span><span class="jt-stars" aria-hidden="true">★★★★★</span><small><?= e(number_format($average,1,',','')) ?>/5<?= $googleCount>0?' — '.$googleCount.' avis':'' ?></small></span></div>
        <?php endif; ?>
      </div>
    </div>
    <?php if($stats): ?>
    <div class="jt-wrap">
      <dl class="jt-stats">
        <?php foreach($stats as $stat): ?><div><dt><?= e($stat['value']) ?></dt><dd><?= e($stat['label']) ?></dd></div><?php endforeach; ?>
      </dl>
    </div>
    <?php endif; ?>
  </section>

  <?php if($services): ?>
  <section class="jt-section jt-services" id="services">
    <div class="jt-wrap">
      <div class="jt-section-head">
        <div>
          <span class="jt-eyebrow"><?= e($copy['services_eyebrow']) ?></span>
          <h2><?= e($copy['services_title']) ?></h2>
        </div>
        <p>Un seul interlocuteur pour l'entretien comme pour la rénovation, du diagnostic à la finition.</p>
      </div>
      <div class="jt-bento">
        <?php foreach($services as $index=>$service):$image=$mediaUrl($service->getAttribute('image_media_id')); ?>
        <a class="jt-service-card<?= $index===0?' jt-span2':'' ?>" href="/<?= e($service->getAttribute('slug')) ?>">
          <span class="jt-service-media"><?php if($image): ?><img src="<?= e($image) ?>" alt="<?= e($service->getAttribute('public_name')) ?>" loading="<?php echo $index<3?'eager':'lazy'; ?>"><?php else: ?><span class="jt-media-fallback" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string)$service->getAttribute('public_name'),0,1))) ?></span><?php endif; ?></span>
          <span class="jt-service-body">
            <h3><?= e($service->getAttribute('public_name')) ?></h3>
            <p><?= e($service->getAttribute('description')) ?></p>
            <em>Découvrir le service<span aria-hidden="true"> →</span></em>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="jt-section jt-about" id="a-propos">
    <div class="jt-wrap jt-about-grid">
      <div class="jt-about-visual"><?php if($aboutImage): ?><img src="<?= e($aboutImage) ?>" alt="<?= e($name) ?> en intervention" loading="lazy"><?php endif; ?><span class="jt-about-badge"><strong><?= e($name) ?></strong><small><?= $city!==''?e($city).' · ':'' ?>Val-d'Oise</small></span></div>
      <div class="jt-about-copy">
        <span class="jt-eyebrow"><?= e($copy['about_eyebrow']) ?></span>
        <h2><?= e($copy['about_title']) ?></h2>
        <p><?= nl2br(e($aboutText)) ?></p>
        <ul class="jt-checklist">
          <li><span aria-hidden="true">✓</span>Une réponse claire et rapide</li>
          <li><span aria-hidden="true">✓</span>Un devis gratuit et transparent</li>
          <li><span aria-hidden="true">✓</span>Un chantier propre et sécurisé</li>
        </ul>
        <a class="jt-link-more" href="/a-propos">Découvrir l'entreprise<span aria-hidden="true"> →</span></a>
      </div>
    </div>
  </section>

  <?php if($projects): ?>
  <section class="jt-section jt-projects" id="realisations">
    <div class="jt-wrap">
      <div class="jt-section-head">
        <div>
          <span class="jt-eyebrow"><?= e($copy['projects_eyebrow']) ?></span>
          <h2><?= e($copy['projects_title']) ?></h2>
        </div>
        <a class="jt-link-more" href="/realisations">Toutes les réalisations<span aria-hidden="true"> →</span></a>
      </div>
      <div class="jt-project-grid">
        <?php foreach($projects as $project):$before=$mediaUrl($project->getAttribute('before_media_id'));$after=$mediaUrl($project->getAttribute('after_media_id'));$image=$after?:$before;if(!$image)continue; ?>
        <figure class="jt-project-card">
          <a href="/realisations"><img src="<?= e($image) ?>" alt="<?= e($project->getAttribute('alt_text')?:$project->getAttribute('title')) ?>" loading="lazy"><?php if($before&&$after): ?><span class="jt-tag">Avant / après</span><?php endif; ?></a>
          <figcaption><strong><?= e($project->getAttribute('title')) ?></strong><small><?= $before&&$after?'Transformation complète':'Chantier réalisé avec soin' ?></small></figcaption>
        </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?= view('public.partials.zone_editorial',['company'=>$company,'copy'=>$copy]) ?>

  <?php
  $sectorPages = array_values(array_filter(
      Page::where(['type' => 'local', 'status' => 'published', 'indexable' => 1], 'title ASC'),
      static fn ($p) => (string) $p->getAttribute('slug') !== ''
  ));
  if ($sectorPages): ?>
  <section class="jt-section jt-sectors">
    <div class="jt-wrap">
      <div class="jt-section-head">
        <div>
          <span class="jt-eyebrow">Nos secteurs d'intervention</span>
          <h2>Un couvreur près de chez vous, dans tout le Val-d'Oise</h2>
        </div>
        <p>Découvrez nos interventions commune par commune et demandez votre devis gratuit en ligne.</p>
      </div>
      <nav class="jt-sector-grid" aria-label="Communes desservies">
        <?php foreach ($sectorPages as $sectorPage): ?>
        <a class="jt-sector-link" href="/<?= e($sectorPage->getAttribute('slug')) ?>">
          <strong><?= e($sectorPage->getAttribute('title')) ?></strong>
          <em>Voir les interventions<span aria-hidden="true"> →</span></em>
        </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </section>
  <?php endif; ?>

  <?php if($reviews): ?>
  <section class="jt-section jt-reviews" id="avis">
    <div class="jt-wrap">
      <div class="jt-section-head">
        <div>
          <span class="jt-eyebrow"><?= e($copy['reviews_eyebrow']) ?></span>
          <h2><?= e($copy['reviews_title']) ?></h2>
        </div>
        <?php if($average!==null): ?>
        <span class="jt-google-score"><b aria-hidden="true">G</b><span><span class="jt-stars" aria-hidden="true">★★★★★</span><small><strong><?= e(number_format($average,1,',','')) ?>/5</strong> · <?= count($allReviews) ?> avis</small></span></span>
        <?php endif; ?>
      </div>
      <div class="jt-review-grid">
        <?php foreach($reviews as $review):$rating=max(1,(int)$review->getAttribute('rating')); ?>
        <article class="jt-review-card">
          <span class="jt-stars" aria-label="<?= $rating ?> sur 5"><?= str_repeat('★',$rating) ?></span>
          <p>« <?= e($review->getAttribute('content')) ?> »</p>
          <footer><span class="jt-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string)$review->getAttribute('author_name'),0,1))) ?></span><span><strong><?= e($review->getAttribute('author_name')) ?></strong><small><?= e($review->getAttribute('source')==='google'?'Avis Google':'Client vérifié') ?></small></span></footer>
        </article>
        <?php endforeach; ?>
      </div>
      <p class="jt-reviews-more"><a class="jt-link-more" href="/avis-clients">Lire tous les avis<span aria-hidden="true"> →</span></a></p>
    </div>
  </section>
  <?php endif; ?>

  <?php if(!empty($latestBlogPosts)): ?>
  <section class="jt-section jt-blog">
    <div class="jt-wrap">
      <div class="jt-section-head">
        <div>
          <span class="jt-eyebrow"><?= e($copy['blog_eyebrow']) ?></span>
          <h2><?= e($copy['blog_title']) ?></h2>
        </div>
        <a class="jt-link-more" href="/blog">Tous les articles<span aria-hidden="true"> →</span></a>
      </div>
      <div class="jt-blog-grid">
        <?php foreach($latestBlogPosts as $post):$image=$postImage($post);$title=trim(strip_tags(html_entity_decode($post['title']['rendered']??'',ENT_QUOTES|ENT_HTML5,'UTF-8')));$excerpt=trim(strip_tags(html_entity_decode($post['excerpt']['rendered']??'',ENT_QUOTES|ENT_HTML5,'UTF-8'))); ?>
        <a class="jt-blog-card" href="/blog/<?= e($post['slug']??'') ?>">
          <span class="jt-blog-media"><?php if($image): ?><img src="<?= e($image) ?>" alt="<?= e($title) ?>" loading="lazy"><?php else: ?><span class="jt-media-fallback" aria-hidden="true">Conseils</span><?php endif; ?></span>
          <span class="jt-blog-body"><small><?= e(isset($post['date'])?date('d/m/Y',strtotime((string)$post['date'])):'' ) ?></small><h3><?= e($title) ?></h3><p><?= e(mb_strimwidth($excerpt,0,130,'…')) ?></p><em>Lire l'article<span aria-hidden="true"> →</span></em></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?= view('public.partials.quote_card',['company'=>$company]) ?>
</main>
