<?php
// WAT DOET DIT BESTAND?
// Maakt van al je games een Excel-bestand om te downloaden.
// Het maken van het Excel-bestand doet PhpSpreadsheet: een stukje code van iemand anders
// (via Composer geïnstalleerd).

// use = zeg welke onderdelen uit het Composer-pakket we gebruiken
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__ . '/../includes/database.php';
require __DIR__ . '/../includes/functies.php';
session_start();

controleerLogin('../');

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
  die('Composer is nog niet geïnstalleerd. Typ in de terminal: composer install');
}
require __DIR__ . '/../vendor/autoload.php';

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
  die('Kon het Excel-bestand niet maken.');
} finally {
  // isset = bestaat deze variabele? Zo ja, dan sluiten we de vraag af.
  if (isset($stmt)) {
    $stmt->close();
  }
}

$statusNamen = [
  'wil_spelen'  => 'Want to Play',
  'speel_ik'    => 'Playing',
  'uitgespeeld' => 'Completed',
];

// Een nieuw Excel-bestand met één werkblad
$excel = new Spreadsheet();
$blad = $excel->getActiveSheet();
$blad->setTitle('Mijn games');

// De bovenste rij (de kopjes). A1 betekent: kolom A, rij 1.
$blad->fromArray(['Titel', 'Status', 'Verlanglijst', 'Toegevoegd op'], null, 'A1');

// Die kopjes zwart met oranje letters (net als de PDF)
$kop = $blad->getStyle('A1:D1');
$kop->getFont()->setBold(true)->getColor()->setRGB('FF7A00');
$kop->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('000000');

// Voor elke game een rij, vanaf rij 2
$rij = 2;

foreach ($games as $game) {
  // De titel zetten we expres als TEKST. Anders zou Excel een titel die met = begint
  // als een formule zien, en 1942 als een getal.
  $blad->setCellValueExplicit('A' . $rij, $game['titel'], DataType::TYPE_STRING);
  $blad->setCellValue('B' . $rij, $statusNamen[$game['status']]);
  $blad->setCellValue('C' . $rij, $game['op_verlanglijst'] ? 'Ja' : 'Nee');
  $blad->setCellValue('D' . $rij, date('d-m-Y', strtotime($game['toegevoegd_op'])));
  $rij++; // tel er 1 bij op: de volgende game komt op de volgende rij
}

// Elke kolom automatisch zo breed maken dat de tekst past
foreach (['A', 'B', 'C', 'D'] as $kolom) {
  $blad->getColumnDimension($kolom)->setAutoSize(true);
}

// Laten downloaden als bestand
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="games_' . date('Y-m-d') . '.xlsx"');

$schrijver = new Xlsx($excel);        // het onderdeel dat het Excel-bestand schrijft
$schrijver->save('php://output');     // schrijf het direct naar de download
exit;
