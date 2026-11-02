<?php
// WAT DOET DIT BESTAND?
// De accountpagina: je gegevens, een profielfoto uploaden en cijfers over je games.

require 'includes/database.php';
require 'includes/functies.php';
session_start();

controleerLogin();

$gebruikerId = $_SESSION['gebruiker_id'];
$foutmelding = '';
$succesmelding = '';

// ------------------------------------------------------------
// STAP 1: de gegevens van deze gebruiker ophalen
// ------------------------------------------------------------
$gebruiker = null;

try {
  $stmt = $verbinding->prepare('SELECT gebruikersnaam, email, rol, profielfoto, aangemaakt_op FROM gebruikers WHERE id = ?');
  $stmt->bind_param('i', $gebruikerId);
  $stmt->execute();
  $gebruiker = $stmt->get_result()->fetch_assoc();
} catch (mysqli_sql_exception $fout) {
  $foutmelding = 'Kon je gegevens niet ophalen.';
} finally {
  if (isset($stmt)) {
    $stmt->close();
  }
}

// ------------------------------------------------------------
// STAP 2: een profielfoto uploaden (alleen als het formulier is verstuurd)
// ------------------------------------------------------------
// $_FILES bevat het bestand dat de bezoeker heeft gekozen
if (isset($_FILES['profielfoto'])) {
  $bestand = $_FILES['profielfoto'];

  // pathinfo haalt de extensie uit de bestandsnaam (bij "foto.PNG" wordt dat "PNG"),
  // strtolower maakt er kleine letters van ("png")
  $extensie = strtolower(pathinfo($bestand['name'], PATHINFO_EXTENSION));
  $toegestaneExtensies = ['jpg', 'jpeg', 'png', 'webp'];

  if ($bestand['error'] !== 0) {                          // 0 betekent: het uploaden ging goed
    $foutmelding = 'Er ging iets mis bij het uploaden.';
  } elseif (!in_array($extensie, $toegestaneExtensies)) {
    $foutmelding = 'Alleen jpg, png of webp is toegestaan.';
  } elseif ($bestand['size'] > 2000000) {                 // de grootte staat in bytes: 2000000 = ongeveer 2 MB
    $foutmelding = 'De foto mag maximaal 2 MB zijn.';
  } else {
    // We verzinnen zelf een nieuwe bestandsnaam (nooit de naam van de bezoeker gebruiken).
    // time() is het aantal seconden sinds 1970, zodat elke naam anders is.
    $nieuweNaam = 'gebruiker_' . $gebruikerId . '_' . time() . '.' . $extensie;

    // __DIR__ is de map waarin dit bestand staat
    $map = __DIR__ . '/uploads/profielfotos/';

    // Bestaat de map nog niet? Maak hem dan aan.
    if (!is_dir($map)) {
      mkdir($map, 0755, true);
    }

    // PHP zet een geüpload bestand eerst tijdelijk neer.
    // move_uploaded_file verplaatst het naar onze eigen map.
    if (move_uploaded_file($bestand['tmp_name'], $map . $nieuweNaam)) {
      // Het pad dat we in de database bewaren
      $pad = 'uploads/profielfotos/' . $nieuweNaam;

      try {
        $stmt = $verbinding->prepare('UPDATE gebruikers SET profielfoto = ? WHERE id = ?');
        $stmt->bind_param('si', $pad, $gebruikerId);
        $stmt->execute();

        $gebruiker['profielfoto'] = $pad; // zodat je de nieuwe foto meteen ziet
        $succesmelding = 'Profielfoto bijgewerkt.';
      } catch (mysqli_sql_exception $fout) {
        $foutmelding = 'Kon de foto niet opslaan in de database.';
      } finally {
        // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
      if (isset($stmt)) {
          $stmt->close();
        }
      }
    } else {
      $foutmelding = 'Kon het bestand niet opslaan op de server.';
    }
  }
}

// ------------------------------------------------------------
// STAP 3: tellen hoeveel games je hebt per status
// ------------------------------------------------------------
$statistieken = ['totaal' => 0, 'wil_spelen' => 0, 'speel_ik' => 0, 'uitgespeeld' => 0, 'verlanglijst' => 0];

try {
  $stmt = $verbinding->prepare('SELECT status, op_verlanglijst FROM games WHERE gebruiker_id = ?');
  $stmt->bind_param('i', $gebruikerId);
  $stmt->execute();
  $resultaat = $stmt->get_result();

  // Voor elke game: tel er 1 bij. ++ betekent "tel er 1 bij op".
  while ($rij = $resultaat->fetch_assoc()) {
    $statistieken['totaal']++;
    $statistieken[$rij['status']]++;

    if ($rij['op_verlanglijst']) {
      $statistieken['verlanglijst']++;
    }
  }
} catch (mysqli_sql_exception $fout) {
  $foutmelding = 'Kon je cijfers niet ophalen.';
} finally {
  if (isset($stmt)) {
    $stmt->close();
  }
}

$paginaTitel = 'Mijn account — Game Log';
include 'includes/header.php';
?>

<main class="account-pagina">
  <section class="account-intro">
    <h1>Mijn account</h1>
  </section>

  <?php if ($foutmelding !== ''): ?>
    <p class="melding fout"><?= htmlspecialchars($foutmelding) ?></p>
  <?php endif; ?>

  <?php if ($succesmelding !== ''): ?>
    <p class="melding succes"><?= htmlspecialchars($succesmelding) ?></p>
  <?php endif; ?>

  <!-- Je gegevens en je profielfoto -->
  <section class="account-kaart">
    <div class="profielfoto-blok">
      <?php if ($gebruiker && $gebruiker['profielfoto']): ?>
        <img src="<?= htmlspecialchars($gebruiker['profielfoto']) ?>" alt="Profielfoto" class="profielfoto" />
      <?php else: ?>
        <!-- Nog geen foto? Dan tonen we de eerste letter van je naam.
             substr pakt de eerste letter, strtoupper maakt er een hoofdletter van.
             "?? '?'" betekent: bestaat de naam niet, gebruik dan een vraagteken. -->
        <div class="profielfoto profielfoto-leeg">
          <?= htmlspecialchars(strtoupper(substr($gebruiker['gebruikersnaam'] ?? '?', 0, 1))) ?>
        </div>
      <?php endif; ?>

      <!-- enctype="multipart/form-data" is nodig om een bestand mee te kunnen sturen -->
      <form method="post" enctype="multipart/form-data" class="foto-formulier">
        <label for="profielfoto">Nieuwe profielfoto</label>
        <input type="file" id="profielfoto" name="profielfoto" accept="image/png, image/jpeg, image/webp" required />
        <button type="submit" class="knop klein">Uploaden</button>
      </form>
    </div>

    <div class="account-info">
      <h2>
        <?= htmlspecialchars($gebruiker['gebruikersnaam'] ?? '') ?>
        <?php if (($gebruiker['rol'] ?? '') === 'admin'): ?>
          <span class="rol-label admin">Admin</span>
        <?php endif; ?>
      </h2>
      <p><?= !empty($gebruiker['email']) ? htmlspecialchars($gebruiker['email']) : 'Geen e-mailadres bekend' ?></p>
      <!-- strtotime maakt van de datum uit de database een getal, date maakt er een mooie datum van -->
      <p>Lid sinds <?= $gebruiker ? date('d-m-Y', strtotime($gebruiker['aangemaakt_op'])) : '' ?></p>
    </div>
  </section>

  <!-- De cijfers -->
  <section class="statistieken">
    <div class="statistiek-kaart">
      <span class="statistiek-getal"><?= $statistieken['totaal'] ?></span>
      <span>Games totaal</span>
    </div>
    <div class="statistiek-kaart">
      <span class="statistiek-getal paars"><?= $statistieken['wil_spelen'] ?></span>
      <span>Want to Play</span>
    </div>
    <div class="statistiek-kaart">
      <span class="statistiek-getal blauw"><?= $statistieken['speel_ik'] ?></span>
      <span>Playing</span>
    </div>
    <div class="statistiek-kaart">
      <span class="statistiek-getal groen"><?= $statistieken['uitgespeeld'] ?></span>
      <span>Completed</span>
    </div>
    <div class="statistiek-kaart">
      <span class="statistiek-getal accent"><?= $statistieken['verlanglijst'] ?></span>
      <span>Verlanglijst</span>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
