<?php
/**
 * @var array<int,array{label:string,pass:bool,critical:bool,detail:string}> $checks
 * @var bool $canProceed
 */
?>
<h1>Verification technique</h1>
<p class="subtitle">Verification automatique de la compatibilite de votre hebergement avant de commencer.</p>

<ul class="check-list">
  <?php foreach ($checks as $check): ?>
    <li>
      <span><?= e($check['label']) ?> <span class="small">— <?= e($check['detail']) ?></span></span>
      <span class="<?= $check['pass'] ? 'ok' : 'fail' ?>"><?= $check['pass'] ? 'OK' : ($check['critical'] ? 'BLOQUANT' : 'ATTENTION') ?></span>
    </li>
  <?php endforeach; ?>
</ul>

<?php if (!$canProceed): ?>
  <div class="alert error">Certaines verifications bloquantes ont echoue. Corrigez-les (ou contactez votre hebergeur) puis rechargez cette page.</div>
<?php endif; ?>

<div class="actions">
  <span></span>
  <a class="btn" href="/install/database" style="<?= $canProceed ? '' : 'pointer-events:none;opacity:.5' ?>">Continuer</a>
</div>
