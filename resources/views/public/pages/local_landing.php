<?php
use App\Models\City;use App\Models\CompanyService;use App\Models\LocalPage;use App\Models\Media;use App\Models\Page;use App\Models\Project;
$name=(string)($company?->getAttribute('trade_name')?:config('app.name'));
$phone=(string)($company?->getAttribute('phone')??'');$phoneHref=preg_replace('/\D+/','',$phone);
$mediaUrl=static fn($id)=>$id?Media::find((int)$id)?->getAttribute('url'):null;
$slug=(string)$page->getAttribute('slug');
$local=LocalPage::first(['page_id'=>(int)$page->id()]);
$city=$local?City::find((int)$local->getAttribute('city_id')):null;
if(!$city&&str_starts_with($slug,'couvreur-'))$city=City::first(['slug'=>substr($slug,9)]);
$service=$page->getAttribute('company_service_id')?CompanyService::find((int)$page->getAttribute('company_service_id')):null;
$cityName=(string)($city?->getAttribute('name')??'');
$hero=($service?$mediaUrl($service->getAttribute('image_media_id')):null)?:$mediaUrl($company?->getAttribute('hero_media_id'));
$services=array_values(array_filter(CompanyService::all('sort_order ASC'),static fn($s)=>(bool)$s->getAttribute('is_active')));
// Pages de la même ville (autres services) et du même service (autres villes).
$sameCity=[];$sameService=[];
if($city){
  foreach(LocalPage::where(['city_id'=>(int)$city->id(),'is_active'=>1]) as $lp){if((int)$lp->getAttribute('page_id')===(int)$page->id())continue;$p=Page::find((int)$lp->getAttribute('page_id'));$s=CompanyService::find((int)$lp->getAttribute('company_service_id'));if($p&&$s&&$p->getAttribute('status')==='published')$sameCity[]=['url'=>'/'.$p->getAttribute('slug'),'label'=>(string)$s->getAttribute('public_name'),'img'=>$mediaUrl($s->getAttribute('image_media_id'))];}
  $hub=Page::findBySlug('couvreur-'.$city->getAttribute('slug'));
}
if($service){foreach(LocalPage::where(['company_service_id'=>(int)$service->id(),'is_active'=>1]) as $lp){if((int)$lp->getAttribute('page_id')===(int)$page->id())continue;$p=Page::find((int)$lp->getAttribute('page_id'));$c=City::find((int)$lp->getAttribute('city_id'));if($p&&$c&&$p->getAttribute('status')==='published')$sameService[]=['url'=>'/'.$p->getAttribute('slug'),'label'=>(string)$c->getAttribute('name')];}}
elseif($city){foreach(Page::where(['type'=>'local','status'=>'published'],'title ASC') as $p){$ps=(string)$p->getAttribute('slug');if(str_starts_with($ps,'couvreur-')&&$ps!==$slug)$sameService[]=['url'=>'/'.$ps,'label'=>preg_replace('/^Couvreur à /u','',(string)$p->getAttribute('title'))];}}
$projects=array_slice(array_values(array_filter(Project::visible(),static fn($p)=>$p->getAttribute('after_media_id')&&(!$service||(int)$p->getAttribute('company_service_id')===(int)$service->id()))),0,3);
if(count($projects)<3)$projects=array_slice(array_values(array_filter(Project::visible(),static fn($p)=>(bool)$p->getAttribute('after_media_id'))),0,3);
?>
<?= view('public.partials.breadcrumbs',['items'=>array_values(array_filter([['label'=>'Accueil','url'=>'/'],$service?['label'=>(string)$service->getAttribute('public_name'),'url'=>'/'.$service->getAttribute('slug')]:null,['label'=>(string)$page->getAttribute('title'),'url'=>null]]))]) ?>
<main class="ll">
  <section class="ll-hero">
    <?php if($hero): ?><img src="<?= e($hero) ?>" alt="<?= e($page->getAttribute('title')) ?> — <?= e($name) ?>" fetchpriority="high"><?php endif; ?>
    <div class="ll-hero-shade" aria-hidden="true"></div>
    <div class="ll-hero-copy">
      <span class="hh-kicker"><?= e($cityName!==''?$cityName.($city?->getAttribute('postal_code')?' · '.$city->getAttribute('postal_code'):''):'La Réunion') ?></span>
      <h1><?= e($page->getAttribute('h1')?:$page->getAttribute('title')) ?></h1>
      <p><?= e($page->getAttribute('meta_description')) ?></p>
      <div class="hh-actions">
        <a class="hh-btn hh-btn-red" href="#devis">Devis gratuit</a>
        <?php if($phone!==''): ?><a class="hh-btn hh-btn-yellow" href="tel:<?= e($phoneHref) ?>">☎ <?= e($phone) ?></a><?php endif; ?>
      </div>
      <ul class="ll-trust"><li>Urgences 24 h/24, 7 j/7</li><li>Déplacement et devis gratuits</li><li>30 ans d’expérience</li></ul>
    </div>
  </section>

  <div class="ll-body">
    <article class="ll-content"><?= $blocksHtml ?? '' ?></article>
    <aside class="ll-aside">
      <div class="ll-card ll-card-red">
        <strong>Besoin d’un couvreur<?= $cityName!==''?' à '.e($cityName):'' ?> ?</strong>
        <p>Diagnostic et devis gratuits, intervention rapide sur toute l’île.</p>
        <?php if($phone!==''): ?><a class="ll-call" href="tel:<?= e($phoneHref) ?>"><?= e($phone) ?></a><?php endif; ?>
        <a class="ll-quote" href="#devis">Demander un devis</a>
      </div>
      <?php if($sameCity): ?><div class="ll-card"><strong>Nos services<?= $cityName!==''?' à '.e($cityName):'' ?></strong><nav><?php foreach($sameCity as $l): ?><a href="<?= e($l['url']) ?>"><?= e($l['label']) ?> <span>→</span></a><?php endforeach; ?><?php if(!empty($hub)&&$hub->getAttribute('slug')!==$slug): ?><a href="/<?= e($hub->getAttribute('slug')) ?>">Couvreur à <?= e($cityName) ?> <span>→</span></a><?php endif; ?></nav></div><?php endif; ?>
      <?php if(!$sameCity&&$services): ?><div class="ll-card"><strong>Nos services</strong><nav><?php foreach($services as $s): ?><a href="/<?= e($s->getAttribute('slug')) ?>"><?= e($s->getAttribute('public_name')) ?> <span>→</span></a><?php endforeach; ?></nav></div><?php endif; ?>
    </aside>
  </div>

  <?php if($projects): ?>
  <section class="ll-section"><div class="hh-wrap">
    <header class="hh-head"><div><span class="hh-eyebrow">Nos réalisations</span><h2>Des chantiers réalisés à La Réunion</h2></div><a class="hh-btn hh-btn-outline" href="/realisations">Voir tout</a></header>
    <div class="ll-projects"><?php foreach($projects as $pr): ?><figure><img src="<?= e($mediaUrl($pr->getAttribute('after_media_id'))) ?>" alt="<?= e($pr->getAttribute('alt_text')?:$pr->getAttribute('title')) ?>" loading="lazy"><figcaption><?= e($pr->getAttribute('title')) ?></figcaption></figure><?php endforeach; ?></div>
  </div></section>
  <?php endif; ?>

  <?php if($sameService): ?>
  <section class="ll-section ll-cities"><div class="hh-wrap">
    <span class="hh-eyebrow">Zone d’intervention</span>
    <h2><?= e(($service?(string)$service->getAttribute('public_name'):'Couvreur').' dans les autres communes') ?></h2>
    <nav class="hh-cities"><?php foreach($sameService as $l): ?><a href="<?= e($l['url']) ?>"><?= e($l['label']) ?></a><?php endforeach; ?></nav>
  </div></section>
  <?php endif; ?>

  <?= view('public.partials.quote_card',['company'=>$company,'currentService'=>$service]) ?>
</main>
