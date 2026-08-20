<?php
use App\Models\CompanyService;
$quotePhone=(string)($company?->getAttribute('phone')??'');
$quotePhoneHref=preg_replace('/\D+/','',$quotePhone);
$quoteEmail=(string)($company?->getAttribute('public_email')??'');
$quoteAddress=trim(implode(' ',array_filter([$company?->getAttribute('address'),$company?->getAttribute('postal_code'),$company?->getAttribute('city')])));
$quoteCompanyName=(string)($company?->getAttribute('trade_name')?:config('app.name','notre entreprise'));
$quoteServices=CompanyService::all('sort_order ASC');
$selectedServiceId=(int)($currentService?->id()??0);
?>
<section class="unified-quote" id="demande-devis"><span id="devis" class="quote-anchor" aria-hidden="true"></span>
  <div class="unified-quote-copy">
    <span class="ph-eyebrow">Parlons de votre projet</span><h2>Devis gratuit,<br>sans engagement</h2>
    <p>Expliquez-nous votre besoin : l’équipe <?= e($quoteCompanyName) ?> vous rappelle rapidement.</p>
    <div class="unified-quote-actions"><?php if($quotePhone!==''): ?><a class="quote-call" href="tel:<?= e($quotePhoneHref) ?>">☎ Appelez maintenant</a><?php endif; ?><a class="quote-simulator" href="/simulateur-de-devis">Simulateur de devis →</a></div>
    <div class="unified-quote-contact"><?php if($quotePhone!==''): ?><a href="tel:<?= e($quotePhoneHref) ?>">Téléphone : <?= e($quotePhone) ?></a><?php endif; ?><?php if($quoteEmail!==''): ?><a href="mailto:<?= e($quoteEmail) ?>">E-mail : <?= e($quoteEmail) ?></a><?php endif; ?><?php if($quoteAddress!==''): ?><span><?= e($quoteAddress) ?></span><?php endif; ?></div>
  </div>
  <div class="unified-quote-form"><form method="post" action="/devis" data-ajax-form>
    <?= csrf_field() ?><input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off"><div data-form-error class="alert-error" style="display:none"></div>
    <div class="unified-form-row"><input name="name" placeholder="Votre nom" required><input type="tel" name="phone" placeholder="Votre téléphone" required></div>
    <div class="unified-form-row"><input type="email" name="email" placeholder="Votre e-mail" required><input name="postal_code" placeholder="Code postal"></div>
    <select name="company_service_id"><option value="">Votre besoin</option><?php foreach($quoteServices as $quoteService):if(!$quoteService->getAttribute('is_active'))continue; ?><option value="<?= (int)$quoteService->id() ?>" <?= $selectedServiceId===(int)$quoteService->id()?'selected':'' ?>><?= e($quoteService->getAttribute('public_name')) ?></option><?php endforeach; ?></select>
    <input name="city" placeholder="Votre ville"><select name="time_slot" required><option value="">Créneau de rappel souhaité</option><option value="8h30-10h">8h30 - 10h</option><option value="10h-13h">10h - 13h</option><option value="13h-15h">13h - 15h</option><option value="15h-18h">15h - 18h</option></select>
    <textarea name="message" rows="4" placeholder="Décrivez votre projet"></textarea><button type="submit">Recevoir mon devis gratuit</button><small>Vos informations servent uniquement à répondre à votre demande.</small>
  </form></div>
</section>
