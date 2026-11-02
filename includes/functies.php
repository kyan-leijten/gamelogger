<?php
// WAT DOET DIT BESTAND?
// Twee kleine controles die meerdere pagina's gebruiken.
// Een "function" is een stukje code met een naam. Je schrijft het één keer en roept het daarna overal aan.

// Controleert of je bent ingelogd. Zo niet, dan ga je naar de inlogpagina.
// $terug is alleen nodig voor pagina's in een submap: "../" betekent "een map omhoog".
function controleerLogin($terug = '')
{
  // $_SESSION is het geheugen van de server over deze bezoeker.
  // Na het inloggen staat daar je gebruiker_id in. Staat het er niet, dan ben je niet ingelogd.
  if (!isset($_SESSION['gebruiker_id'])) {
    header('Location: ' . $terug . 'login.php'); // stuur de bezoeker naar een andere pagina
    exit;                                        // stop meteen met de rest van de pagina
  }
}

// Controleert of je admin bent. Zo niet, dan ga je terug naar de gamespagina.
function controleerAdmin()
{
  controleerLogin();

  // De rol ('gebruiker' of 'admin') is bij het inloggen in de sessie gezet
  if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: games.php');
    exit;
  }
}
