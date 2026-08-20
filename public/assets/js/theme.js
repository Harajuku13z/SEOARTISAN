(function () {
  document.querySelectorAll('.ph-cities[data-city-pool]').forEach(function (el) {
    var pool = [];
    try { pool = JSON.parse(el.getAttribute('data-city-pool') || '[]'); } catch (err) { pool = []; }
    var count = parseInt(el.getAttribute('data-city-count'), 10) || 5;
    for (var i = pool.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = pool[i]; pool[i] = pool[j]; pool[j] = tmp;
    }
    pool.slice(0, count).forEach(function (city) {
      var span = document.createElement('span');
      span.textContent = city;
      el.appendChild(span);
    });
  });

  var toggle = document.querySelector('.mobile-toggle');
  var nav = document.querySelector('.site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      nav.classList.toggle('open');
    });
  }

  var servicesMenu = document.querySelector('.services-menu');
  var servicesTrigger = document.querySelector('.services-trigger');
  var mobileMenu = window.matchMedia('(max-width: 900px)');
  if (servicesMenu && servicesTrigger) {
    servicesTrigger.addEventListener('click', function (event) {
      event.preventDefault();
      var opened = servicesMenu.classList.toggle('mobile-open');
      servicesTrigger.setAttribute('aria-expanded', opened ? 'true' : 'false');
    });
  }

  document.querySelectorAll('.mega-title').forEach(function (title) {
    title.addEventListener('click', function (event) {
      event.preventDefault();
      var group = title.closest('.mega-group');
      var willOpen = group && !group.classList.contains('accordion-open');
      document.querySelectorAll('.mega-group.accordion-open').forEach(function (other) {
        other.classList.remove('accordion-open');
        var otherTitle = other.querySelector('.mega-title');
        if (otherTitle) { otherTitle.setAttribute('aria-expanded', 'false'); }
      });
      if (group && willOpen) {
        group.classList.add('accordion-open');
        title.setAttribute('aria-expanded', 'true');
      }
    });
  });

  document.addEventListener('click', function (event) {
    if (!servicesMenu || servicesMenu.contains(event.target)) { return; }
    servicesMenu.classList.remove('mobile-open');
    if (servicesTrigger) { servicesTrigger.setAttribute('aria-expanded', 'false'); }
  });

  var slider = document.querySelector('[data-hero-slider]');
  if (slider) {
    var slides = slider.querySelectorAll('.slide');
    var dots = slider.querySelectorAll('.dots button');
    var current = 0;
    var timer = null;

    function show(index) {
      slides[current].classList.remove('active');
      if (dots[current]) { dots[current].classList.remove('active'); }
      current = index;
      slides[current].classList.add('active');
      if (dots[current]) { dots[current].classList.add('active'); }
    }

    function next() {
      show((current + 1) % slides.length);
    }

    if (slides.length > 1) {
      timer = setInterval(next, 4500);
      dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
          clearInterval(timer);
          show(parseInt(dot.getAttribute('data-slide-index'), 10));
          timer = setInterval(next, 4500);
        });
      });
    }
  }

  document.querySelectorAll('form[data-ajax-form]').forEach(function (form) {
    var emailField = form.querySelector('input[name="email"]');
    if (emailField) { emailField.required = true; }
    var textarea = form.querySelector('textarea[name="message"]');
    var insertionPoint = textarea || form.querySelector('button[type="submit"], button');
    if (!form.querySelector('[name="city"]') && insertionPoint) {
      var cityField = document.createElement('input');
      cityField.name = 'city'; cityField.placeholder = 'Votre ville';
      insertionPoint.parentNode.insertBefore(cityField, insertionPoint);
    }
    if (!form.querySelector('[name="time_slot"]') && insertionPoint) {
      var slotField = document.createElement('select');
      slotField.name = 'time_slot'; slotField.required = true;
      slotField.innerHTML = '<option value="">Créneau de rappel souhaité</option><option value="matin">Le matin</option><option value="apresmidi">L’après-midi</option><option value="soir">En soirée</option><option value="urgence">Dès que possible — urgence</option>';
      insertionPoint.parentNode.insertBefore(slotField, insertionPoint);
    }
    var callbackField = form.querySelector('[name="time_slot"]');
    if (callbackField) { callbackField.required = true; }
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      var submitBtn = form.querySelector('button[type=submit]');
      var successBox = form.parentElement.querySelector('[data-form-success]');
      var errorBox = form.querySelector('[data-form-error]');
      if (submitBtn) { submitBtn.disabled = true; }
      if (errorBox) { errorBox.style.display = 'none'; }

      try {
        var res = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { 'Accept': 'application/json' }
        });
        var json = await res.json();
        if (json.ok) {
          window.location.href = json.redirect || '/succes';
          return;
          form.style.display = 'none';
          if (successBox) { successBox.style.display = 'block'; }
        } else if (errorBox) {
          errorBox.textContent = json.message || 'Une erreur est survenue, veuillez reessayer.';
          errorBox.style.display = 'block';
        }
      } catch (err) {
        if (errorBox) {
          errorBox.textContent = 'Erreur reseau, veuillez reessayer.';
          errorBox.style.display = 'block';
        }
      } finally {
        if (submitBtn) { submitBtn.disabled = false; }
      }
    });
  });

  var lightboxSelector = '.gallery-grid img, .before-after img, .ph-project-grid img';
  var overlay = null;
  var overlayImg = null;
  var overlayCaption = null;
  var lastFocused = null;
  var lightboxImages = [];
  var lightboxIndex = 0;

  function buildOverlay() {
    overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.innerHTML = '<button type="button" class="lightbox-close" aria-label="Fermer">&times;</button>' +
      '<button type="button" class="lightbox-nav prev" aria-label="Photo precedente">&lsaquo;</button>' +
      '<button type="button" class="lightbox-nav next" aria-label="Photo suivante">&rsaquo;</button>' +
      '<img alt=""><figcaption></figcaption>';
    document.body.appendChild(overlay);
    overlayImg = overlay.querySelector('img');
    overlayCaption = overlay.querySelector('figcaption');
    overlay.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
    overlay.querySelector('.lightbox-nav.prev').addEventListener('click', function (event) { event.stopPropagation(); showLightboxIndex(lightboxIndex - 1); });
    overlay.querySelector('.lightbox-nav.next').addEventListener('click', function (event) { event.stopPropagation(); showLightboxIndex(lightboxIndex + 1); });
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) { closeLightbox(); }
    });
  }

  function showLightboxIndex(index) {
    if (!lightboxImages.length) { return; }
    lightboxIndex = (index + lightboxImages.length) % lightboxImages.length;
    var img = lightboxImages[lightboxIndex];
    overlayImg.src = img.currentSrc || img.src;
    overlayImg.alt = img.alt || '';
    overlayCaption.textContent = img.alt || '';
    var showNav = lightboxImages.length > 1;
    overlay.querySelector('.lightbox-nav.prev').style.display = showNav ? '' : 'none';
    overlay.querySelector('.lightbox-nav.next').style.display = showNav ? '' : 'none';
  }

  function openLightbox(img) {
    if (!overlay) { buildOverlay(); }
    lightboxImages = Array.prototype.slice.call(document.querySelectorAll(lightboxSelector));
    var index = lightboxImages.indexOf(img);
    showLightboxIndex(index === -1 ? 0 : index);
    lastFocused = document.activeElement;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    overlay.querySelector('.lightbox-close').focus();
  }

  function closeLightbox() {
    if (!overlay) { return; }
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    if (lastFocused) { lastFocused.focus(); }
  }

  document.addEventListener('click', function (event) {
    var img = event.target.closest ? event.target.closest(lightboxSelector) : null;
    if (img) { openLightbox(img); }
  });

  document.addEventListener('keydown', function (event) {
    if (!overlay || !overlay.classList.contains('open')) { return; }
    if (event.key === 'Escape') { closeLightbox(); }
    else if (event.key === 'ArrowRight') { showLightboxIndex(lightboxIndex + 1); }
    else if (event.key === 'ArrowLeft') { showLightboxIndex(lightboxIndex - 1); }
  });

  var touchStartX = null;
  document.addEventListener('touchstart', function (event) {
    if (overlay && overlay.classList.contains('open') && event.touches.length === 1) {
      touchStartX = event.touches[0].clientX;
    }
  });
  document.addEventListener('touchend', function (event) {
    if (touchStartX === null || !overlay || !overlay.classList.contains('open')) { return; }
    var deltaX = event.changedTouches[0].clientX - touchStartX;
    touchStartX = null;
    if (Math.abs(deltaX) < 40) { return; }
    showLightboxIndex(deltaX > 0 ? lightboxIndex - 1 : lightboxIndex + 1);
  });
})();
