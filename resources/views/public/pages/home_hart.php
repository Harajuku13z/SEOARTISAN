<?php
use App\Models\CompanyService;use App\Models\Media;use App\Models\Page;use App\Models\Setting;use App\Models\Project;
$name=(string)($company?->getAttribute('trade_name')?:config('app.name','Votre artisan'));
$phone=(string)($company?->getAttribute('phone')??'');$phoneHref=preg_replace('/\D+/','',$phone);
$whatsapp=preg_replace('/\D+/','',(string)($company?->getAttribute('whatsapp')??''));
if(str_starts_with($whatsapp,'0'))$whatsapp='262'.substr($whatsapp,1);
$region=(string)($company?->getAttribute('region')??'');
$h1=(string)($page->getAttribute('h1')?:$name);
$h1Html=preg_replace('/\*(.+?)\*/u','<span>$1</span>',e($h1));
$intro=(string)($company?->getAttribute('short_description')??'');
$copy=json_decode((string)(Setting::first(['key'=>'content.home_copy'])?->getAttribute('value')??'{}'),true)?:[];
$photos=(array)($copy['photos']??[]);
$photo=static fn(string $key,string $fallback=''):string=>(string)($photos[$key]??$fallback);
$mediaUrl=static fn($id)=>$id?Media::find((int)$id)?->getAttribute('url'):null;
$services=array_values(array_filter(CompanyService::all('sort_order ASC'),static fn($s)=>(bool)$s->getAttribute('is_active')));
$sectorPages=array_values(array_filter(Page::where(['type'=>'local','status'=>'published','indexable'=>1],'title ASC'),static fn($p)=>(string)$p->getAttribute('slug')!==''));
$zoneMap=(string)(json_decode((string)(Setting::first(['key'=>'visual.zone_map'])?->getAttribute('value')??'""'),true)?:'');
$icons=['shield'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>','home'=>'<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>','clock'=>'<circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2"/><path d="M9 2h6"/>','pin'=>'<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>','phone'=>'<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/>','search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>','doc'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>','tool'=>'<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4Z"/>','check'=>'<path d="M20 6 9 17l-5-5"/>','wind'=>'<path d="M17.7 7.7A2.5 2.5 0 1 1 19.5 12H2"/><path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/>'];
$icon=static fn(string $k):string=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($icons[$k]??$icons['check']).'</svg>';
$badges=array_values(array_filter((array)($copy['badges']??[]),'is_array'));
$why=array_values(array_filter((array)($copy['why']??[]),'is_array'));
$steps=array_values(array_filter((array)($copy['steps']??[]),'is_array'));
$faqs=array_values(array_filter((array)($copy['faq']??[]),'is_array'));
$projectPairs=array_slice(array_values(array_filter(Project::visible(),static fn($p)=>$p->getAttribute('before_media_id')&&$p->getAttribute('after_media_id'))),0,3);
$cities=array_values(array_filter(array_map('trim',explode(',',(string)($copy['zone_cities']??'')))));
?>
<main class="hh">

  <section class="hh-hero">
    <picture class="hh-hero-bg"><img src="<?= e($photo('hero','/assets/images/hart/hero-reunion.svg')) ?>" alt="Toiture en tôle rouge rénovée par HART TOITURE face à l’océan" fetchpriority="high"></picture>
    <div class="hh-hero-shade" aria-hidden="true"></div>
    <div class="hh-stripes" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="hh-wrap hh-hero-inner">
      <div class="hh-hero-copy">
        <span class="hh-kicker"><?= e($copy['hero_kicker']??'Couvreur à La Réunion') ?></span>
        <h1><?= $h1Html ?></h1>
        <p class="hh-lead"><?= e($copy['hero_lead']??$intro) ?></p>
        <div class="hh-actions">
          <a class="hh-btn hh-btn-red" href="#devis">Demander un devis</a>
          <?php if($phone!==''): ?><a class="hh-btn hh-btn-yellow" href="tel:<?= e($phoneHref) ?>"><?= $icon('phone') ?>Appeler maintenant</a><?php endif; ?>
        </div>
        <ul class="hh-hero-trust">
          <?php foreach((array)($copy['hero_trust']??['Devis gratuit','Toute l’île']) as $item): ?><li><?= $icon('check') ?><?= e($item) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <aside class="hh-hero-card" aria-label="Contact rapide">
        <span class="hh-hero-card-ico"><?= $icon('wind') ?></span>
        <strong><?= e($copy['hero_card_title']??'Fuite, tôle arrachée ?') ?></strong>
        <p><?= e($copy['hero_card_text']??'Intervention rapide partout à La Réunion.') ?></p>
        <?php if($phone!==''): ?><a href="tel:<?= e($phoneHref) ?>"><?= e($phone) ?></a><?php endif; ?>
        <?php if($whatsapp!==''): ?><a class="hh-wa" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener">Écrire sur WhatsApp</a><?php endif; ?>
      </aside>
    </div>
  </section>

  <?php if($badges): ?>
  <section class="hh-badges" aria-label="Nos engagements"><div class="hh-wrap"><ul>
    <?php foreach($badges as $badge): ?><li><span class="ico"><?= $icon((string)($badge['icon']??'shield')) ?></span><span><strong><?= e($badge['title']??'') ?></strong><small><?= e($badge['text']??'') ?></small></span></li><?php endforeach; ?>
  </ul></div></section>
  <?php endif; ?>

  <?php if($services): ?>
  <section class="hh-section" id="services">
    <div class="hh-wrap">
      <header class="hh-head">
        <div><span class="hh-eyebrow"><?= e($copy['services_eyebrow']??'Nos services') ?></span><h2><?= e($copy['services_title']??'Nos services') ?></h2></div>
        <p><?= e($copy['services_text']??'') ?></p>
      </header>
      <div class="hh-services">
        <?php foreach($services as $i=>$service):$img=(string)($photos['service_'.$service->getAttribute('slug')]??$mediaUrl($service->getAttribute('image_media_id'))??''); ?>
        <a class="hh-service<?= $i<2?' is-large':'' ?>" href="/<?= e($service->getAttribute('slug')) ?>">
          <?php if($img!==''): ?><img src="<?= e($img) ?>" alt="<?= e($service->getAttribute('public_name')) ?> à La Réunion" loading="<?= $i<2?'eager':'lazy' ?>"><?php endif; ?>
          <span class="hh-service-body">
            <?php if($service->getAttribute('is_emergency')): ?><em class="hh-tag">Urgence</em><?php endif; ?>
            <strong><?= e($service->getAttribute('public_name')) ?></strong>
            <small><?= e($service->getAttribute('description')) ?></small>
            <b>Découvrir <span aria-hidden="true">→</span></b>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="hh-section hh-why">
    <div class="hh-wrap hh-why-grid">
      <div class="hh-why-media">
        <img src="<?= e($photo('why','/assets/images/hart/logo-hart.png')) ?>" alt="Couvreur HART TOITURE sur le toit d’une maison à La Réunion" loading="lazy">
        <img class="hh-why-small" src="<?= e($photo('why_small','/assets/images/hart/logo-hart.png')) ?>" alt="Toiture en tôle repeinte par HART TOITURE" loading="lazy">
        <?php if(!empty($copy['seal_value'])): ?><span class="hh-seal"><b><?= e($copy['seal_value']) ?></b><small><?= e($copy['seal_label']??'') ?></small></span><?php endif; ?>
      </div>
      <div class="hh-why-copy">
        <span class="hh-eyebrow"><?= e($copy['about_eyebrow']??'Qui sommes-nous') ?></span>
        <h2><?= e($copy['about_title']??$name) ?></h2>
        <p><?= e($copy['about_lead']??(string)($company?->getAttribute('editorial_presentation')??'')) ?></p>
        <?php if($why): ?><ul class="hh-why-list">
          <?php foreach($why as $point): ?><li><span><?= $icon((string)($point['icon']??'check')) ?></span><div><strong><?= e($point['title']??'') ?></strong><small><?= e($point['text']??'') ?></small></div></li><?php endforeach; ?>
        </ul><?php endif; ?>
        <a class="hh-btn hh-btn-outline" href="/a-propos">Découvrir HART</a>
      </div>
    </div>
  </section>

  <section class="hh-emergency">
    <img src="<?= e($photo('emergency','/assets/images/hart/hero-reunion.svg')) ?>" alt="" loading="lazy" aria-hidden="true">
    <div class="hh-wrap hh-emergency-inner">
      <div>
        <span class="hh-kicker"><?= e($copy['emergency_kicker']??'Saison cyclonique') ?></span>
        <h2><?= e($copy['emergency_title']??'Intervention rapide partout à La Réunion') ?></h2>
        <p><?= e($copy['emergency_text']??'') ?></p>
      </div>
      <div class="hh-actions">
        <?php if($phone!==''): ?><a class="hh-btn hh-btn-yellow" href="tel:<?= e($phoneHref) ?>"><?= $icon('phone') ?><?= e($phone) ?></a><?php endif; ?>
        <a class="hh-btn hh-btn-white" href="/recherche-fuite-et-urgence">Recherche de fuite et urgence</a>
      </div>
    </div>
  </section>

  <?php if($projectPairs): ?>
  <section class="hh-section hh-projects" id="realisations">
    <div class="hh-wrap">
      <header class="hh-head">
        <div><span class="hh-eyebrow">Nos réalisations</span><h2><?= e($copy['projects_title']??'Avant / après : le travail parle de lui-même') ?></h2></div>
        <a class="hh-btn hh-btn-outline" href="/realisations">Toutes les réalisations</a>
      </header>
      <div class="hh-projects-grid">
        <?php foreach($projectPairs as $project): ?>
        <figure class="hh-ba">
          <div class="hh-ba-media">
            <span><img src="<?= e($mediaUrl($project->getAttribute('before_media_id'))) ?>" alt="Avant — <?= e($project->getAttribute('title')) ?>" loading="lazy"><em>Avant</em></span>
            <span><img src="<?= e($mediaUrl($project->getAttribute('after_media_id'))) ?>" alt="Après — <?= e($project->getAttribute('title')) ?>" loading="lazy"><em class="is-after">Après</em></span>
          </div>
          <figcaption><strong><?= e($project->getAttribute('title')) ?></strong><small><?= e($project->getAttribute('description')) ?></small></figcaption>
        </figure>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if($steps): ?>
  <section class="hh-section hh-steps-section">
    <div class="hh-wrap">
      <header class="hh-head hh-head-center"><div><span class="hh-eyebrow">Comment ça marche</span><h2><?= e($copy['steps_title']??'Votre projet en 4 étapes') ?></h2></div></header>
      <ol class="hh-steps">
        <?php foreach($steps as $n=>$step): ?><li><span class="hh-step-n"><?= $n+1 ?></span><span class="hh-step-ico"><?= $icon((string)($step['icon']??'check')) ?></span><strong><?= e($step['title']??'') ?></strong><small><?= e($step['text']??'') ?></small></li><?php endforeach; ?>
      </ol>
    </div>
  </section>
  <?php endif; ?>

  <section class="hh-section hh-zone" id="zone">
    <div class="hh-wrap hh-zone-grid">
      <div>
        <span class="hh-eyebrow"><?= e($copy['zone_eyebrow']??'Zone d’intervention') ?></span>
        <h2><?= e($copy['zone_title']??'Partout à La Réunion') ?></h2>
        <p><?= e($copy['zone_text']??'') ?></p>
        <?php
        $hubs=[];foreach($sectorPages as $sp){$ss=(string)$sp->getAttribute('slug');if(str_starts_with($ss,'couvreur-'))$hubs[substr($ss,9)]=['url'=>'/'.$ss,'name'=>preg_replace('/^Couvreur à /u','',(string)$sp->getAttribute('title'))];}
        $groups=(array)($copy['zone_groups']??[]);
        ?>
        <?php if($hubs&&$groups): ?>
        <div class="hh-zones">
          <?php foreach($groups as $gi=>$group):$items=array_values(array_filter(array_map(static fn($slug)=>$hubs[$slug]??null,(array)($group['cities']??[])))); if(!$items)continue; ?>
          <div class="hh-sector hh-sector-<?= (int)$gi ?>">
            <strong><?= e($group['label']??'') ?><small><?= count($items) ?> communes</small></strong>
            <ul><?php foreach($items as $it): ?><li><a href="<?= e($it['url']) ?>"><?= e($it['name']) ?></a></li><?php endforeach; ?></ul>
          </div>
          <?php endforeach; ?>
        </div>
        <?php elseif($hubs): ?><nav class="hh-cities" aria-label="Communes desservies"><?php foreach($hubs as $it): ?><a href="<?= e($it['url']) ?>"><?= e($it['name']) ?></a><?php endforeach; ?></nav>
        <?php elseif($cities): ?><div class="hh-cities"><?php foreach($cities as $c): ?><span><?= e($c) ?></span><?php endforeach; ?></div><?php endif; ?>
        <a class="hh-btn hh-btn-red hh-zone-cta" href="#devis">Vérifier ma commune</a>
      </div>
      <?php if($zoneMap!==''): ?><div class="hh-map"><img src="<?= e($zoneMap) ?>" alt="Carte des communes de La Réunion desservies par <?= e($name) ?>" loading="lazy"></div><?php endif; ?>
    </div>
  </section>

  <?php if(!empty($latestBlogPosts)): ?>
  <section class="hh-section hh-blog">
    <div class="hh-wrap">
      <header class="hh-head"><div><span class="hh-eyebrow">Conseils toiture</span><h2>Nos derniers articles</h2></div><a class="hh-btn hh-btn-outline" href="/blog">Tous les articles</a></header>
      <div class="hh-blog-grid">
        <?php foreach($latestBlogPosts as $post):$pImg=$post['_embedded']['wp:featuredmedia'][0]['source_url']??null;$pTitle=trim(strip_tags(html_entity_decode($post['title']['rendered']??'',ENT_QUOTES|ENT_HTML5,'UTF-8')));$pEx=trim(strip_tags(html_entity_decode($post['excerpt']['rendered']??'',ENT_QUOTES|ENT_HTML5,'UTF-8'))); ?>
        <a class="hh-post" href="/blog/<?= e($post['slug']??'') ?>"><?php if(is_string($pImg)): ?><img src="<?= e($pImg) ?>" alt="<?= e($pTitle) ?>" loading="lazy"><?php endif; ?><span><small><?= e(isset($post['date'])?date('d/m/Y',strtotime((string)$post['date'])):'') ?></small><strong><?= e($pTitle) ?></strong><em><?= e(mb_strimwidth($pEx,0,140,'…')) ?></em><b>Lire l’article →</b></span></a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if($faqs): ?>
  <section class="hh-section hh-faq">
    <div class="hh-wrap hh-faq-grid">
      <div><span class="hh-eyebrow">Questions fréquentes</span><h2>Vos questions sur votre toiture</h2><p>Une autre question ? Appelez-nous ou écrivez-nous sur WhatsApp, nous vous répondons rapidement.</p></div>
      <div class="hh-faq-list">
        <?php foreach($faqs as $i=>$faq): ?><details<?= $i===0?' open':'' ?>><summary><?= e($faq['q']??'') ?></summary><p><?= e($faq['a']??'') ?></p></details><?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?= view('public.partials.quote_card',['company'=>$company]) ?>
</main>
<?php if($faqs): ?><script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>array_map(static fn($f)=>['@type'=>'Question','name'=>(string)($f['q']??''),'acceptedAnswer'=>['@type'=>'Answer','text'=>(string)($f['a']??'')]],$faqs)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG) ?></script><?php endif; ?>
