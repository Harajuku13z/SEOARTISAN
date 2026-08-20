<?php
use App\Models\Media;
use App\Models\Project;
use App\Models\Setting;

$mediaUrl = static fn (?int $id): ?string => $id === null ? null : Media::find($id)?->getAttribute('url');
$pairs = [];
$singles = [];
foreach (Project::visible() as $project) {
    $before = $mediaUrl($project->getAttribute('before_media_id'));
    $after = $mediaUrl($project->getAttribute('after_media_id'));
    if ($before && $after) {
        $pairs[] = ['project' => $project, 'before' => $before, 'after' => $after];
    } elseif ($after ?? $before) {
        $singles[] = ['project' => $project, 'url' => $after ?? $before];
    }
}

$videos = [];
$entries = json_decode((string) (Setting::first(['key' => 'content.realisation_videos'])?->getAttribute('value') ?? '[]'), true) ?: [];
foreach ($entries as $entry) {
    $media = Media::find((int) ($entry['media_id'] ?? 0));
    if ($media !== null) {
        $url = (string) $media->getAttribute('url');
        $videos[] = [
            'media' => $media,
            'title' => (string) ($entry['title'] ?? 'Ils parlent de nous'),
            'poster' => preg_replace('/\.mp4$/i', '-poster.png', $url),
        ];
    }
}
if ($pairs === [] && $singles === [] && $videos === []) return;
?>

<?php if ($videos !== []): ?>
<section class="section realisation-videos-section" id="ils-parlent-de-nous">
  <div class="container">
    <span class="eyebrow">Ils parlent de nous</span>
    <h2><?= e($company?->getAttribute('trade_name')?:config('app.name')) ?> dans les médias</h2>
    <p class="realisation-section-intro">Découvrez les reportages et vidéos consacrés à notre entreprise et à notre savoir-faire.</p>
    <div class="realisation-video-grid">
      <?php foreach ($videos as $video): ?>
        <article class="realisation-video-card">
          <video controls preload="metadata" playsinline poster="<?= e($video['poster']) ?>" aria-label="<?= e($video['title']) ?>">
            <source src="<?= e($video['media']->getAttribute('url')) ?>" type="<?= e($video['media']->getAttribute('mime_type')) ?>">
            Votre navigateur ne prend pas en charge cette vidéo.
          </video>
          <div><span>Reportage vidéo</span><h3><?= e($video['title']) ?></h3></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($pairs !== [] || $singles !== []): ?>
<section class="section realisation-projects-section" id="realisations">
  <div class="container">
    <span class="eyebrow">Réalisations</span>
    <h2>Nos derniers chantiers</h2>
    <p class="realisation-section-intro">Découvrez nos installations et interventions réalisées chez nos clients.</p>
    <?php if ($pairs !== []): ?>
      <div class="services-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));margin-top:24px">
        <?php foreach ($pairs as $pair): ?>
          <div><div class="before-after">
            <div><img src="<?= e($pair['before']) ?>" alt="Avant - <?= e($pair['project']->getAttribute('title')) ?>" loading="lazy"><span class="before-label">Avant</span></div>
            <div><img src="<?= e($pair['after']) ?>" alt="Après - <?= e($pair['project']->getAttribute('title')) ?>" loading="lazy"><span class="after-label">Après</span></div>
          </div><div class="before-after-caption"><?= e($pair['project']->getAttribute('title')) ?></div></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($singles !== []): ?>
      <div class="gallery-grid">
        <?php foreach ($singles as $i => $single): ?>
          <figure class="<?= $i === 0 ? 'wide' : '' ?>"><img src="<?= e($single['url']) ?>" alt="<?= e($single['project']->getAttribute('alt_text') ?: $single['project']->getAttribute('title')) ?>" loading="lazy"><figcaption><?= e($single['project']->getAttribute('title')) ?></figcaption></figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<style>
.realisation-videos-section{background:linear-gradient(135deg,#eef7fa 0%,#fff 68%)}
.realisation-projects-section{background:#f7f8f6}
.realisation-section-intro{max-width:700px;color:#66747d;font-size:1.05rem}
.realisation-video-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;margin-top:30px}
.realisation-video-card{overflow:hidden;border:1px solid #d7e0e4;border-radius:18px;background:#fff;box-shadow:0 14px 38px rgba(17,48,61,.1)}
.realisation-video-card video{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#102f3d}
.realisation-video-card>div{padding:17px 20px 20px}.realisation-video-card span{font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#e3a009}
.realisation-video-card h3{margin:6px 0 0;color:var(--color-primary,#0c5668)}
@media(max-width:640px){.realisation-video-grid{grid-template-columns:1fr}.realisation-video-card{border-radius:14px}}
</style>
