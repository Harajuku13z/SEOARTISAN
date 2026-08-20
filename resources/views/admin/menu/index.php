<?php $success = flash_message('success'); ?>
<div class="admin-topbar"><div><h1>Menu du site</h1><p>Créez des domaines principaux puis rattachez-leur des sous-menus.</p></div></div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<div class="card" style="border-color:#9cc7d8;background:#f4fbfd"><h3>Créer automatiquement les catégories de services</h3><p>Crée Climatisation, Pompe à chaleur, Adoucisseur d’eau, Ballon thermodynamique et Autres, génère leur page et classe les services existants.</p><form method="post" action="/admin/menu/auto-organize" onsubmit="return confirm('Créer et organiser automatiquement les catégories ?')"><?= csrf_field() ?><button type="submit">Créer et classer les catégories</button></form></div>
<div class="card">
  <h3>1. Créer un domaine principal</h3>
  <p>La page principale et sa route sont créées automatiquement. Exemple : « Climatisation » crée <code>/climatisation</code>.</p>
  <form method="post" action="/admin/menu/domain"><?= csrf_field() ?><div class="row"><div><label>Nom du domaine</label><input name="label" required placeholder="Climatisation, Chauffage, Eau..."></div><div style="align-self:end"><button type="submit">Créer le domaine et sa page</button></div></div></form>
</div>
<div class="card">
  <h3>Ajouter des pages et services existants</h3>
  <p>Cochez les liens à afficher : leur nom et leur route seront renseignés automatiquement.</p>
  <form method="post" action="/admin/menu/selection"><?= csrf_field() ?>
    <label>Domaine parent</label>
    <select name="parent_id"><option value="">Menu principal</option><?php foreach ($items as $item): if (!empty($item['parent_id'])) continue; ?><option value="<?= e($item['id']) ?>"><?= e($item['label']) ?></option><?php endforeach; ?></select>
    <div class="row" style="align-items:start;margin-top:18px">
      <div><h4>Services</h4><div style="display:grid;gap:9px;max-height:360px;overflow:auto;padding:12px;border:1px solid #dfe4ec;border-radius:10px">
        <?php foreach ($services as $service): ?><label class="checkbox-row"><input type="checkbox" name="service_ids[]" value="<?= (int)$service->id() ?>"> <span><?= e($service->getAttribute('public_name')) ?></span></label><?php endforeach; ?>
      </div></div>
      <div><h4>Pages et routes du site</h4><div style="display:grid;gap:9px;max-height:360px;overflow:auto;padding:12px;border:1px solid #dfe4ec;border-radius:10px">
        <?php foreach ($pages as $page): if ($page->getAttribute('company_service_id')) continue; $route = ($page->getAttribute('type')==='home'||$page->getAttribute('slug')==='accueil') ? '/' : '/'.$page->getAttribute('slug'); ?><label class="checkbox-row"><input type="checkbox" name="page_ids[]" value="<?= (int)$page->id() ?>"> <span><?= e($page->getAttribute('title')) ?> <small style="display:block;color:#778195"><?= e($route) ?></small></span></label><?php endforeach; ?>
      </div></div>
    </div>
    <div class="actions-bar"><button type="submit">Ajouter les éléments cochés</button></div>
  </form>
</div>
<div class="card">
  <h3>Ajouter un élément</h3>
  <form method="post" action="/admin/menu"><?= csrf_field() ?>
    <div class="row"><div><label>Nom affiché</label><input name="label" required placeholder="Ex. Chauffage"></div><div><label>Lien</label><input name="url" placeholder="/pompe-a-chaleur ou #services"></div></div>
    <div class="row"><div><label>Domaine parent</label><select name="parent_id"><option value="">Aucun — menu principal</option><?php foreach ($items as $item): if (!empty($item['parent_id'])) continue; ?><option value="<?= e($item['id']) ?>"><?= e($item['label']) ?></option><?php endforeach; ?></select></div><div><label>Position</label><input type="number" name="sort_order" value="<?= count($items)+1 ?>"></div></div>
    <button type="submit">Ajouter au menu</button>
  </form>
</div>
<div class="card"><h3>3. Organiser par glisser-déposer</h3><p>Glissez un élément dans un domaine pour en faire un sous-menu, ou déplacez-le pour changer son ordre.</p>
  <form method="post" action="/admin/menu/reorder" id="menu-sort-form"><?= csrf_field() ?><input type="hidden" name="structure" id="menu-structure">
  <div id="menu-root" class="menu-sort-list">
  <?php foreach ($items as $root): if (!empty($root['parent_id'])) continue; ?>
    <div class="menu-sort-item" draggable="true" data-id="<?= e($root['id']) ?>">
      <div class="menu-sort-row"><span class="drag-handle">☰</span><strong><?= e($root['label']) ?></strong><small><?= e($root['url']) ?></small><span class="badge published">Domaine</span><?php if (($root['url'] ?? '#') === '#'): ?><button class="btn secondary" type="submit" formaction="/admin/menu/<?= e($root['id']) ?>/create-page">Créer sa page</button><?php endif; ?><button class="btn secondary" type="submit" formaction="/admin/menu/<?= e($root['id']) ?>/delete" onclick="return confirm('Supprimer ?')">Supprimer</button></div>
      <div class="menu-children menu-sort-list" data-parent="<?= e($root['id']) ?>"><?php foreach ($items as $child): if (($child['parent_id'] ?? '') !== $root['id']) continue; ?><div class="menu-sort-item child" draggable="true" data-id="<?= e($child['id']) ?>"><div class="menu-sort-row"><span class="drag-handle">☰</span><span><?= e($child['label']) ?></span><small><?= e($child['url']) ?></small><button class="btn secondary" type="submit" formaction="/admin/menu/<?= e($child['id']) ?>/delete" onclick="return confirm('Supprimer ?')">Supprimer</button></div></div><?php endforeach; ?></div>
    </div>
  <?php endforeach; ?>
  <?php foreach ($items as $orphan): if (empty($orphan['parent_id']) || array_filter($items, fn($i)=>$i['id']===($orphan['parent_id']??''))) continue; ?><div class="menu-sort-item" draggable="true" data-id="<?= e($orphan['id']) ?>"><div class="menu-sort-row"><span class="drag-handle">☰</span><span><?= e($orphan['label']) ?></span><small><?= e($orphan['url']) ?></small></div></div><?php endforeach; ?>
  </div><div class="actions-bar"><button type="submit">Enregistrer le menu</button></div></form>
</div>
<style>.menu-sort-list{display:flex;flex-direction:column;gap:10px;min-height:18px}.menu-sort-item{border:1px solid #dfe4ec;border-radius:10px;background:#fff}.menu-sort-item.dragging{opacity:.4}.menu-sort-row{display:flex;align-items:center;gap:12px;padding:12px}.menu-sort-row small{color:#778195;margin-right:auto}.drag-handle{cursor:grab;font-size:20px;color:#778195}.menu-children{margin:0 12px 12px 42px;padding:10px;border:1px dashed #b9c2d0;border-radius:9px;background:#f7f9fc}.menu-children:empty:after{content:'Déposez ici les sous-menus';color:#8a94a5;font-size:13px;padding:5px}</style>
<script>
let dragged=null;
document.querySelectorAll('.menu-sort-item').forEach(el=>{el.addEventListener('dragstart',e=>{dragged=el;el.classList.add('dragging');e.stopPropagation()});el.addEventListener('dragend',()=>{el.classList.remove('dragging');dragged=null})});
document.querySelectorAll('.menu-sort-list').forEach(list=>{list.addEventListener('dragover',e=>{e.preventDefault();e.stopPropagation();if(!dragged)return;const candidates=[...list.children].filter(x=>x.classList.contains('menu-sort-item')&&x!==dragged);const next=candidates.find(x=>e.clientY<x.getBoundingClientRect().top+x.offsetHeight/2);list.insertBefore(dragged,next||null)})});
document.getElementById('menu-sort-form').addEventListener('submit',function(){const rows=[];let order=0;document.querySelectorAll('#menu-root > .menu-sort-item').forEach(root=>{rows.push({id:root.dataset.id,parent_id:'',sort_order:order++});let sub=0;root.querySelectorAll(':scope > .menu-children > .menu-sort-item').forEach(child=>rows.push({id:child.dataset.id,parent_id:root.dataset.id,sort_order:sub++}))});document.getElementById('menu-structure').value=JSON.stringify(rows)});
</script>
