<?php
$name=(string)($company?->getAttribute('trade_name')?:config('app.name','Votre artisan'));
$phone=(string)($company?->getAttribute('phone')??'');
$phoneHref=preg_replace('/\D+/','',$phone);
?>
<main class="success-page">
  <section class="success-card">
    <div class="success-check" aria-hidden="true">✓</div>
    <span class="success-eyebrow">Demande transmise</span>
    <h1>Merci, votre demande<br>a bien été envoyée.</h1>
    <p>L’équipe <?= e($name) ?> a reçu vos informations et vous recontactera rapidement. Pour un besoin urgent, vous pouvez nous appeler immédiatement.</p>
    <div class="success-actions">
      <?php if($phone): ?><a class="success-call" href="tel:<?= e($phoneHref) ?>"><span>☎</span> Appeler maintenant — <?= e($phone) ?></a><?php endif; ?>
      <a class="success-home" href="/">Retour à l’accueil</a>
    </div>
  </section>
</main>
