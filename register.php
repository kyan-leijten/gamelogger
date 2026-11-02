<?php
// WAT DOET DIT BESTAND?
// Een account maken: het formulier laten zien, controleren en opslaan in de database.

// require = laad dit bestand. Is het er niet, dan stopt de pagina.
require 'includes/database.php';
session_start();

$foutmelding = '';
$gebruikersnaam = '';
$email = '';

// Is het formulier verstuurd? POST betekent: de gegevens uit het formulier zijn opgestuurd.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // $_POST bevat wat de bezoeker heeft ingevuld.
  // "?? ''" betekent: staat het veld er niet, gebruik dan een lege tekst.
  // trim() haalt spaties voor en achter de tekst weg.
  $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $wachtwoord = $_POST['wachtwoord'] ?? '';
  $wachtwoordHerhaal = $_POST['wachtwoord_herhaal'] ?? '';

  // STAP 1: controleren of alles goed is ingevuld
  if ($gebruikersnaam === '' || $email === '' || $wachtwoord === '') {
    $foutmelding = 'Vul alle velden in.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // filter_var checkt of het op een e-mailadres lijkt
    $foutmelding = 'Vul een geldig e-mailadres in.';
  } elseif (strlen($wachtwoord) < 8) {                    // strlen telt het aantal tekens
    $foutmelding = 'Je wachtwoord moet minimaal 8 tekens lang zijn.';
  } elseif ($wachtwoord !== $wachtwoordHerhaal) {
    $foutmelding = 'De wachtwoorden komen niet overeen.';
  } else {
    // STAP 2: kijken of de gebruikersnaam of het e-mailadres al bestaat
    $bestaatAl = false;

    // try: probeer dit. Gaat het mis, dan springt PHP naar catch.
    // finally: dit gebeurt altijd, ook na een fout.
    try {
      // PREPARED STATEMENT: we zetten de vraag eerst klaar met vraagtekens (?)
      // en vullen de waarden daarna in. Zo kan niemand met een gemeen invulveld
      // de database kapotmaken (dat heet SQL-injectie).
      $stmtControle = $verbinding->prepare('SELECT id FROM gebruikers WHERE gebruikersnaam = ? OR email = ?');
      $stmtControle->bind_param('ss', $gebruikersnaam, $email); // 'ss' = twee stukjes tekst
      $stmtControle->execute();                                  // voer de vraag uit
      // fetch_assoc geeft de eerste gevonden rij terug, of niks als er niemand is gevonden
      $bestaatAl = $stmtControle->get_result()->fetch_assoc();
    } catch (mysqli_sql_exception $fout) {
      $foutmelding = 'Er ging iets mis. Probeer het later opnieuw.';
    } finally {
      // Altijd opruimen: sluit de vraag af
      // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
      if (isset($stmtControle)) {
        $stmtControle->close();
      }
    }

    if ($foutmelding === '' && $bestaatAl) {
      $foutmelding = 'Deze gebruikersnaam of dit e-mailadres is al in gebruik.';
    } elseif ($foutmelding === '') {
      // STAP 3: het account opslaan
      // We slaan NIET het wachtwoord zelf op, maar een HASH: een onleesbare reeks tekens
      // waar je het wachtwoord niet meer uit kunt terughalen.
      // PHP voegt er ook automatisch een SALT aan toe (willekeurige tekens).
      // Daardoor krijgen twee mensen met hetzelfde wachtwoord toch een andere hash.
      // PASSWORD_DEFAULT betekent: laat PHP zelf het beste hash-systeem kiezen.
      $wachtwoordHash = password_hash($wachtwoord, PASSWORD_DEFAULT);
      $aangemaakt = false;

      try {
        $stmtOpslaan = $verbinding->prepare('INSERT INTO gebruikers (gebruikersnaam, email, wachtwoord) VALUES (?, ?, ?)');
        $stmtOpslaan->bind_param('sss', $gebruikersnaam, $email, $wachtwoordHash);
        $stmtOpslaan->execute();
        $aangemaakt = true;
      } catch (mysqli_sql_exception $fout) {
        $foutmelding = 'Registreren is niet gelukt. Probeer het opnieuw.';
      } finally {
        if (isset($stmtOpslaan)) {
          $stmtOpslaan->close();
        }
      }

      // Gelukt? Dan naar de inlogpagina. exit stopt de rest van deze pagina.
      if ($aangemaakt) {
        header('Location: login.php?geregistreerd=1');
        exit;
      }
    }
  }
}

$paginaTitel = 'Account maken — Game Log';
include 'includes/header.php';
?>

<main class="auth-pagina">
  <section class="auth-formulier-sectie">
    <h1>Account maken</h1>
    <p>Maak een account aan om je games bij te houden.</p>

    <!-- Is er een foutmelding? Laat die dan zien. -->
    <?php if ($foutmelding !== ''): ?>
      <p class="melding fout"><?= htmlspecialchars($foutmelding) ?></p>
    <?php endif; ?>

    <!-- required = de browser laat je niet verder als dit veld leeg is -->
    <form method="post" class="auth-formulier" id="registerFormulier">
      <label for="gebruikersnaam">Gebruikersnaam</label>
      <input type="text" id="gebruikersnaam" name="gebruikersnaam" required maxlength="50" value="<?= htmlspecialchars($gebruikersnaam) ?>" />

      <label for="email">E-mailadres</label>
      <input type="email" id="email" name="email" required maxlength="255" value="<?= htmlspecialchars($email) ?>" />

      <label for="wachtwoord">Wachtwoord (minimaal 8 tekens)</label>
      <input type="password" id="wachtwoord" name="wachtwoord" required />

      <label for="wachtwoord_herhaal">Wachtwoord herhalen</label>
      <input type="password" id="wachtwoord_herhaal" name="wachtwoord_herhaal" required />

      <!-- Hier zet JavaScript een melding als de twee wachtwoorden niet gelijk zijn -->
      <p class="js-melding" id="wachtwoordMelding"></p>

      <button type="submit" class="knop">Account maken</button>
    </form>

    <p class="inloggen-tekst">
      Heb je al een account?
      <a href="login.php">Inloggen</a>
    </p>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
