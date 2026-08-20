<?php
use App\Models\CompanyService;
use App\Models\Media;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Testimonial;
$name=(string)($company?->getAttribute('trade_name')?:config('app.name','Votre artisan'));
$phone=(string)($company?->getAttribute('phone')??'');$phoneHref=preg_replace('/\D+/','',$phone);
$hero=$company?->getAttribute('hero_media_id')?Media::find((int)$company->getAttribute('hero_media_id'))?->getAttribute('url'):null;
$services=array_values(array_filter(CompanyService::all('sort_order ASC'),static fn($service)=>(bool)$service->getAttribute('is_active')));
$projects=array_slice(Project::visible(),0,6);$reviews=array_slice(Testimonial::visible(),0,3);
$copy=array_merge(['hero_eyebrow'=>'Artisan local','hero_title'=>'Votre projet entre de bonnes mains.','services_title'=>'Nos prestations','about_title'=>'Un savoir-faire de proximité','zone_title'=>'Nous intervenons près de chez vous','zone_text'=>'Contactez-nous pour vérifier notre disponibilité dans votre commune.','projects_title'=>'Nos réalisations','reviews_title'=>'Ils nous ont fait confiance','blog_title'=>'Nos derniers conseils'],json_decode((string)(Setting::first(['key'=>'content.home_copy'])?->getAttribute('value')??'{}'),true)?:[]);
$mediaUrl=static fn($id)=>$id?Media::find((int)$id)?->getAttribute('url'):null;
?>
<main class="professional-home">
  <section class="ph-hero"><?php if($hero): ?><img src="<?= e($hero) ?>" alt="<?= e($name) ?>" fetchpriority="high"><?php endif; ?><div class="ph-hero-overlay"></div><div class="ph-hero-content"><span class="ph-eyebrow"><?= e($copy['hero_eyebrow']) ?><?= $company?->getAttribute('city')?' · '.e($company->getAttribute('city')):'' ?></span><h1><?= e($page->getAttribute('h1')?:$copy['hero_title']) ?></h1><?php if($company?->getAttribute('short_description')): ?><p><?= e($company->getAttribute('short_description')) ?></p><?php endif; ?><div><a class="ph-btn accent" href="#devis">Demander un devis</a><?php if($phone!==''): ?><a class="ph-btn light" href="tel:<?= e($phoneHref) ?>">Appeler — <?= e($phone) ?></a><?php endif; ?></div></div></section>

  <?php if($services): ?><section class="ph-section"><span class="ph-eyebrow blue">Nos prestations</span><h2><?= e($copy['services_title']) ?></h2><div class="marketing-service-grid"><?php foreach($services as $service):$image=$mediaUrl($service->getAttribute('image_media_id')); ?><a class="marketing-service-card" href="/<?= e($service->getAttribute('slug')) ?>"><?php if($image): ?><img src="<?= e($image) ?>" alt="<?= e($service->getAttribute('public_name')) ?>" loading="lazy"><?php endif; ?><div><h3><?= e($service->getAttribute('public_name')) ?></h3><p><?= e($service->getAttribute('description')) ?></p><span>Découvrir →</span></div></a><?php endforeach; ?></div></section><?php endif; ?>

  <section class="ph-section"><span class="ph-eyebrow blue">Qui sommes-nous</span><h2><?= e($copy['about_title']) ?></h2><?php if($company?->getAttribute('long_description')): ?><p><?= nl2br(e($company->getAttribute('long_description'))) ?></p><?php elseif($company?->getAttribute('short_description')): ?><p><?= e($company->getAttribute('short_description')) ?></p><?php endif; ?><a class="reviews-more-button" href="/a-propos">En savoir plus →</a></section>

  <?= view('public.partials.zone_card',['company'=>$company]) ?>

  <?php if($projects): ?><section class="ph-section"><span class="ph-eyebrow blue">Réalisations</span><h2><?= e($copy['projects_title']) ?></h2><div class="marketing-project-grid"><?php foreach($projects as $project):$image=$mediaUrl($project->getAttribute('after_media_id'))?:$mediaUrl($project->getAttribute('before_media_id'));if(!$image)continue; ?><figure><img src="<?= e($image) ?>" alt="<?= e($project->getAttribute('alt_text')?:$project->getAttribute('title')) ?>" loading="lazy"><figcaption><?= e($project->getAttribute('title')) ?></figcaption></figure><?php endforeach; ?></div><a class="reviews-more-button" href="/realisations">Toutes les réalisations →</a></section><?php endif; ?>

  <?php if($reviews): ?><section class="ph-section"><span class="ph-eyebrow blue">Avis clients</span><h2><?= e($copy['reviews_title']) ?></h2><div class="marketing-review-grid"><?php foreach($reviews as $review): ?><article><div class="stars"><?= str_repeat('★',max(1,(int)$review->getAttribute('rating'))) ?></div><p>“<?= e($review->getAttribute('content')) ?>”</p><strong><?= e($review->getAttribute('author_name')) ?></strong></article><?php endforeach; ?></div><a class="reviews-more-button" href="/avis-clients">Tous les avis →</a></section><?php endif; ?>

  <?php if(!empty($latestBlogPosts)): ?><section class="ph-section"><span class="ph-eyebrow blue">Conseils &amp; expertise</span><h2><?= e($copy['blog_title']) ?></h2><div class="marketing-service-grid"><?php foreach($latestBlogPosts as $post): ?><a class="marketing-service-card" href="/blog/<?= e($post['slug']??'') ?>"><div><h3><?= e(trim(strip_tags(html_entity_decode($post['title']['rendered']??'',ENT_QUOTES|ENT_HTML5,'UTF-8')))) ?></h3><span>Lire l’article →</span></div></a><?php endforeach; ?></div></section><?php endif; ?>

  <?= view('public.partials.quote_card',['company'=>$company]) ?>
</main>
