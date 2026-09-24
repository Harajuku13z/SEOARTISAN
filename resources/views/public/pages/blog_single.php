<?php
$plain=static fn(string $value):string=>trim(html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$image=$post['_embedded']['wp:featuredmedia'][0]['source_url']??null;
$imageAlt=$plain((string)($post['_embedded']['wp:featuredmedia'][0]['alt_text']??''))?:$plain($post['title']['rendered']??'');
$phone=(string)($company?->getAttribute('phone')??'');$phoneHref=preg_replace('/\D+/','',$phone);
$blogName=(string)($company?->getAttribute('trade_name')?:config('app.name'));
$blogArea=trim((string)($company?->getAttribute('department')?:$company?->getAttribute('region')??''));
$title=$plain($post['title']['rendered']??'');
$content=(string)($post['content']['rendered']??'');
// Sommaire : ancres ajoutées aux intertitres H2.
$toc=[];$used=[];
$content=preg_replace_callback('#<h2([^>]*)>(.*?)</h2>#si',static function(array $m)use(&$toc,&$used,$plain){$label=$plain($m[2]);$id=trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT',$label)?:'')),'-')?:'section';$base=$id;$n=2;while(isset($used[$id]))$id=$base.'-'.$n++;$used[$id]=true;$toc[]=['id'=>$id,'label'=>$label];return '<h2'.$m[1].' id="'.$id.'">'.$m[2].'</h2>';},$content)??$content;
$words=str_word_count($plain($content));$minutes=max(2,(int)round($words/200));
$published=isset($post['date'])?strtotime((string)$post['date']):null;$modified=isset($post['modified'])?strtotime((string)$post['modified']):null;
$months=['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
$fr=static fn(?int $t):string=>$t?date('j',$t).' '.$months[(int)date('n',$t)-1].' '.date('Y',$t):'';
?>
<main class="blog-single hb">
  <nav class="hb-crumbs" aria-label="Fil d’Ariane"><a href="/">Accueil</a><span>/</span><a href="/blog">Blog</a><span>/</span><span aria-current="page"><?= e($title) ?></span></nav>
  <div class="hb-layout">
    <article class="hb-article">
      <header class="hb-head">
        <span class="hh-kicker">Conseils toiture · La Réunion</span>
        <h1><?= e($title) ?></h1>
        <p class="hb-meta">Par <strong>Guy Hart</strong>, couvreur depuis 30 ans · <time datetime="<?= e((string)($post['date']??'')) ?>"><?= e($fr($published)) ?></time><?php if($modified&&$published&&date('Y-m-d',$modified)!==date('Y-m-d',$published)): ?> · mis à jour le <time datetime="<?= e((string)$post['modified']) ?>"><?= e($fr($modified)) ?></time><?php endif; ?> · <?= $minutes ?> min de lecture</p>
      </header>
      <?php if($image): ?><img class="hb-hero" src="<?= e($image) ?>" alt="<?= e($imageAlt) ?>" fetchpriority="high"><?php endif; ?>
      <?php if(count($toc)>=3): ?>
      <nav class="hb-toc" aria-label="Sommaire"><strong>Sommaire</strong><ol><?php foreach($toc as $item): ?><li><a href="#<?= e($item['id']) ?>"><?= e($item['label']) ?></a></li><?php endforeach; ?></ol></nav>
      <?php endif; ?>
      <div class="wp-article-content hb-content"><?= $content ?></div>
      <aside class="hb-author">
        <img src="/assets/images/hart/icon-512.png" alt="" width="64" height="64">
        <div><strong>Guy Hart — fondateur de <?= e($blogName) ?></strong><p>Installé à La Réunion depuis l’enfance, Guy Hart cumule trente ans d’expérience en toiture. Avec son équipe basée à Saint-Denis, il intervient sur toute l’île pour la recherche de fuite, la rénovation, le traitement antirouille, la peinture et l’étanchéité des toitures.</p></div>
      </aside>
    </article>
    <aside class="hb-sidebar" aria-label="Contact et services">
      <div class="hb-card hb-card-cta">
        <span class="hb-eyebrow">Devis gratuit</span>
        <strong class="hb-card-title">Un projet de toiture ?</strong>
        <p>Décrivez votre besoin à <?= e($blogName) ?> : déplacement et devis gratuits<?= $blogArea!==''?', partout à '.e(preg_replace('/\s*\(\d+\)$/','',$blogArea)):'' ?>.</p>
        <a class="hb-btn-red" href="#devis">Demander un devis</a>
        <?php if($phone): ?><a class="hb-btn-yellow" href="tel:<?= e($phoneHref) ?>">☎ <?= e($phone) ?></a><?php endif; ?>
      </div>
      <div class="hb-card"><strong class="hb-card-title dark">Nos interventions</strong><nav><?php foreach(array_slice($menuServices??[],0,7) as $item): ?><a href="/<?= e($item['slug']) ?>"><?= e($item['public_name']) ?> <span>→</span></a><?php endforeach; ?></nav></div>
    </aside>
  </div>
  <?php if(!empty($localLinks)): ?><section class="hb-local"><div class="hh-wrap"><span class="hh-eyebrow">Près de chez vous</span><h2>Nos services dans votre commune</h2><nav class="hh-cities"><?php foreach($localLinks as $link): ?><a href="/<?= e(ltrim((string)($link['slug']??$link['url']??''),'/')) ?>"><?= e($link['service']) ?> à <?= e($link['city']) ?></a><?php endforeach; ?></nav></div></section><?php endif; ?>
  <?= view('public.partials.quote_card',['company'=>$company]) ?>
</main>
