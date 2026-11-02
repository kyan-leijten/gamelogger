<?php
// WAT DOET DIT BESTAND?
// De adminpagina. Alleen een admin mag hier komen.
// Je ziet alle gebruikers, kunt iemand admin maken of weer gewone gebruiker, en iemand verwijderen.

require 'includes/database.php';
require 'includes/functies.php';
session_start();

// Geen admin? Dan ga je terug naar de gamespagina.
controleerAdmin();

// (int) maakt er een heel getal van, zodat er niks geks in kan zitten
$mijnId = (int) $_SESSION['gebruiker_id'];

// Welke knop is er ingedrukt, en bij welke gebruiker?
// "?? ''" betekent: is er niks verstuurd, gebruik dan een lege tekst.
$actie = $_POST['actie'] ?? '';
$doelId = (int) ($_POST['gebruiker_id'] ?? 0);

// ------------------------------------------------------------
// ACTIE 1: iemand admin maken, of weer gewone gebruiker
// ------------------------------------------------------------
if ($actie === 'rol_wijzigen') {
  $uitkomst = 'fout';

  // Je kunt je eigen rol niet wijzigen (anders ben je jezelf per ongeluk de admin kwijt)
  if ($doelId !== $mijnId) {
    try {
      // IF(a, b, c): is de rol "admin"? Dan wordt het "gebruiker". Anders wordt het "admin".
      $stmt = $verbinding->prepare("UPDATE gebruikers SET rol = IF(rol = 'admin', 'gebruiker', 'admin') WHERE id = ?");
      $stmt->bind_param('i', $doelId);
      $stmt->execute();
      $uitkomst = 'rol_gewijzigd';
    } catch (mysqli_sql_exception $fout) {
      $uitkomst = 'fout';
    } finally {
      // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
      if (isset($stmt)) {
        $stmt->close();
      }
    }
  }

  header('Location: admin.php?melding=' . $uitkomst);
  exit;
}

// ------------------------------------------------------------
// ACTIE 2: een gebruiker verwijderen (zijn games verdwijnen automatisch mee)
// ------------------------------------------------------------
if ($actie === 'verwijder_gebruiker') {
  $uitkomst = 'fout';

  // Jezelf verwijderen mag niet
  if ($doelId !== $mijnId) {
    try {
      $stmt = $verbinding->prepare('DELETE FROM gebruikers WHERE id = ?');
      $stmt->bind_param('i', $doelId);
      $stmt->execute();
      $uitkomst = 'verwijderd';
    } catch (mysqli_sql_exception $fout) {
      $uitkomst = 'fout';
    } finally {
      if (isset($stmt)) {
        $stmt->close();
      }
    }
  }

  header('Location: admin.php?melding=' . $uitkomst);
  exit;
}

// ------------------------------------------------------------
// DE MELDING NA EEN ACTIE (zelfde systeem als op de gamespagina)
// ------------------------------------------------------------
$meldingen = [
  'rol_gewijzigd' => ['Rol aangepast.', 'succes'],
  'verwijderd'    => ['Gebruiker verwijderd.', 'verwijderd'],
  'fout'          => ['Dat kan niet. Probeer het opnieuw.', 'fout'],
];

$melding = '';
$meldingSoort = '';

if (isset($_GET['melding']) && isset($meldingen[$_GET['melding']])) {
  $melding = $meldingen[$_GET['melding']][0];
  $meldingSoort = $meldingen[$_GET['melding']][1];
}

// ------------------------------------------------------------
// ALLE GEBRUIKERS OPHALEN, met erbij hoeveel games ze hebben
// ------------------------------------------------------------
$gebruikers = [];
$foutmelding = '';

try {
  // (SELECT COUNT(*) ...) telt per gebruiker het aantal games
  $stmt = $verbinding->prepare(
    'SELECT id, gebruikersnaam, email, rol, profielfoto, aangemaakt_op,
            (SELECT COUNT(*) FROM games WHERE games.gebruiker_id = gebruikers.id) AS aantal_games
     FROM gebruikers
     ORDER BY id DESC'
  );
  $stmt->execute();
  $resultaat = $stmt->get_result();

  while ($rij = $resultaat->fetch_assoc()) {
    $gebruikers[] = $rij;
  }
} catch (mysqli_sql_exception $fout) {
  $foutmelding = 'Kon de gebruikers niet ophalen.';
} finally {
  if (isset($stmt)) {
    $stmt->close();
  }
}

// array_column pakt van elke gebruiker alleen het aantal games, array_sum telt ze op
$totaalGames = array_sum(array_column($gebruikers, 'aantal_games'));

$paginaTitel = 'Admin — Game Log';
include 'includes/header.php';
?>

<main class="admin-pagina">
  <section class="account-intro">
    <h1>Admin</h1>
    <p>Beheer hier de gebruikers van Game Log.</p>
  </section>

  <?php if ($melding !== ''): ?>
    <p class="melding <?= $meldingSoort ?>"><?= htmlspecialchars($melding) ?></p>
  <?php endif; ?>

  <?php if ($foutmelding !== ''): ?>
    <p class="melding fout"><?= htmlspecialchars($foutmelding) ?></p>
  <?php endif; ?>

  <!-- Twee cijfers bovenaan -->
  <section class="admin-cijfers">
    <div class="statistiek-kaart">
      <span class="statistiek-getal"><?= count($gebruikers) ?></span>
      <span>Gebruikers</span>
    </div>
    <div class="statistiek-kaart">
      <span class="statistiek-getal accent"><?= $totaalGames ?></span>
      <span>Games in totaal</span>
    </div>
  </section>

  <!-- De tabel met alle gebruikers -->
  <section class="tabel-wrapper">
    <table class="admin-tabel">
      <thead>
        <tr>
          <th>Gebruiker</th>
          <th>E-mail</th>
          <th>Rol</th>
          <th>Games</th>
          <th>Lid sinds</th>
          <th>Acties</th>
        </tr>
      </thead>
      <tbody>
        <!-- Eén rij per gebruiker -->
        <?php foreach ($gebruikers as $gebruiker): ?>
          <tr>
            <td>
              <div class="tabel-gebruiker">
                <!-- Heeft de gebruiker een profielfoto? Laat die zien. Zo niet: de eerste letter van de naam. -->
                <?php if ($gebruiker['profielfoto']): ?>
                  <img src="<?= htmlspecialchars($gebruiker['profielfoto']) ?>" alt="" class="mini-avatar" />
                <?php else: ?>
                  <span class="mini-avatar leeg"><?= htmlspecialchars(strtoupper(substr($gebruiker['gebruikersnaam'], 0, 1))) ?></span>
                <?php endif; ?>

                <span>
                  <?= htmlspecialchars($gebruiker['gebruikersnaam']) ?>
                  <?php if ((int) $gebruiker['id'] === $mijnId): ?>
                    <small>(jij)</small>
                  <?php endif; ?>
                </span>
              </div>
            </td>
            <td><?= $gebruiker['email'] ? htmlspecialchars($gebruiker['email']) : '—' ?></td>
            <td><span class="rol-label <?= $gebruiker['rol'] ?>"><?= $gebruiker['rol'] === 'admin' ? 'Admin' : 'Gebruiker' ?></span></td>
            <td><?= (int) $gebruiker['aantal_games'] ?></td>
            <!-- strtotime + date maken van de datum uit de database een nette datum (zoals 26-10-2026) -->
            <td><?= date('d-m-Y', strtotime($gebruiker['aangemaakt_op'])) ?></td>
            <td>
              <!-- Bij jezelf laten we geen knoppen zien -->
              <?php if ((int) $gebruiker['id'] !== $mijnId): ?>
                <div class="tabel-acties">
                  <form method="post">
                    <input type="hidden" name="actie" value="rol_wijzigen" />
                    <input type="hidden" name="gebruiker_id" value="<?= (int) $gebruiker['id'] ?>" />
                    <button type="submit" class="tabel-knop">
                      <?= $gebruiker['rol'] === 'admin' ? 'Maak gebruiker' : 'Maak admin' ?>
                    </button>
                  </form>

                  <form method="post" onsubmit="return confirm('Deze gebruiker en alle games verwijderen?')">
                    <input type="hidden" name="actie" value="verwijder_gebruiker" />
                    <input type="hidden" name="gebruiker_id" value="<?= (int) $gebruiker['id'] ?>" />
                    <button type="submit" class="tabel-knop rood">Verwijderen</button>
                  </form>
                </div>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
