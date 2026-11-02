<?php
// WAT DOET DIT BESTAND?
// Maakt verbinding met de database. Elke pagina die de database nodig heeft, laadt dit bestand.

// try / catch: PHP probeert eerst wat er in "try" staat.
// Gaat dat mis, dan springt PHP meteen naar "catch" in plaats van de pagina te laten crashen.
try {
  // De gegevens: server, gebruikersnaam, wachtwoord, naam van de database.
  // Dit zijn de standaardwaarden van XAMPP. Heb jij andere? Pas ze hier aan.
  $verbinding = new mysqli('localhost', 'root', '', 'gamelog');

  // Zorgt dat tekens als é en ★ goed worden opgeslagen
  $verbinding->set_charset('utf8mb4');
} catch (mysqli_sql_exception $fout) {
  // Het lukte niet: stop de pagina en laat zien wat er mis is
  die('Verbinding met de database is mislukt: ' . $fout->getMessage());
}
