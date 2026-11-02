// WAT DOET DIT BESTAND?
// JavaScript draait in de browser van de bezoeker (PHP draait op de server).
// Hier staan twee kleine dingen: het menu op een telefoon en een snelle wachtwoordcontrole.

// ------------------------------------------------------------
// 1. Het menu op een telefoon openen en sluiten
// ------------------------------------------------------------
// document.getElementById zoekt een stukje van de pagina op aan de hand van zijn id
const menuKnop = document.getElementById('menuKnop');
const hoofdMenu = document.getElementById('hoofdMenu');

// Staan beide op de pagina? (op sommige pagina's niet)
if (menuKnop && hoofdMenu) {
  // addEventListener: "als er op de knop wordt geklikt, doe dan dit"
  menuKnop.addEventListener('click', function () {
    // toggle: staat de class "open" erop? Haal hem weg. Staat hij er niet op? Zet hem erop.
    // De CSS laat het menu zien of verbergen, afhankelijk van die class.
    hoofdMenu.classList.toggle('open');
  });
}

// ------------------------------------------------------------
// 2. Bij het registreren: kloppen de twee wachtwoorden?
// ------------------------------------------------------------
const registerFormulier = document.getElementById('registerFormulier');

if (registerFormulier) {
  const wachtwoord = document.getElementById('wachtwoord');
  const wachtwoordHerhaal = document.getElementById('wachtwoord_herhaal');
  const melding = document.getElementById('wachtwoordMelding');

  // "submit" gebeurt vlak voordat het formulier wordt verstuurd
  registerFormulier.addEventListener('submit', function (event) {
    if (wachtwoord.value !== wachtwoordHerhaal.value) {
      event.preventDefault(); // stop het versturen
      melding.textContent = 'De wachtwoorden komen niet overeen.';
    } else {
      melding.textContent = '';
    }
  });
  // PHP controleert dit daarna nóg een keer, want JavaScript kan iemand omzeilen.
}
