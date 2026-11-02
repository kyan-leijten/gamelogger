<?php
// WAT DOET DIT BESTAND?
// Maakt van al je games een XML-bestand om te downloaden.
// XML is een tekstbestand waarin alles tussen labels staat, zoals <titel>Zelda</titel>.

require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/functies.php';
session_start();

controleerLogin('../');

$gebruikerId = $_SESSION['gebruiker_id'];
$games = [];

// Alle games van deze gebruiker ophalen
try {
  $stmt = $verbinding->prepare('SELECT titel, status, op_verlanglijst, toegevoegd_op FROM games WHERE gebruiker_id = ? ORDER BY titel');
  $stmt->bind_param('i', $gebruikerId);
  $stmt->execute();
  $resultaat = $stmt->get_result();

  while ($rij = $resultaat->fetch_assoc()) {
    $games[] = $rij;
  }
} catch (mysqli_sql_exception $fout) {
  die('Kon het XML-bestand niet maken.');
} finally {
  // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
  if (isset($stmt)) {
    $stmt->close();
  }
}

// Laten downloaden als bestand
header('Content-Type: application/xml; charset=UTF-8');
header('Content-Disposition: attachment; filename="games_' . date('Y-m-d') . '.xml"');

// We schrijven de XML gewoon als tekst. \n is een nieuwe regel.
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<games gebruiker="' . htmlspecialchars($_SESSION['gebruikersnaam']) . '" gemaakt_op="' . date('Y-m-d') . '">' . "\n";

foreach ($games as $game) {
  // htmlspecialchars zorgt dat tekens als & en < in een titel geen fout geven
  echo '  <game>' . "\n";
  echo '    <titel>' . htmlspecialchars($game['titel']) . '</titel>' . "\n";
  echo '    <status>' . $game['status'] . '</status>' . "\n";
  echo '    <op_verlanglijst>' . ($game['op_verlanglijst'] ? 'ja' : 'nee') . '</op_verlanglijst>' . "\n";
  echo '    <toegevoegd_op>' . $game['toegevoegd_op'] . '</toegevoegd_op>' . "\n";
  echo '  </game>' . "\n";
}

echo '</games>' . "\n";
exit;
