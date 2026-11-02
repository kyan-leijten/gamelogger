<?php
// WAT DOET DIT BESTAND?
// De gamespagina. Hier kun je games toevoegen, bekijken, de status wijzigen,
// op je verlanglijst zetten en verwijderen.

require 'includes/database.php';
require 'includes/functies.php';
session_start();

// Ben je niet ingelogd? Dan ga je meteen naar de inlogpagina.
controleerLogin();

$gebruikerId = $_SESSION['gebruiker_id'];
$foutmelding = '';

// Dit zijn de drie statussen die we toestaan. Alles anders weigeren we.
$toegestaneStatussen = ['wil_spelen', 'speel_ik', 'uitgespeeld'];

// Welke knop is er ingedrukt? Elk formulier stuurt een verborgen veld "actie" mee.
// "?? ''" betekent: is er niks verstuurd, gebruik dan een lege tekst.
$actie = $_POST['actie'] ?? '';

// ------------------------------------------------------------
// ACTIE 1: een nieuwe game toevoegen
// ------------------------------------------------------------
if ($actie === 'toevoegen') {
  $titel = trim($_POST['titel'] ?? '');
  $status = $_POST['status'] ?? 'wil_spelen';

  // Is het vinkje "op verlanglijst" aangezet? Zo ja: 1, anders 0.
  $opVerlanglijst = isset($_POST['op_verlanglijst']) ? 1 : 0;

  if ($titel === '') {
    $foutmelding = 'Vul een titel in voordat je een game toevoegt.';
  } elseif (!in_array($status, $toegestaneStatussen)) { // in_array: staat de status in onze lijst?
    $foutmelding = 'Ongeldige status gekozen.';
  } else {
    $uitkomst = 'fout'; // we gaan ervan uit dat het misgaat, tenzij het lukt

    // try/catch/finally: zie register.php voor uitleg. Prepared statement: ook.
    try {
      $stmt = $verbinding->prepare('INSERT INTO games (gebruiker_id, titel, status, op_verlanglijst) VALUES (?, ?, ?, ?)');
      $stmt->bind_param('issi', $gebruikerId, $titel, $status, $opVerlanglijst); // i = getal, s = tekst
      $stmt->execute();
      $uitkomst = 'toegevoegd';
    } catch (mysqli_sql_exception $fout) {
      $uitkomst = 'fout';
    } finally {
      // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
      if (isset($stmt)) {
        $stmt->close();
      }
    }

    // Terug naar deze pagina. Het woord achter ?melding= bepaalt welke melding je ziet.
    header('Location: games.php?melding=' . $uitkomst);
    exit;
  }
}

// ------------------------------------------------------------
// ACTIE 2: de status van een game wijzigen
// ------------------------------------------------------------
if ($actie === 'status_wijzigen') {
  // (int) maakt er een heel getal van, zodat er niks geks in kan zitten
  $gameId = (int) ($_POST['game_id'] ?? 0);
  $status = $_POST['status'] ?? '';
  $uitkomst = 'fout';

  if (in_array($status, $toegestaneStatussen)) {
    try {
      // "AND gebruiker_id = ?" zorgt dat je alleen je EIGEN games kunt aanpassen
      $stmt = $verbinding->prepare('UPDATE games SET status = ? WHERE id = ? AND gebruiker_id = ?');
      $stmt->bind_param('sii', $status, $gameId, $gebruikerId);
      $stmt->execute();
      $uitkomst = 'status_gewijzigd';
    } catch (mysqli_sql_exception $fout) {
      $uitkomst = 'fout';
    } finally {
      if (isset($stmt)) {
        $stmt->close();
      }
    }
  }

  header('Location: games.php?melding=' . $uitkomst);
  exit;
}

// ------------------------------------------------------------
// ACTIE 3: de game op de verlanglijst zetten of eraf halen
// ------------------------------------------------------------
if ($actie === 'verlanglijst_wijzigen') {
  $gameId = (int) ($_POST['game_id'] ?? 0);
  $uitkomst = 'fout';

  try {
    // NOT draait de waarde om: 0 wordt 1 en 1 wordt 0
    $stmt = $verbinding->prepare('UPDATE games SET op_verlanglijst = NOT op_verlanglijst WHERE id = ? AND gebruiker_id = ?');
    $stmt->bind_param('ii', $gameId, $gebruikerId);
    $stmt->execute();
    $uitkomst = 'verlanglijst_gewijzigd';
  } catch (mysqli_sql_exception $fout) {
    $uitkomst = 'fout';
  } finally {
    if (isset($stmt)) {
      $stmt->close();
    }
  }

  header('Location: games.php?melding=' . $uitkomst);
  exit;
}

// ------------------------------------------------------------
// ACTIE 4: een game verwijderen
// ------------------------------------------------------------
if ($actie === 'verwijderen') {
  $gameId = (int) ($_POST['game_id'] ?? 0);
  $uitkomst = 'fout';

  try {
    $stmt = $verbinding->prepare('DELETE FROM games WHERE id = ? AND gebruiker_id = ?');
    $stmt->bind_param('ii', $gameId, $gebruikerId);
    $stmt->execute();
    $uitkomst = 'verwijderd';
  } catch (mysqli_sql_exception $fout) {
    $uitkomst = 'fout';
  } finally {
    if (isset($stmt)) {
      $stmt->close();
    }
  }

  header('Location: games.php?melding=' . $uitkomst);
  exit;
}

// ------------------------------------------------------------
// DE MELDING NA EEN ACTIE (de animatie die inschuift en verdwijnt)
// ------------------------------------------------------------
// Links staat het woord uit de link (?melding=...), rechts de tekst en de kleur.
$meldingen = [
  'toegevoegd'             => ['Game toegevoegd.', 'succes'],
  'status_gewijzigd'       => ['Status aangepast.', 'succes'],
  'verlanglijst_gewijzigd' => ['Verlanglijst aangepast.', 'succes'],
  'verwijderd'             => ['Game verwijderd.', 'verwijderd'],
  'fout'                   => ['Er ging iets mis. Probeer het opnieuw.', 'fout'],
];

$melding = '';
$meldingSoort = '';

// Staat er ?melding=... in de link, en kennen we dat woord? Dan laten we de melding zien.
if (isset($_GET['melding']) && isset($meldingen[$_GET['melding']])) {
  $melding = $meldingen[$_GET['melding']][0];      // [0] = de tekst
  $meldingSoort = $meldingen[$_GET['melding']][1]; // [1] = de kleur
}

// ------------------------------------------------------------
// ALLE GAMES VAN DEZE GEBRUIKER OPHALEN
// ------------------------------------------------------------
// We stoppen ze in drie lijstjes, één per status, en ook in een lijstje voor de verlanglijst.
$statusLijsten = ['wil_spelen' => [], 'speel_ik' => [], 'uitgespeeld' => []];
$verlanglijst = [];

try {
  $stmt = $verbinding->prepare('SELECT id, titel, status, op_verlanglijst FROM games WHERE gebruiker_id = ? ORDER BY toegevoegd_op DESC');
  $stmt->bind_param('i', $gebruikerId);
  $stmt->execute();
  $resultaat = $stmt->get_result();

  // while: herhaal dit voor elke gevonden game ($rij is steeds één game)
  while ($rij = $resultaat->fetch_assoc()) {
    $statusLijsten[$rij['status']][] = $rij; // [] = zet deze game achteraan in het juiste lijstje

    if ($rij['op_verlanglijst']) {
      $verlanglijst[] = $rij;
    }
  }
} catch (mysqli_sql_exception $fout) {
  $foutmelding = 'Kon je games niet ophalen.';
} finally {
  if (isset($stmt)) {
    $stmt->close();
  }
}

// Voor de drie kolommen: de naam en de kleur van elke status
$kolommen = [
  'wil_spelen'  => ['label' => 'Want to Play', 'kleur' => 'paars'],
  'speel_ik'    => ['label' => 'Playing',      'kleur' => 'blauw'],
  'uitgespeeld' => ['label' => 'Completed',    'kleur' => 'groen'],
];

$paginaTitel = 'Mijn games — Game Log';
include 'includes/header.php';
?>

<main class="games-pagina">
  <section class="games-intro">
    <h1>Mijn games</h1>
    <p>Voeg een nieuwe game toe en houd bij hoe ver je bent.</p>

    <!-- Knoppen om je games te downloaden -->
    <a href="export/backup.php" class="knop tweede klein">Backup downloaden</a>
    <a href="export/pdf.php" class="knop tweede klein">PDF downloaden</a>
    <a href="export/xls.php" class="knop tweede klein">XLS downloaden</a>
    <a href="export/xml.php" class="knop tweede klein">XML downloaden</a>
  </section>

  <!-- De melding na een actie. De kleur komt van de class (succes, verwijderd of fout). -->
  <?php if ($melding !== ''): ?>
    <p class="melding <?= $meldingSoort ?>"><?= htmlspecialchars($melding) ?></p>
  <?php endif; ?>

  <?php if ($foutmelding !== ''): ?>
    <p class="melding fout"><?= htmlspecialchars($foutmelding) ?></p>
  <?php endif; ?>

  <!-- Het formulier om een game toe te voegen -->
  <section class="game-toevoegen">
    <form method="post" class="game-formulier">
      <!-- Verborgen veld: zo weet PHP welke actie dit is -->
      <input type="hidden" name="actie" value="toevoegen" />

      <div>
        <label for="titel">Titel van de game</label>
        <input type="text" id="titel" name="titel" placeholder="Bijv. Zelda: Breath of the Wild" required maxlength="100" />
      </div>

      <div>
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="wil_spelen">Want to Play</option>
          <option value="speel_ik">Playing</option>
          <option value="uitgespeeld">Completed</option>
        </select>
      </div>

      <label class="verlanglijst-vinkje">
        <input type="checkbox" name="op_verlanglijst" value="1" />
        Op verlanglijst
      </label>

      <button type="submit" class="knop">Toevoegen</button>
    </form>
  </section>

  <!-- De verlanglijst: alleen zichtbaar als er games op staan -->
  <?php if (!empty($verlanglijst)): ?>
    <section class="verlanglijst-sectie">
      <h2>Verlanglijst</h2>

      <div class="verlanglijst-lijst">
        <!-- foreach: herhaal dit blokje voor elke game op de verlanglijst -->
        <?php foreach ($verlanglijst as $game): ?>
          <div class="game-kaart">
            <p class="game-titel"><?= htmlspecialchars($game['titel']) ?></p>

            <form method="post" class="verlanglijst-formulier">
              <input type="hidden" name="actie" value="verlanglijst_wijzigen" />
              <input type="hidden" name="game_id" value="<?= (int) $game['id'] ?>" />
              <button type="submit" class="ster-knop actief" aria-label="Van verlanglijst halen">★</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- De drie kolommen: Want to Play, Playing en Completed -->
  <section class="games-overzicht">
    <?php foreach ($kolommen as $sleutel => $info): ?>
      <div class="game-kolom">
        <h2 class="<?= $info['kleur'] ?>"><?= $info['label'] ?></h2>

        <?php if (empty($statusLijsten[$sleutel])): ?>
          <p class="leeg-bericht">geen games gekozen</p>
        <?php endif; ?>

        <?php foreach ($statusLijsten[$sleutel] as $game): ?>
          <div class="game-kaart">
            <p class="game-titel"><?= htmlspecialchars($game['titel']) ?></p>

            <!-- Het sterretje: op de verlanglijst of niet -->
            <form method="post" class="verlanglijst-formulier">
              <input type="hidden" name="actie" value="verlanglijst_wijzigen" />
              <input type="hidden" name="game_id" value="<?= (int) $game['id'] ?>" />
              <button type="submit" class="ster-knop <?= $game['op_verlanglijst'] ? 'actief' : '' ?>" aria-label="Verlanglijst aan/uit">
                <?= $game['op_verlanglijst'] ? '★' : '☆' ?>
              </button>
            </form>

            <!-- De keuzelijst om de status te wijzigen. onchange: verstuur meteen als je iets anders kiest. -->
            <form method="post" class="status-formulier">
              <input type="hidden" name="actie" value="status_wijzigen" />
              <input type="hidden" name="game_id" value="<?= (int) $game['id'] ?>" />
              <select name="status" onchange="this.form.submit()">
                <option value="wil_spelen" <?= $sleutel === 'wil_spelen' ? 'selected' : '' ?>>Want to Play</option>
                <option value="speel_ik" <?= $sleutel === 'speel_ik' ? 'selected' : '' ?>>Playing</option>
                <option value="uitgespeeld" <?= $sleutel === 'uitgespeeld' ? 'selected' : '' ?>>Completed</option>
              </select>
            </form>

            <!-- Het kruisje om te verwijderen. confirm: eerst vragen of je het zeker weet. -->
            <form method="post" class="verwijder-formulier" onsubmit="return confirm('Deze game verwijderen?')">
              <input type="hidden" name="actie" value="verwijderen" />
              <input type="hidden" name="game_id" value="<?= (int) $game['id'] ?>" />
              <button type="submit" class="verwijder-knop" aria-label="Verwijderen">✕</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
