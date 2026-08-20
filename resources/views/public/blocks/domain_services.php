<?php
use App\Models\CompanyService;
use App\Models\Media;
use App\Services\Content\MenuService;
$menuId = (string)($data['menu_id'] ?? '');
$services = [];
foreach (MenuService::items() as $item) {
    if (($item['parent_id'] ?? '') !== $menuId || empty($item['active'])) continue;
    $slug = ltrim((string)($item['url'] ?? ''), '/');
    $service = CompanyService::first(['slug'=>$slug]);
    if ($service) $services[] = $service;
}
if (!$services) return;
?>
<section class="section"><div class="container"><div class="section-head"><div><span class="eyebrow">Nos prestations</span><h2><?= e($data['title'] ?? 'Nos services') ?></h2></div></div><div class="services-grid">
<?php foreach ($services as $service): $media=$service->getAttribute('image_media_id') ? Media::find((int)$service->getAttribute('image_media_id')) : null; ?><a class="service-card" href="/<?= e($service->getAttribute('slug')) ?>"><?php if ($media): ?><div class="thumb"><img src="<?= e($media->getAttribute('url')) ?>" alt="<?= e($service->getAttribute('public_name')) ?>" loading="lazy"></div><?php endif; ?><div class="body"><strong><?= e($service->getAttribute('public_name')) ?></strong><p><?= e($service->getAttribute('description')) ?></p><span class="more">Découvrir le service →</span></div></a><?php endforeach; ?>
</div></div></section>
