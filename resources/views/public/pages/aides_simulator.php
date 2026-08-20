<?php
$phone = (string)($company?->getAttribute('phone') ?? '');
$phoneHref = preg_replace('/\D+/', '', $phone);
?>
<main class="aides-simulator-page" style="font-family: Inter, system-ui, sans-serif; background: var(--nr-bone); padding: 60px 20px; min-height: 80vh; box-sizing: border-box;">
  <div style="max-width: 800px; margin: 0 auto;">
    
    <!-- Hero / Title -->
    <div style="text-align: center; margin-bottom: 40px;">
      <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.18em; font-weight: 800; color: var(--nr-sky); display: inline-block; margin-bottom: 8px;">Rénovation Énergétique</span>
      <h1 style="font-family: Anton, sans-serif; font-weight: 400; text-transform: uppercase; font-size: 42px; color: var(--nr-teal); margin: 0 0 16px;">Simulateur d'Aides 2026</h1>
      <p style="color: var(--nr-stone-500); font-size: 16px; max-width: 600px; margin: 0 auto; line-height: 1.5;">
        Calculez en quelques clics le montant de vos subventions de l'État (MaPrimeRénov') et de nos primes CEE (Certificats d'Économies d'Énergie) pour vos travaux.
      </p>
    </div>

    <!-- Wizard Box -->
    <div style="background: #fff; border-radius: 24px; border: 1.5px solid var(--nr-stone-200); box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 40px; box-sizing: border-box; position: relative; overflow: hidden;">
      
      <!-- Progress Bar -->
      <div style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
          <strong style="color: var(--nr-teal); font-size: 14px;">Progression de la simulation</strong>
          <span style="color: var(--nr-stone-500); font-size: 13px; font-weight: 700;"><span id="step-num">1</span> / 4</span>
        </div>
        <div style="width: 100%; height: 6px; background: var(--nr-stone-200); border-radius: 10px; overflow: hidden;">
          <div id="step-progress-bar" style="width: 25%; height: 100%; background: var(--nr-yellow); transition: width 0.3s ease;"></div>
        </div>
      </div>

      <!-- Form Wrapper -->
      <form id="sim-form" method="post" action="/devis" data-ajax-form>
        <?= csrf_field() ?>
        <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off" style="display: none;">
        <input type="hidden" name="source_url" value="/simulateur-aides">
        
        <!-- STEP 1: Logement -->
        <fieldset class="sim-step" data-step="1" style="border: none; padding: 0; margin: 0;">
          <legend style="font-family: Anton, sans-serif; font-size: 24px; color: var(--nr-teal); margin-bottom: 24px; text-transform: uppercase;">1. Votre logement</legend>
          
          <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 700; color: var(--nr-teal); margin-bottom: 10px;">Type de propriété</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
                <input type="radio" name="property_type" value="maison" checked style="display:none;">
                <span style="font-size: 32px; margin-bottom: 8px;">🏠</span>
                Maison
              </label>
              <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
                <input type="radio" name="property_type" value="appartement" style="display:none;">
                <span style="font-size: 32px; margin-bottom: 8px;">🏢</span>
                Appartement
              </label>
            </div>
          </div>

          <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 700; color: var(--nr-teal); margin-bottom: 10px;">Ancienneté de la construction</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
                <input type="radio" name="property_age" value="plus_2" checked style="display:none;">
                Plus de 2 ans
              </label>
              <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
                <input type="radio" name="property_age" value="moins_2" style="display:none;">
                Moins de 2 ans
              </label>
            </div>
            <div id="age-warning" style="display: none; background: #fff5f5; color: #c92a2a; padding: 12px 16px; border-radius: 8px; border: 1px solid rgba(201,42,42,0.15); margin-top: 12px; font-size: 13px; line-height: 1.4;">
              ⚠️ <strong>Remarque :</strong> Les aides de l'État et primes CEE sont réservées aux logements de plus de 2 ans d'ancienneté.
            </div>
          </div>
        </fieldset>

        <!-- STEP 2: Le Projet -->
        <fieldset class="sim-step" data-step="2" style="border: none; padding: 0; margin: 0; display: none;">
          <legend style="font-family: Anton, sans-serif; font-size: 24px; color: var(--nr-teal); margin-bottom: 24px; text-transform: uppercase;">2. Votre projet</legend>
          <p style="color: var(--nr-stone-500); margin-bottom: 20px; font-size: 14px;">Sélectionnez l'équipement que vous souhaitez installer ou remplacer.</p>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
              <input type="radio" name="equipment" value="pac_air_eau" checked style="display:none;">
              <span style="font-size: 28px; margin-bottom: 6px;">🌡️</span>
              Pompe à chaleur air/eau
            </label>
            <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
              <input type="radio" name="equipment" value="pac_air_air" style="display:none;">
              <span style="font-size: 28px; margin-bottom: 6px;">❄️</span>
              PAC air/air (Climatisation)
            </label>
            <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
              <input type="radio" name="equipment" value="ballon_thermo" style="display:none;">
              <span style="font-size: 28px; margin-bottom: 6px;">💧</span>
              Ballon thermodynamique
            </label>
            <label class="choice-box" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; border: 2px solid var(--nr-stone-200); border-radius: 16px; cursor: pointer; text-align: center; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
              <input type="radio" name="equipment" value="chaudiere" style="display:none;">
              <span style="font-size: 28px; margin-bottom: 6px;">🔥</span>
              Chaudière à condensation
            </label>
          </div>
        </fieldset>

        <!-- STEP 3: Situation & Revenus -->
        <fieldset class="sim-step" data-step="3" style="border: none; padding: 0; margin: 0; display: none;">
          <legend style="font-family: Anton, sans-serif; font-size: 24px; color: var(--nr-teal); margin-bottom: 24px; text-transform: uppercase;">3. Foyer & Revenus</legend>
          
          <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 700; color: var(--nr-teal); margin-bottom: 8px;">Nombre d'occupants dans le ménage</label>
            <select id="sim-occupants" name="occupants" style="width: 100%; padding: 12px; border: 1.5px solid var(--nr-stone-200); border-radius: 8px; font: inherit; background: #fff; color: var(--nr-teal);">
              <option value="1">1 personne</option>
              <option value="2" selected>2 personnes</option>
              <option value="3">3 personnes</option>
              <option value="4">4 personnes</option>
              <option value="5">5 personnes et plus</option>
            </select>
          </div>

          <div style="margin-bottom: 24px;">
            <label style="display: block; font-weight: 700; color: var(--nr-teal); margin-bottom: 8px;">Région du logement</label>
            <div style="display: flex; gap: 16px;">
              <label class="choice-box flex-row" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 14px; border: 2px solid var(--nr-stone-200); border-radius: 12px; cursor: pointer; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
                <input type="radio" name="region" value="hors_idf" checked style="display:none;">
                Autre région (Bourgogne...)
              </label>
              <label class="choice-box flex-row" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 14px; border: 2px solid var(--nr-stone-200); border-radius: 12px; cursor: pointer; font-weight: 700; color: var(--nr-teal); transition: 0.2s;">
                <input type="radio" name="region" value="idf" style="display:none;">
                Île-de-France
              </label>
            </div>
          </div>

          <div style="margin-bottom: 12px;">
            <label style="display: block; font-weight: 700; color: var(--nr-teal); margin-bottom: 8px;">Revenus annuels du foyer</label>
            <p style="color: var(--nr-stone-500); font-size: 13px; margin: -4px 0 12px;">Choisissez le barème correspondant à votre revenu fiscal de référence.</p>
            
            <div style="display: flex; flex-direction: column; gap: 10px;" id="income-pills-container">
              <!-- Bleu -->
              <label class="profile-pill-choice active" data-profile="bleu" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border: 2px solid #0056b3; border-radius: 12px; cursor: pointer; transition: 0.2s; background: rgba(0,86,179,0.04);">
                <input type="radio" name="income_profile" value="bleu" checked style="display:none;">
                <span style="display: flex; align-items: center; gap: 10px;">
                  <span style="width: 16px; height: 16px; border-radius: 50%; background: #0056b3; display: inline-block;"></span>
                  <strong style="color: var(--nr-teal);">Profil Bleu</strong>
                  <span style="font-size: 13px; color: var(--nr-stone-500);">(Très modestes)</span>
                </span>
                <span id="range-bleu" style="font-size: 14px; font-weight: 700; color: var(--nr-teal);">Moins de 23 734 €</span>
              </label>
              <!-- Jaune -->
              <label class="profile-pill-choice" data-profile="jaune" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border: 2px solid var(--nr-stone-200); border-radius: 12px; cursor: pointer; transition: 0.2s;">
                <input type="radio" name="income_profile" value="jaune" style="display:none;">
                <span style="display: flex; align-items: center; gap: 10px;">
                  <span style="width: 16px; height: 16px; border-radius: 50%; background: #ff922b; display: inline-block;"></span>
                  <strong style="color: var(--nr-teal);">Profil Jaune</strong>
                  <span style="font-size: 13px; color: var(--nr-stone-500);">(Modestes)</span>
                </span>
                <span id="range-jaune" style="font-size: 14px; font-weight: 700; color: var(--nr-teal);">Entre 23 734 € et 30 225 €</span>
              </label>
              <!-- Violet -->
              <label class="profile-pill-choice" data-profile="violet" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border: 2px solid var(--nr-stone-200); border-radius: 12px; cursor: pointer; transition: 0.2s;">
                <input type="radio" name="income_profile" value="violet" style="display:none;">
                <span style="display: flex; align-items: center; gap: 10px;">
                  <span style="width: 16px; height: 16px; border-radius: 50%; background: #be4bdb; display: inline-block;"></span>
                  <strong style="color: var(--nr-teal);">Profil Violet</strong>
                  <span style="font-size: 13px; color: var(--nr-stone-500);">(Intermédiaires)</span>
                </span>
                <span id="range-violet" style="font-size: 14px; font-weight: 700; color: var(--nr-teal);">Entre 30 225 € et 45 496 €</span>
              </label>
              <!-- Rose -->
              <label class="profile-pill-choice" data-profile="rose" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border: 2px solid var(--nr-stone-200); border-radius: 12px; cursor: pointer; transition: 0.2s;">
                <input type="radio" name="income_profile" value="rose" style="display:none;">
                <span style="display: flex; align-items: center; gap: 10px;">
                  <span style="width: 16px; height: 16px; border-radius: 50%; background: #f06595; display: inline-block;"></span>
                  <strong style="color: var(--nr-teal);">Profil Rose</strong>
                  <span style="font-size: 13px; color: var(--nr-stone-500);">(Supérieurs)</span>
                </span>
                <span id="range-rose" style="font-size: 14px; font-weight: 700; color: var(--nr-teal);">Plus de 45 496 €</span>
              </label>
            </div>
          </div>
        </fieldset>

        <!-- STEP 4: Résultats & Contact -->
        <fieldset class="sim-step" data-step="4" style="border: none; padding: 0; margin: 0; display: none;">
          <legend style="font-family: Anton, sans-serif; font-size: 24px; color: var(--nr-teal); margin-bottom: 12px; text-transform: uppercase;">4. Votre estimation d'aides</legend>
          
          <div style="background: #ebfbee; border: 1.5px solid rgba(44,138,62,0.2); padding: 24px; border-radius: 16px; text-align: center; margin-bottom: 24px;">
            <span style="font-size: 13px; color: #2c8a3e; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 6px;">Total des aides estimées</span>
            <strong id="final-sim-amount" style="font-size: 46px; font-family: Anton, sans-serif; color: #2c8a3e; display: block; line-height: 1.1;">9 000 €</strong>
            
            <div style="display: flex; justify-content: center; gap: 24px; margin-top: 18px; border-top: 1px dashed rgba(44,138,62,0.15); padding-top: 14px; font-size: 13px; color: var(--nr-teal);">
              <div>MaPrimeRénov' : <strong id="final-sim-mpr" style="color: #2c8a3e;">5 000 €</strong></div>
              <div style="width: 1px; background: rgba(44,138,62,0.2);"></div>
              <div>Prime CEE : <strong id="final-sim-cee" style="color: #2c8a3e;">4 000 €</strong></div>
            </div>
          </div>

          <!-- Note sur estimation uniquement -->
          <div style="background: var(--nr-bone); padding: 14px 18px; border-radius: 10px; border: 1.5px solid var(--nr-stone-200); margin-bottom: 30px; font-size: 12px; line-height: 1.5; color: var(--nr-stone-500); text-align: justify;">
            ℹ️ <strong>Note importante :</strong> Ces montants sont des <strong>estimations indicatives uniquement</strong> basées sur les barèmes officiels 2026. L'obtention effective des subventions dépend de la validation de vos pièces justificatives par l'ANAH et les signataires CEE, ainsi que de l'éligibilité technique de votre logement et de l'artisan RGE réalisant la pose.
          </div>

          <h3 style="font-family: Anton, sans-serif; font-size: 20px; color: var(--nr-teal); margin-bottom: 18px; text-transform: uppercase;">Demander mon dossier d'aides gratuit</h3>
          <p style="color: var(--nr-stone-500); font-size: 14px; margin-bottom: 20px;">Nos conseillers certifiés prennent en charge l'intégralité des démarches administratives pour vous.</p>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <label style="display:block; font-size:13px; font-weight:700; color:var(--nr-teal);">Nom et prénom *<input name="name" required style="width:100%; padding:12px; margin-top:6px; border:1.5px solid var(--nr-stone-200); border-radius:6px; font:inherit; box-sizing:border-box;"></label>
            <label style="display:block; font-size:13px; font-weight:700; color:var(--nr-teal);">Téléphone *<input type="tel" name="phone" required style="width:100%; padding:12px; margin-top:6px; border:1.5px solid var(--nr-stone-200); border-radius:6px; font:inherit; box-sizing:border-box;"></label>
          </div>

          <div style="margin-bottom: 16px;">
            <label style="display:block; font-size:13px; font-weight:700; color:var(--nr-teal);">Adresse email *<input type="email" name="email" required style="width:100%; padding:12px; margin-top:6px; border:1.5px solid var(--nr-stone-200); border-radius:6px; font:inherit; box-sizing:border-box;"></label>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
            <label style="display:block; font-size:13px; font-weight:700; color:var(--nr-teal);">Code postal *<input name="postal_code" required maxlength="5" inputmode="numeric" style="width:100%; padding:12px; margin-top:6px; border:1.5px solid var(--nr-stone-200); border-radius:6px; font:inherit; box-sizing:border-box;"></label>
            <label style="display:block; font-size:13px; font-weight:700; color:var(--nr-teal);">Ville *<input name="city" required style="width:100%; padding:12px; margin-top:6px; border:1.5px solid var(--nr-stone-200); border-radius:6px; font:inherit; box-sizing:border-box;"></label>
          </div>

          <input type="hidden" name="company_service_id" id="sim-service-id" value="">
          <input type="hidden" name="time_slot" value="8h30-10h">
          <input type="hidden" name="message" id="sim-hidden-message" value="">

          <button type="submit" style="width: 100%; padding: 16px; background: var(--nr-teal); color: #fff; font-family: inherit; font-size: 15px; font-weight: 800; border-radius: 8px; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 0.05em; transition: background 0.2s;">
            Envoyer ma demande d'aides
          </button>
        </fieldset>

        <!-- Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; border-top: 1.5px solid var(--nr-stone-200); padding-top: 20px;">
          <button type="button" id="prev-btn" style="padding: 12px 24px; border: 1.5px solid var(--nr-stone-200); border-radius: 8px; background: #fff; color: var(--nr-teal); font-weight: 700; cursor: pointer; font-family: inherit; font-size: 14px; visibility: hidden;">
            ← Précédent
          </button>
          <button type="button" id="next-btn" style="padding: 12px 28px; border: none; border-radius: 8px; background: var(--nr-teal); color: #fff; font-weight: 700; cursor: pointer; font-family: inherit; font-size: 14px;">
            Continuer →
          </button>
        </div>
      </form>
      
      <!-- Success Box -->
      <div id="sim-success-box" style="display: none; text-align: center; padding: 40px 20px;">
        <span style="font-size: 64px; display: block; margin-bottom: 20px;">🎉</span>
        <h2 style="font-family: Anton, sans-serif; font-size: 28px; color: var(--nr-teal); text-transform: uppercase; margin-bottom: 12px;">Demande Envoyée !</h2>
        <p style="color: var(--nr-stone-500); font-size: 16px; line-height: 1.6; max-width: 500px; margin: 0 auto 30px;">
          Merci pour votre demande. Un conseiller spécialisé RGE va analyser votre dossier et vous recontacter d'ici 24h pour confirmer l'exactitude de vos subventions.
        </p>
        <a href="/" style="display: inline-block; padding: 14px 28px; background: var(--nr-teal); color: #fff; text-decoration: none; font-weight: 700; border-radius: 8px; text-transform: uppercase; font-size: 13px;">
          Retourner à l'accueil
        </a>
      </div>

    </div>
  </div>
</main>

<style>
.choice-box {
  background: #fff;
}
.choice-box:hover {
  border-color: var(--nr-teal) !important;
  background: rgba(15,76,92,0.01);
}
.choice-box input[type="radio"]:checked + span, 
.choice-box:has(input[type="radio"]:checked) {
  border-color: var(--nr-teal) !important;
  background: rgba(15,76,92,0.04) !important;
}
.profile-pill-choice:hover {
  border-color: var(--nr-teal) !important;
}
.profile-pill-choice:has(input[type="radio"]:checked) {
  background: rgba(15,76,92,0.02) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const steps = document.querySelectorAll('.sim-step');
  const bar = document.getElementById('step-progress-bar');
  const numSpan = document.getElementById('step-num');
  const prevBtn = document.getElementById('prev-btn');
  const nextBtn = document.getElementById('next-btn');
  const warningDiv = document.getElementById('age-warning');
  const occupantsSelect = document.getElementById('sim-occupants');
  const form = document.getElementById('sim-form');
  const successBox = document.getElementById('sim-success-box');
  
  let currentStep = 1;

  // MaPrimeRénov' Plafonds de revenus 2026 (Autres Régions / Hors IDF)
  const incomeThresholds = {
    hors_idf: {
      1: { bleu: 17290, jaune: 22182, violet: 30514 },
      2: { bleu: 25282, jaune: 32448, violet: 44634 },
      3: { bleu: 30427, jaune: 39052, violet: 53738 },
      4: { bleu: 35572, jaune: 45656, violet: 62842 },
      5: { bleu: 40728, jaune: 52264, violet: 71962 }
    },
    idf: {
      1: { bleu: 24707, jaune: 30048, violet: 40388 },
      2: { bleu: 36248, jaune: 44052, violet: 59220 },
      3: { bleu: 43572, jaune: 52932, violet: 71148 },
      4: { bleu: 50882, jaune: 61812, violet: 83076 },
      5: { bleu: 58212, jaune: 70716, violet: 95052 }
    }
  };

  // Subventions map data
  const subventionsData = {
    pac_air_eau: {
      bleu: { total: 9000, mpr: 5000, cee: 4000 },
      jaune: { total: 7000, mpr: 3000, cee: 4000 },
      violet: { total: 4500, mpr: 1500, cee: 3000 },
      rose: { total: 1000, mpr: 0, cee: 1000 }
    },
    pac_air_air: {
      bleu: { total: 900, mpr: 0, cee: 900 },
      jaune: { total: 900, mpr: 0, cee: 900 },
      violet: { total: 450, mpr: 0, cee: 450 },
      rose: { total: 450, mpr: 0, cee: 450 }
    },
    ballon_thermo: {
      bleu: { total: 1400, mpr: 1200, cee: 200 },
      jaune: { total: 1000, mpr: 800, cee: 200 },
      violet: { total: 600, mpr: 400, cee: 200 },
      rose: { total: 200, mpr: 0, cee: 200 }
    },
    chaudiere: {
      bleu: { total: 4800, mpr: 4000, cee: 800 },
      jaune: { total: 3800, mpr: 3000, cee: 800 },
      violet: { total: 2300, mpr: 1500, cee: 800 },
      rose: { total: 800, mpr: 0, cee: 800 }
    }
  };

  function getSelectedValue(name) {
    const el = form.querySelector(`input[name="${name}"]:checked`);
    return el ? el.value : '';
  }

  function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(val);
  }

  function updateIncomeRanges() {
    const region = getSelectedValue('region');
    const occupants = parseInt(occupantsSelect.value);
    
    const row = incomeThresholds[region][occupants] || incomeThresholds[region][5];
    const limits = {
      bleu: row.bleu,
      jaune: row.jaune,
      violet: row.violet
    };

    document.getElementById('range-bleu').textContent = 'Moins de ' + formatPrice(limits.bleu);
    document.getElementById('range-jaune').textContent = 'Entre ' + formatPrice(limits.bleu) + ' et ' + formatPrice(limits.jaune);
    document.getElementById('range-violet').textContent = 'Entre ' + formatPrice(limits.jaune) + ' et ' + formatPrice(limits.violet);
    document.getElementById('range-rose').textContent = 'Plus de ' + formatPrice(limits.violet);
  }

  form.querySelectorAll('input[name="property_age"]').forEach(radio => {
    radio.addEventListener('change', function () {
      warningDiv.style.display = this.value === 'moins_2' ? 'block' : 'none';
    });
  });

  occupantsSelect.addEventListener('change', updateIncomeRanges);
  form.querySelectorAll('input[name="region"]').forEach(radio => {
    radio.addEventListener('change', updateIncomeRanges);
  });

  const profilePills = document.querySelectorAll('.profile-pill-choice');
  profilePills.forEach(pill => {
    pill.addEventListener('click', function () {
      profilePills.forEach(p => {
        p.classList.remove('active');
        p.style.borderColor = 'var(--nr-stone-200)';
        p.style.background = '#fff';
      });
      this.classList.add('active');
      const profile = this.getAttribute('data-profile');
      const borderColors = {
        bleu: '#0056b3',
        jaune: '#ff922b',
        violet: '#be4bdb',
        rose: '#f06595'
      };
      const bgColors = {
        bleu: 'rgba(0,86,179,0.04)',
        jaune: 'rgba(255,146,43,0.04)',
        violet: 'rgba(190,75,219,0.04)',
        rose: 'rgba(240,101,149,0.04)'
      };
      this.style.borderColor = borderColors[profile];
      this.style.background = bgColors[profile];
      this.querySelector('input[type="radio"]').checked = true;
    });
  });

  function calculateResults() {
    const age = getSelectedValue('property_age');
    const eq = getSelectedValue('equipment');
    const profile = getSelectedValue('income_profile');

    let total = 0, mpr = 0, cee = 0;

    if (age === 'plus_2') {
      const data = subventionsData[eq][profile];
      total = data.total;
      mpr = data.mpr;
      cee = data.cee;
    }

    document.getElementById('final-sim-amount').textContent = formatPrice(total);
    document.getElementById('final-sim-mpr').textContent = formatPrice(mpr);
    document.getElementById('final-sim-cee').textContent = formatPrice(cee);

    const eqNames = {
      pac_air_eau: 'Pompe à chaleur air/eau',
      pac_air_air: 'Pompe à chaleur air/air (Climatisation réversible)',
      ballon_thermo: 'Ballon thermodynamique',
      chaudiere: 'Chaudière à condensation'
    };
    
    const profileNames = {
      bleu: 'Bleu (Revenus très modestes)',
      jaune: 'Jaune (Revenus modestes)',
      violet: 'Violet (Revenus intermédiaires)',
      rose: 'Rose (Revenus supérieurs)'
    };

    document.getElementById('sim-hidden-message').value = 
      'Simulation d\'aides Rénovation Énergétique :\n' +
      '- Type de logement : ' + getSelectedValue('property_type') + '\n' +
      '- Ancienneté : ' + (age === 'plus_2' ? 'Plus de 2 ans' : 'Moins de 2 ans') + '\n' +
      '- Équipement souhaité : ' + eqNames[eq] + '\n' +
      '- Occupants : ' + occupantsSelect.value + ' personne(s) (' + (getSelectedValue('region') === 'idf' ? 'Île-de-France' : 'Autre région') + ')\n' +
      '- Profil de revenus : ' + profileNames[profile] + '\n' +
      '- Aides estimées : ' + formatPrice(total) + ' (MaPrimeRénov\' : ' + formatPrice(mpr) + ' | CEE : ' + formatPrice(cee) + ')';
  }

  function showStep(s) {
    steps.forEach(step => {
      step.style.display = parseInt(step.getAttribute('data-step')) === s ? 'block' : 'none';
    });

    currentStep = s;
    numSpan.textContent = s;
    bar.style.width = (s / 4 * 100) + '%';
    
    prevBtn.style.visibility = s === 1 ? 'hidden' : 'visible';
    
    if (s === 4) {
      calculateResults();
      nextBtn.style.display = 'none';
    } else {
      nextBtn.style.display = 'block';
      nextBtn.textContent = 'Continuer →';
    }
  }

  nextBtn.addEventListener('click', function () {
    if (currentStep < 4) {
      showStep(currentStep + 1);
    }
  });

  prevBtn.addEventListener('click', function () {
    if (currentStep > 1) {
      showStep(currentStep - 1);
    }
  });

  updateIncomeRanges();
  showStep(1);

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    
    const eq = getSelectedValue('equipment');
    const eqIds = {
      pac_air_eau: 8,
      pac_air_air: 15,
      ballon_thermo: 23,
      chaudiere: 4
    };
    
    const fd = new FormData(this);
    fd.set('company_service_id', eqIds[eq] || '');

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Envoi en cours...';

    try {
      const response = await fetch(this.action, {
        method: 'POST',
        body: fd,
        headers: {
          'Accept': 'application/json'
        }
      });
      const json = await response.json();
      if (json.ok) {
        form.style.display = 'none';
        successBox.style.display = 'block';
        successBox.scrollIntoView({ behavior: 'smooth' });
      } else {
        alert(json.message || 'Une erreur est survenue lors de l\'envoi de la demande.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Envoyer ma demande d\'aides';
      }
    } catch (err) {
      alert('Erreur réseau. Veuillez réessayer.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Envoyer ma demande d\'aides';
    }
  });

});
</script>
