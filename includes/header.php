<?php
// De bovenkant van elke pagina: het begin van de HTML en het menu.
// Het staat in één bestand, dus het menu is op elke pagina hetzelfde.
// Elke pagina zet eerst $paginaTitel (de titel van het tabblad) en laadt daarna dit bestand.

// Ben je ingelogd? En ben je admin? Het menu hieronder past zich daarop aan.
$ingelogd = isset($_SESSION['gebruiker_id']);
$isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
?>
<!doctype html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Een PHP-stukje met een "=" erachter zet iets op de pagina (zoals de titel hieronder).
         htmlspecialchars maakt tekst veilig, zodat niemand er HTML-code in kan stoppen. -->
    <title><?= htmlspecialchars($paginaTitel) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
  </head>

  <body>
    <header class="navigatie">
      <a href="index.php" class="logo">
        <!-- Dit rondje draait de hele tijd rond (zie .logo-icoon in style.css) -->
        <span class="logo-icoon"></span>
        <span>GAME LOG</span>
      </a>

      <!-- Het streepjes-knopje voor het menu op een telefoon -->
      <button class="menu-knop" id="menuKnop" aria-label="Menu openen">
        <span></span><span></span><span></span>
      </button>

      <nav id="hoofdMenu">
        <a href="index.php">Home</a>

        <?php if ($ingelogd): ?>
          <!-- Dit zie je alleen als je bent ingelogd -->
          <a href="games.php">Mijn games</a>
          <a href="profiel.php">Account</a>
          <?php if ($isAdmin): ?>
            <a href="admin.php">Admin</a>
          <?php endif; ?>
          <a href="contact.php">Contact</a>
          <a href="logout.php">Uitloggen</a>
        <?php else: ?>
          <!-- Dit zie je alleen als je NIET bent ingelogd -->
          <a href="index.php#uitleg">Hoe werkt het?</a>
          <a href="contact.php">Contact</a>
          <a href="login.php">Inloggen</a>
          <a href="register.php" class="knop klein">Account maken</a>
        <?php endif; ?>
      </nav>
    </header>
