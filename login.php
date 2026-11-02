<?php
// WAT DOET DIT BESTAND?
// Inloggen: het wachtwoord controleren en de bezoeker onthouden in de sessie.

require 'includes/database.php';
session_start();

$foutmelding = '';

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
  $wachtwoord = $_POST['wachtwoord'] ?? '';
  $gebruiker = null;

  // Zoek de gebruiker in de database (met een prepared statement, zie register.php)
  // het is stmtlogin zo heb ik het doosje genoemd omdat het de opdracht meegeeft alvast.
  try {
    $stmtLogin = $verbinding->prepare('SELECT id, wachtwoord, rol FROM gebruikers WHERE gebruikersnaam = ?');
    $stmtLogin->bind_param('s', $gebruikersnaam); // 's' = één stukje tekst
    $stmtLogin->execute();
    $gebruiker = $stmtLogin->get_result()->fetch_assoc(); // de gevonden rij, of niks
  } catch (mysqli_sql_exception $fout) {
    $foutmelding = 'Er ging iets mis bij het inloggen. Probeer het later opnieuw.';
  } finally {
    // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
    if (isset($stmtLogin)) {
      $stmtLogin->close();
    }
  }

  if ($foutmelding === '') {
    // password_verify maakt van het ingevulde wachtwoord opnieuw een hash
    // (met dezelfde salt) en kijkt of die gelijk is aan de hash in de database.
    if ($gebruiker && password_verify($wachtwoord, $gebruiker['wachtwoord'])) {
      // Goed! We onthouden in de sessie wie er is ingelogd
      $_SESSION['gebruiker_id'] = $gebruiker['id'];
      $_SESSION['gebruikersnaam'] = $gebruikersnaam;
      $_SESSION['rol'] = $gebruiker['rol'];

      header('Location: games.php');
      exit;
    }

    // Bewust één melding voor "verkeerd wachtwoord" én "onbekende naam",
    // zodat niemand kan uitproberen welke namen bestaan
    $foutmelding = 'Gebruikersnaam of wachtwoord is onjuist.';
  }
}

$paginaTitel = 'Inloggen — Game Log';
include 'includes/header.php';
?>

<main class="auth-pagina">
  <section class="auth-formulier-sectie">
    <h1>Inloggen</h1>
    <p>Log in om jouw games te bekijken.</p>

    <!-- Net gelukt met registreren? Dan staat ?geregistreerd=1 in de link -->
    <!-- staat er in de link een label dat je geregistreerd bent  -->
    <?php if (isset($_GET['geregistreerd'])): ?>
      <p class="melding succes">Je account is aangemaakt. Je kunt nu inloggen.</p>
    <?php endif; ?>

    <?php if ($foutmelding !== ''): ?>
      <p class="melding fout"><?= htmlspecialchars($foutmelding) ?></p>
    <?php endif; ?>

    <form method="post" class="auth-formulier">
      <label for="gebruikersnaam">Gebruikersnaam</label>
      <input type="text" id="gebruikersnaam" name="gebruikersnaam" required />

      <label for="wachtwoord">Wachtwoord</label>
      <input type="password" id="wachtwoord" name="wachtwoord" required />

      <button type="submit" class="knop">Inloggen</button>
    </form>

    <p class="inloggen-tekst">
      Nog geen account?
      <a href="register.php">Account maken</a>
    </p>
  </section>
</main>

<?php include 'includes/footer.php'; ?>