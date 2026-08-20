<?php
/** @var array<int,\App\Models\Project> $projects */
/** @var array<int,array{media:\App\Models\Media,title:string}> $videos */
$success = flash_message('success');
$errors = flash_message('_errors');
?>
<div class="admin-topbar">
  <h1>Realisations</h1>
  <a class="btn" href="/admin/projects/create">Ajouter une realisation</a>
</div>
<?php if ($success): ?><div class="alert ok"><?= e($success) ?></div><?php endif; ?>
<?php if (is_array($errors) && isset($errors['form'])): ?><div class="alert error"><?= e($errors['form']) ?></div><?php endif; ?>

<table>
  <tr><th>Titre</th><th>Categorie</th><th>Date</th><th>Visible</th><th></th></tr>
  <?php foreach ($projects as $project): ?>
    <tr>
      <td><?= e($project->getAttribute('title')) ?></td>
      <td><?= e($project->getAttribute('category')) ?></td>
      <td><?= e($project->getAttribute('project_date')) ?></td>
      <td><span class="badge <?= $project->getAttribute('is_visible') ? 'published' : 'archived' ?>"><?= $project->getAttribute('is_visible') ? 'oui' : 'non' ?></span></td>
      <td style="display:flex;gap:8px">
        <a class="btn secondary sm" href="/admin/projects/<?= (int) $project->id() ?>">Modifier</a>
        <form method="post" action="/admin/projects/<?= (int) $project->id() ?>/delete" onsubmit="return confirm('Supprimer cette realisation ?')">
          <?= csrf_field() ?><button type="submit" class="btn danger sm">Supprimer</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<div class="card" style="margin-top:28px">
  <h2>Ils parlent de nous — vidéos</h2>
  <p>Ces vidéos apparaissent dans l’onglet « Ils parlent de nous » de la page Réalisations.</p>
  <?php if ($videos !== []): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin:20px 0">
      <?php foreach ($videos as $video): ?>
        <article style="border:1px solid #d8e0e5;border-radius:12px;padding:12px">
          <video controls preload="metadata" poster="<?= e(preg_replace('/\.mp4$/i', '-poster.png', (string) $video['media']->getAttribute('url'))) ?>" style="display:block;width:100%;aspect-ratio:16/9;background:#102f3d;border-radius:8px;object-fit:cover">
            <source src="<?= e($video['media']->getAttribute('url')) ?>" type="<?= e($video['media']->getAttribute('mime_type')) ?>">
          </video>
          <strong style="display:block;margin:10px 0"><?= e($video['title']) ?></strong>
          <form method="post" action="/admin/projects/videos/<?= (int) $video['media']->id() ?>/delete" onsubmit="return confirm('Supprimer cette vidéo ?')">
            <?= csrf_field() ?><button type="submit" class="btn danger sm">Supprimer</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post" action="/admin/projects/videos" enctype="multipart/form-data" style="display:grid;gap:12px;max-width:680px">
    <?= csrf_field() ?>
    <label>Titre de la vidéo<input type="text" name="video_title" placeholder="Ex. Notre entreprise dans les médias"></label>
    <label>Fichier vidéo (MP4, WebM ou MOV — 200 Mo maximum)<input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" required></label>
    <button type="submit" class="btn">Ajouter la vidéo</button>
  </form>
</div>
