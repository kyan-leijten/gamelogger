<?php
// WAT DOET DIT BESTAND?
// Maakt van al je games een PDF om te downloaden.
// Het maken van de PDF doet Dompdf: een stukje code van iemand anders (via Composer geïnstalleerd).
// Dompdf maakt van gewone HTML met CSS een PDF.

// use = zeg welk onderdeel uit het Composer-pakket we gebruiken
use Dompdf\Dompdf;

require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/functies.php';
session_start();

controleerLogin('../');

// Zonder de map vendor (Composer) werkt Dompdf niet
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
  die('Composer is nog niet geïnstalleerd. Typ in de terminal: composer install');
}
require __DIR__ . '/../vendor/autoload.php'; // laadt alle Composer-pakketten

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
  die('Kon de PDF niet maken.');
} finally {
  // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
  if (isset($stmt)) {
    $stmt->close();
  }
}

// De namen die we in de PDF laten zien
$statusNamen = [
  'wil_spelen'  => 'Want to Play',
  'speel_ik'    => 'Playing',
  'uitgespeeld' => 'Completed',
];

// De tabelrijen bouwen: voor elke game één rij.
// .= betekent: plak dit achter wat er al staat.
$rijen = '';

foreach ($games as $game) {
  $rijen .= '<tr>'
    . '<td>' . htmlspecialchars($game['titel']) . '</td>'
    . '<td>' . $statusNamen[$game['status']] . '</td>'
    . '<td>' . ($game['op_verlanglijst'] ? 'Ja' : 'Nee') . '</td>'
    . '<td>' . date('d-m-Y', strtotime($game['toegevoegd_op'])) . '</td>'
    . '</tr>';
}

// Nog geen games? Dan één rij met een bericht
if ($rijen === '') {
  $rijen = '<tr><td colspan="4">Je hebt nog geen games.</td></tr>';
}

// De pagina in HTML, met de CSS er gewoon tussen (zwart en oranje)
$html = '
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; color: #000000; font-size: 12px; }
    h1 { color: #ff7a00; border-bottom: 3px solid #000000; padding-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th { background: #000000; color: #ff7a00; text-align: left; padding: 8px; }
    td { border: 1px solid #000000; padding: 8px; }
  </style>
</head>
<body>
  <h1>Mijn games</h1>
  <p>Gebruiker: ' . htmlspecialchars($_SESSION['gebruikersnaam']) . ' | Gemaakt op: ' . date('d-m-Y') . '</p>
  <table>
    <tr>
      <th>Titel</th>
      <th>Status</th>
      <th>Verlanglijst</th>
      <th>Toegevoegd op</th>
    </tr>
    ' . $rijen . '
  </table>
</body>
</html>';

// De HTML omzetten naar een PDF en laten downloaden
$pdf = new Dompdf();                               // een nieuw, leeg PDF-programma
$pdf->getOptions()->set('defaultFont', 'DejaVu Sans'); // een lettertype dat ook é en ★ kent
$pdf->loadHtml($html);                             // geef het onze HTML
$pdf->setPaper('A4', 'portrait');                  // A4, staand
$pdf->render();                                    // maak de PDF
$pdf->stream('games_' . date('Y-m-d') . '.pdf', ['Attachment' => true]); // download hem
exit;
