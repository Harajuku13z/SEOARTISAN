<?php
/**
 * @var \App\Models\Page $page
 * @var array<int,\App\Models\PageBlock> $blocks
 * @var array<int,string> $simpleTypes
 * @var array<int,string> $allTypes
 * @var array<string,string> $homeCopy
 * @var \App\Models\Media|null $savImage
 * @var \App\Models\Media|null $zoneImage
 */
$success = flash_message('success');
?>
<div class="admin-topbar">
  <h1><?= e($page->getAttribute('title') ?: $page->getAttribute('h1')) ?></h1>
  <a class="btn secondary" href="/<?= e($page->getAttribute('slug')) ?>" target="_blank">Voir la page</a>
</div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<?php $errors=flash_errors();if(!empty($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>
<?php if ($page->getAttribute('content_is_placeholder')): ?>
  <div class="alert warn">Cette page contient un contenu temporaire.</div>
<?php endif; ?>

<form method="post" action="/admin/pages/<?= (int) $page->id() ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card">
    <fieldset class="fieldset">
      <legend>Metadonnees</legend>
      <label>Titre (interne)</label>
      <input type="text" name="title" value="<?= e($page->getAttribute('title')) ?>">
      <label>H1</label>
      <input type="text" name="h1" value="<?= e($page->getAttribute('h1')) ?>">
      <label>Titre SEO</label>
      <input type="text" name="meta_title" value="<?= e($page->getAttribute('meta_title')) ?>">
      <label>Description SEO</label>
      <textarea name="meta_description" rows="2"><?= e($page->getAttribute('meta_description')) ?></textarea>
      <div class="row">
        <div>
          <label>Statut</label>
          <select name="status">
            <option value="draft" <?= $page->getAttribute('status') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
            <option value="published" <?= $page->getAttribute('status') === 'published' ? 'selected' : '' ?>>Publie</option>
            <option value="archived" <?= $page->getAttribute('status') === 'archived' ? 'selected' : '' ?>>Archive</option>
          </select>
        </div>
        <div class="checkbox-row" style="align-self:center;margin-top:20px">
          <input type="checkbox" id="indexable" name="indexable" value="1" <?= $page->getAttribute('indexable') ? 'checked' : '' ?>>
          <label for="indexable">Indexable par les moteurs de recherche</label>
        </div>
      </div>
    </fieldset>
  </div>
  <?php if($page->getAttribute('type')==='home'): ?>
  <div class="card">
    <fieldset class="fieldset">
      <legend>Textes affichés sur la page d’accueil</legend>

      <div class="admin-public-copy">
        <h3>Hero (bandeau du haut)</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_hero_eyebrow" value="<?= e($homeCopy['hero_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_hero_title" value="<?= e($homeCopy['hero_title']) ?>"></div>
          </div>
        </div>
      </div>

      <div class="admin-public-copy">
        <h3>Nos services</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_services_eyebrow" value="<?= e($homeCopy['services_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_services_title" value="<?= e($homeCopy['services_title']) ?>"></div>
          </div>
        </div>
      </div>

      <div class="admin-public-copy">
        <h3>Présentation (« Qui sommes-nous »)</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_about_eyebrow" value="<?= e($homeCopy['about_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_about_title" value="<?= e($homeCopy['about_title']) ?>"></div>
          </div>
        </div>
      </div>

      <div class="admin-public-copy">
        <h3>Zone d’intervention</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_zone_eyebrow" value="<?= e($homeCopy['zone_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_zone_title" value="<?= e($homeCopy['zone_title']) ?>"></div>
          </div>
          <label>Texte</label>
          <textarea name="home_zone_text" rows="3"><?= e($homeCopy['zone_text']) ?></textarea>
        </div>
        <div class="admin-public-copy-group">
          <label>Photo affichée à côté de cette section</label>
          <?php if($zoneImage): ?><img src="<?= e($zoneImage->getAttribute('url')) ?>" alt="Photo zone d’intervention" style="display:block;width:260px;height:180px;object-fit:cover;border-radius:16px;margin:8px 0 14px"><?php endif; ?>
          <input type="file" name="zone_image" accept="image/*">
          <p class="hint">Format conseillé : photo au format paysage, au moins 900 × 620 px.</p>
        </div>
      </div>

      <div class="admin-public-copy">
        <h3>Réalisations</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_projects_eyebrow" value="<?= e($homeCopy['projects_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_projects_title" value="<?= e($homeCopy['projects_title']) ?>"></div>
          </div>
        </div>
      </div>

      <div class="admin-public-copy">
        <h3>Avis clients</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_reviews_eyebrow" value="<?= e($homeCopy['reviews_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_reviews_title" value="<?= e($homeCopy['reviews_title']) ?>"></div>
          </div>
        </div>
      </div>
    </fieldset>

    <fieldset class="fieldset">
      <legend>Section SAV</legend>
      <div class="admin-public-copy">
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_sav_eyebrow" value="<?= e($homeCopy['sav_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_sav_title" value="<?= e($homeCopy['sav_title']) ?>"></div>
          </div>
          <label>Texte</label>
          <textarea name="home_sav_text" rows="4"><?= e($homeCopy['sav_text']) ?></textarea>
        </div>
        <div class="admin-public-copy-group">
          <label>Image carrée du SAV</label>
          <?php if($savImage): ?><img src="<?= e($savImage->getAttribute('url')) ?>" alt="Image SAV" style="display:block;width:220px;height:220px;object-fit:cover;border-radius:16px;margin:8px 0 14px"><?php endif; ?>
          <input type="file" name="sav_image" accept="image/*">
          <p class="hint">Format conseillé : image carrée, au moins 800 × 800 px.</p>
        </div>
      </div>
    </fieldset>
  </div>
  <?php endif; ?>

  <?php if($page->getAttribute('type')==='home'): ?>
  <div class="card">
    <fieldset class="fieldset">
      <legend>Autres textes de la page d’accueil</legend>
      <div class="admin-public-copy">
        <h3>Marques &amp; partenaires</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_partners_eyebrow" value="<?= e($homeCopy['partners_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_partners_title" value="<?= e($homeCopy['partners_title']) ?>"></div>
          </div>
        </div>
      </div>
      <div class="admin-public-copy">
        <h3>Derniers articles du blog</h3>
        <div class="admin-public-copy-group">
          <div class="row">
            <div><label>Libellé</label><input name="home_blog_eyebrow" value="<?= e($homeCopy['blog_eyebrow']) ?>"></div>
            <div><label>Titre</label><input name="home_blog_title" value="<?= e($homeCopy['blog_title']) ?>"></div>
          </div>
        </div>
      </div>
      <div class="admin-public-copy">
        <h3>Points forts du SAV (3 lignes)</h3>
        <div class="admin-public-copy-group">
          <input name="home_sav_point_1" value="<?= e($homeCopy['sav_point_1']) ?>">
          <input name="home_sav_point_2" value="<?= e($homeCopy['sav_point_2']) ?>">
          <input name="home_sav_point_3" value="<?= e($homeCopy['sav_point_3']) ?>">
        </div>
      </div>
    </fieldset>
  </div>
  <?php endif; ?>
  <div class="actions-bar"><button type="submit">Enregistrer</button></div>
</form>

<div class="card">
  <h3 style="margin-top:0">Blocs de contenu</h3>
  <?php foreach ($blocks as $i => $block): $data = (array) $block->getAttribute('data'); $type = $block->getAttribute('type'); ?>
    <div style="border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:14px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <strong style="text-transform:uppercase;font-size:12px;color:var(--muted)"><?= e($type) ?></strong>
        <div style="display:flex;gap:6px">
          <form method="post" action="/admin/pages/block/<?= (int) $block->id() ?>/move" style="display:inline"><?= csrf_field() ?><input type="hidden" name="direction" value="up"><button type="submit" class="btn secondary sm">&uarr;</button></form>
          <form method="post" action="/admin/pages/block/<?= (int) $block->id() ?>/move" style="display:inline"><?= csrf_field() ?><input type="hidden" name="direction" value="down"><button type="submit" class="btn secondary sm">&darr;</button></form>
          <form method="post" action="/admin/pages/block/<?= (int) $block->id() ?>/delete" style="display:inline"><?= csrf_field() ?><button type="submit" class="btn danger sm">Supprimer</button></form>
        </div>
      </div>
      <form method="post" action="/admin/pages/block/<?= (int) $block->id() ?>">
        <?= csrf_field() ?>
        <?php if (in_array($type, $simpleTypes, true)): ?>
          <?php if ($type === 'heading'): ?>
            <label>Texte</label><input type="text" name="data[text]" value="<?= e($data['text'] ?? '') ?>">
          <?php elseif ($type === 'text'): ?>
            <label>Titre (facultatif)</label><input type="text" name="data[heading]" value="<?= e($data['heading'] ?? '') ?>">
            <label>Contenu</label><textarea name="data[content]" rows="4"><?= e($data['content'] ?? '') ?></textarea>
          <?php elseif ($type === 'list'): ?>
            <label>Titre (facultatif)</label><input type="text" name="data[heading]" value="<?= e($data['heading'] ?? '') ?>">
            <label>Elements (un par ligne)</label><textarea name="data[items]" rows="4"><?= e(implode("\n", (array) ($data['items'] ?? []))) ?></textarea>
          <?php elseif ($type === 'button'): ?>
            <label>Libelle</label><input type="text" name="data[label]" value="<?= e($data['label'] ?? '') ?>">
            <label>URL</label><input type="text" name="data[url]" value="<?= e($data['url'] ?? '') ?>">
          <?php elseif ($type === 'cta'): ?>
            <label>Titre</label><input type="text" name="data[title]" value="<?= e($data['title'] ?? '') ?>">
            <label>Texte</label><textarea name="data[text]" rows="2"><?= e($data['text'] ?? '') ?></textarea>
          <?php endif; ?>
        <?php else: ?>
          <label>Donnees (JSON)<?= in_array($type, ['services', 'projects', 'testimonials', 'service_area'], true) ? ' - ce bloc affiche automatiquement les donnees reelles, aucune configuration requise' : '' ?></label>
          <textarea name="data_json" rows="4"><?= e(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
        <?php endif; ?>
        <div class="checkbox-row">
          <input type="checkbox" id="active_<?= (int) $block->id() ?>" name="is_active" value="1" <?= $block->getAttribute('is_active') ? 'checked' : '' ?>>
          <label for="active_<?= (int) $block->id() ?>">Bloc actif</label>
        </div>
        <button type="submit" class="btn secondary sm">Enregistrer ce bloc</button>
      </form>
    </div>
  <?php endforeach; ?>

  <form method="post" action="/admin/pages/<?= (int) $page->id() ?>/blocks" style="display:flex;gap:10px;align-items:end;margin-top:10px">
    <?= csrf_field() ?>
    <div style="flex:1">
      <label>Ajouter un bloc</label>
      <select name="type">
        <?php foreach ($allTypes as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" style="margin-bottom:14px">Ajouter</button>
  </form>
</div>
