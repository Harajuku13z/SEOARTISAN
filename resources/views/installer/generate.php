<?php
/** @var int $total */
?>
<h1>Generation du site</h1>
<p class="subtitle">Generation des pages principales et des textes. En cas d'echec ponctuel, un brouillon clairement identifie est cree - vous pourrez relancer la generation depuis l'administration.</p>

<progress id="progress-bar" value="0" max="<?= (int) $total ?>"></progress>
<p class="small" id="progress-label">Preparation...</p>

<div id="done-actions" style="display:none" class="actions">
  <span></span>
  <a class="btn" href="/admin" id="go-admin">Aller a l'administration</a>
</div>

<script>
(async function () {
  const bar = document.getElementById('progress-bar');
  const label = document.getElementById('progress-label');
  const csrfToken = '<?= e(csrf_token()) ?>';

  async function step() {
    const res = await fetch('/install/generate/next', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
      body: '_csrf_token=' + encodeURIComponent(csrfToken)
    });
    const json = await res.json();
    bar.value = json.current;
    bar.max = json.total;
    if (json.label) {
      label.textContent = 'Genere : ' + json.label + ' (' + json.current + '/' + json.total + ')';
    }
    if (json.done) {
      label.textContent = 'Generation terminee (' + json.total + '/' + json.total + '). Finalisation...';
      const finishRes = await fetch('/install/generate/finish', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: '_csrf_token=' + encodeURIComponent(csrfToken)
      });
      const finishJson = await finishRes.json();
      label.textContent = 'Termine ! Redirection...';
      document.getElementById('done-actions').style.display = 'flex';
      setTimeout(function () { window.location.href = finishJson.redirect || '/admin'; }, 800);
      return;
    }
    step();
  }

  step();
})();
</script>
