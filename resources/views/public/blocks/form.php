<?php
/** @var array<string,mixed> $data */
use App\Models\Company;
use App\Repositories\CompanyServiceRepository;
use App\Core\Database;

$formType = (string) ($data['form_type'] ?? 'quote');
$company = Company::current();
$services = [];
if ($company !== null) {
    $services = (new CompanyServiceRepository(Database::instance()))->forCompany((int) $company->id(), true);
}
$action = $formType === 'contact' ? '/contact' : '/devis';
$phone = $company?->getAttribute('phone');

ob_start();
?>
<div class="form-card">
  <div data-form-success style="display:none" class="form-success">
    <div class="check">&#10003;</div>
    <h3>Merci, votre demande est envoyee !</h3>
    <p>Un membre de notre equipe vous recontacte rapidement.</p>
  </div>
  <form method="post" action="<?= e($action) ?>" data-ajax-form>
    <?= csrf_field() ?>
    <input type="text" name="website" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off">
    <h3><?= $formType === 'contact' ? 'Nous contacter' : 'Recevoir mon devis gratuit' ?></h3>
    <?php if ($formType !== 'contact' && $company?->getAttribute('offers_emergency')): ?>
      <div class="form-kicker">Intervention rapide - urgences prises en charge 24/24</div>
    <?php endif; ?>
    <div data-form-error class="alert-error" style="display:none"></div>
    <div class="form-row">
      <input type="text" name="name" placeholder="Nom et prenom" required>
      <input type="tel" name="phone" placeholder="Telephone" required>
    </div>
    <input type="email" name="email" placeholder="Adresse email" required>
    <div class="form-row">
      <input type="text" name="postal_code" placeholder="Code postal">
      <input type="text" name="city" placeholder="Ville">
    </div>
    <?php if ($formType !== 'contact' && $services !== []): ?>
      <select name="company_service_id">
        <option value="">Service souhaite</option>
        <?php foreach ($services as $service): ?>
          <option value="<?= e($service['id']) ?>"><?= e($service['public_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="time_slot">
        <option value="">Etre rappele(e) plutot...</option>
        <option value="8h30-10h">8h30 - 10h</option>
        <option value="10h-13h">10h - 13h</option>
        <option value="13h-15h">13h - 15h</option>
        <option value="15h-18h">15h - 18h</option>
      </select>
    <?php endif; ?>
    <textarea name="message" rows="3" placeholder="Decrivez votre projet ou votre demande..."></textarea>
    <button type="submit" class="btn primary"><?= $formType === 'contact' ? 'Envoyer' : 'Recevoir mon devis gratuit' ?></button>
    <p class="fine-print">Vos donnees ne sont utilisees que pour vous repondre. Sans engagement.</p>
  </form>
</div>
<?php
$formCard = ob_get_clean();

if ($formType === 'contact') {
    echo '<section class="section" style="max-width:520px;margin:0 auto">' . $formCard . '</section>';

    return;
}
?>
<section class="cta-split" id="quote-form">
  <div class="container">
    <div>
      <span class="eyebrow">Devis gratuit</span>
      <h2>Un projet en tete ?</h2>
      <p class="lead">Decrivez votre besoin, nous revenons vers vous rapidement avec un devis gratuit et sans engagement.</p>
      <div class="checklist">
        <span><span class="dot"></span> Reponse rapide</span>
        <?php if ($company?->getAttribute('offers_free_quote')): ?><span><span class="dot"></span> Devis gratuit et sans engagement</span><?php endif; ?>
        <?php if ($company?->getAttribute('offers_emergency')): ?><span><span class="dot"></span> Intervention 24/24 en cas d'urgence</span><?php endif; ?>
      </div>
      <?php if ($phone): ?>
        <a class="phone-link" href="tel:<?= e(preg_replace('/\s+/', '', (string) $phone)) ?>">Ou appelez : <?= e($phone) ?></a>
      <?php endif; ?>
    </div>
    <?= $formCard ?>
  </div>
</section>
