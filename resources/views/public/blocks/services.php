<?php
use App\Core\Database;
use App\Models\Company;
use App\Models\Media;
use App\Repositories\CompanyServiceRepository;

$company = Company::current();
if ($company === null) {
    return;
}
$services = (new CompanyServiceRepository(Database::instance()))->forCompany((int) $company->id(), true);
if ($services === []) {
    return;
}

$aboutText = $company->getAttribute('editorial_presentation') ?: $company->getAttribute('long_description') ?: $company->getAttribute('short_description');
$aboutMedia = null;
if ($company->getAttribute('hero_media_id')) {
    $aboutMedia = Media::find((int) $company->getAttribute('hero_media_id'));
}

$certifications = (array) ($company->getAttribute('certifications') ?? []);
$brandsRaw = (string) ($company->getAttribute('editorial_brands_used') ?? '');
$brands = $brandsRaw !== '' ? array_filter(array_map('trim', preg_split('/[,;\n]/', $brandsRaw))) : [];
?>
<section class="section" id="services">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Nos prestations</span>
        <h2>Nos services</h2>
      </div>
      <p>Une equipe qualifiee pour des interventions rapides et des solutions durables, de l'installation a l'entretien.</p>
    </div>
    <div class="services-grid">
      <?php foreach ($services as $service): ?>
        <a class="service-card" href="/<?= e($service['slug']) ?>">
          <div class="thumb">
            <?php if (!empty($service['image_url'])): ?>
              <img src="<?= e($service['image_url']) ?>" alt="<?= e($service['image_alt'] ?? $service['public_name']) ?>" loading="lazy">
            <?php endif; ?>
          </div>
          <div class="body">
            <?php if (!empty($service['is_emergency'])): ?><span class="badge-emergency">Urgence</span><?php endif; ?>
            <strong><?= e($service['public_name']) ?></strong>
            <?php if (!empty($service['description'])): ?><p><?= e($service['description']) ?></p><?php endif; ?>
            <span class="more">En savoir plus &rarr;</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($aboutText): ?>
      <div class="panel-dark">
        <div>
          <span class="eyebrow">A propos, express</span>
          <h3>Une entreprise a taille humaine</h3>
          <p><?= e($aboutText) ?></p>
          <div class="stats">
            <?php if ($company->getAttribute('offers_emergency')): ?>
              <div><b>24/24</b><span>depannage &amp; entretien</span></div>
            <?php endif; ?>
            <?php if ($company->getAttribute('service_radius_km')): ?>
              <div><b><?= e($company->getAttribute('service_radius_km')) ?> km</b><span>zone d'intervention</span></div>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($aboutMedia): ?>
          <div class="media"><img src="<?= e($aboutMedia->getAttribute('url')) ?>" alt="<?= e($aboutMedia->getAttribute('alt_text') ?: $company->getAttribute('trade_name')) ?>" loading="lazy"></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($brands !== []): ?>
  <section class="section">
    <div class="container">
      <div class="brands-strip">
        <span class="eyebrow">Marques &amp; partenaires</span>
        <div class="row">
          <?php foreach ($brands as $brand): ?><span><?= e($brand) ?></span><?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($certifications !== []): ?>
  <section class="section">
    <div class="container">
      <div class="certifications-grid">
        <?php foreach ($certifications as $cert): ?>
          <div class="certification-card">
            <span class="abbr"><?= e(mb_strtoupper(mb_substr((string) $cert, 0, 2))) ?></span>
            <div><strong><?= e($cert) ?></strong></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>
