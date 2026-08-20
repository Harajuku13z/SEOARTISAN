<?php
use App\Models\CompanyService;
$services=array_values(array_filter(CompanyService::all('sort_order ASC'),static fn($service)=>(bool)$service->getAttribute('is_active')));
?>
<main class="quote-simulator-page"><section class="ph-section"><span class="ph-eyebrow blue">Votre projet</span><h1>Demande de devis personnalisée</h1><p>Sélectionnez la prestation souhaitée et décrivez votre besoin. Les services affichés correspondent uniquement à ceux activés par l’entreprise.</p>
  <form method="post" action="/devis" data-ajax-form class="unified-quote-form"><?= csrf_field() ?><input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off"><div data-form-error class="alert-error" style="display:none"></div>
    <label>Prestation<select name="company_service_id" required><option value="">Choisissez un service</option><?php foreach($services as $service): ?><option value="<?= (int)$service->id() ?>"><?= e($service->getAttribute('public_name')) ?></option><?php endforeach; ?></select></label>
    <div class="unified-form-row"><label>Nom<input name="name" required></label><label>Téléphone<input type="tel" name="phone" required></label></div>
    <div class="unified-form-row"><label>E-mail<input type="email" name="email"></label><label>Code postal<input name="postal_code"></label></div>
    <label>Ville<input name="city"></label><label>Votre besoin<textarea name="message" rows="6" required></textarea></label>
    <label>Créneau de rappel<select name="time_slot" required><option value="">Choisissez un créneau</option><option value="8h30-10h">8h30 - 10h</option><option value="10h-13h">10h - 13h</option><option value="13h-15h">13h - 15h</option><option value="15h-18h">15h - 18h</option></select></label>
    <button type="submit">Envoyer ma demande</button><small>Vos informations servent uniquement à traiter votre demande.</small>
  </form>
</section></main>
