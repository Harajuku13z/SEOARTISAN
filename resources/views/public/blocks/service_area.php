<?php
use App\Core\Database;
use App\Models\Company;
use App\Repositories\CityRepository;

$company = Company::current();
if ($company === null) {
    return;
}
$cities = (new CityRepository(Database::instance()))->forCompany((int) $company->id());
if ($cities === []) {
    return;
}
$primary = null;
foreach ($cities as $c) {
    if (!empty($c['is_primary'])) {
        $primary = $c;
        break;
    }
}
?>
<section class="section" id="zones">
  <div class="container">
    <span class="eyebrow">Zone d'intervention</span>
    <h2 style="margin-bottom:10px">Ou intervenons-nous ?</h2>
    <?php if ($primary): ?>
      <p style="color:var(--color-ink-soft);max-width:640px">
        Basee a <?= e($primary['name']) ?><?= $company->getAttribute('service_radius_km') ? ', ' . e($company->getAttribute('trade_name')) . ' intervient dans un rayon de ' . e($company->getAttribute('service_radius_km')) . ' km' : '' ?>.
      </p>
    <?php endif; ?>
    <div class="zone-list" style="margin-top:20px">
      <?php foreach ($cities as $city): ?>
        <span class="zone-chip <?= !empty($city['is_primary']) ? 'primary' : '' ?>"><?= e($city['name']) ?><?= $city['postal_code'] ? ' (' . e($city['postal_code']) . ')' : '' ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>
