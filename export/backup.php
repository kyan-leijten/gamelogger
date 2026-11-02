<?php
// WAT DOET DIT BESTAND?
// Maakt van al je games een bestand (JSON) dat je kunt downloaden, als backup.
// Dit bestand laat dus geen webpagina zien, maar geeft een download.

// __DIR__ is de map waarin dit bestand staat. "/.." betekent: een map omhoog.
require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/functies.php';
session_start();

// Niet ingelogd? Terug naar de inlogpagina ("../" omdat we in de map export zitten)
controleerLogin('../');

$gebruikerId = $_SESSION['gebruiker_id'];
$games = [];

// Alle games van deze gebruiker ophalen (zie register.php voor uitleg over prepared statements)
try {
  $stmt = $verbinding->prepare('SELECT titel, status, op_verlanglijst, toegevoegd_op FROM games WHERE gebruiker_id = ? ORDER BY toegevoegd_op DESC');
  $stmt->bind_param('i', $gebruikerId);
  $stmt->execute();
  $resultaat = $stmt->get_result();

  while ($rij = $resultaat->fetch_assoc()) {
    $games[] = $rij;
  }
} catch (mysqli_sql_exception $fout) {
  die('Kon geen backup maken.'); // die = stop en laat deze tekst zien
} finally {
  // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
  if (isset($stmt)) {
    $stmt->close();
  }
}

// header() vertelt de browser wat voor soort bestand dit is en dat het gedownload moet worden
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="gamelog_backup_' . date('Y-m-d') . '.json"');

// json_encode maakt van onze lijst met games een tekstbestand in JSON-formaat.
// JSON_PRETTY_PRINT zorgt dat het netjes onder elkaar komt te staan.
echo json_encode($games, JSON_PRETTY_PRINT);
exit;
