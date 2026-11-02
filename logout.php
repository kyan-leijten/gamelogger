<?php
// WAT DOET DIT BESTAND?
// Uitloggen: de sessie leegmaken en terug naar de homepage.

session_start();
session_destroy();           // wist alles wat de server over deze bezoeker onthield
header('Location: index.php');
exit;
