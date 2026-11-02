<?php
// De homepage: uitleg over de site en knoppen om een account te maken of in te loggen.

// session_start() zet de sessie aan. Een sessie is het geheugen van de server over deze bezoeker
// (bijvoorbeeld: is hij ingelogd?). Dit moet bovenaan elke pagina staan.
session_start();

// De titel die in het tabblad van de browser staat
$paginaTitel = 'Game Log — Houd je games bij';

// include = plak het menu (header.php) hier in de pagina
include 'includes/header.php';
?>

<main>
  <!-- Bovenste stuk met de grote titel en de knoppen -->
  <section class="introductie">
    <div class="introductie-tekst">
      <h1>Houd je <span>games</span> bij</h1>

      <p>
        Met Game Log kun je eenvoudig bijhouden welke games je nog wilt
        spelen, momenteel speelt en al hebt uitgespeeld.
      </p>

      <div class="knoppen">
        <!-- Ben je ingelogd? Dan zie je één knop. Anders zie je twee knoppen. -->
        <?php if (isset($_SESSION['gebruiker_id'])): ?>
          <a href="games.php" class="knop">Naar mijn games</a>
        <?php else: ?>
          <a href="register.php" class="knop">Account maken</a>
          <a href="login.php" class="knop tweede">Inloggen</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Alleen versiering: zes gekleurde blokjes -->
    <div class="introductie-icoon" aria-hidden="true">
      <span></span><span></span><span></span>
      <span></span><span></span><span></span>
    </div>
  </section>

  <!-- Uitleg over de drie statussen -->
  <section id="uitleg" class="uitleg">
    <h2>Hoe werkt Game Log?</h2>

    <p class="ondertekst">
      Voeg je games toe aan je persoonlijke lijst en geef iedere game een status.
    </p>

    <div class="statussen">
      <div class="status">
        <div class="status-icoon paars">♥</div>
        <h3>Want to Play</h3>
        <p>Games die je nog wilt spelen.</p>
      </div>

      <div class="status">
        <div class="status-icoon blauw">▶</div>
        <h3>Playing</h3>
        <p>Games waar je momenteel mee bezig bent.</p>
      </div>

      <div class="status">
        <div class="status-icoon groen">✓</div>
        <h3>Completed</h3>
        <p>Games die je hebt uitgespeeld.</p>
      </div>
    </div>
  </section>

  <!-- Wat kun je ermee? -->
  <section class="mogelijkheden">
    <h2>Wat kun je met Game Log?</h2>

    <div class="mogelijkheden-lijst">
      <div>
        <span class="mogelijkheid-icoon">+</span>
        <h3>Games toevoegen</h3>
        <p>Voeg nieuwe games toe aan je persoonlijke lijst.</p>
      </div>

      <div>
        <span class="mogelijkheid-icoon">◎</span>
        <h3>Games bekijken</h3>
        <p>Bekijk al je opgeslagen games op één plek.</p>
      </div>

      <div>
        <span class="mogelijkheid-icoon">↻</span>
        <h3>Games bijhouden</h3>
        <p>Verander de status van je games wanneer je verder bent gekomen.</p>
      </div>
    </div>
  </section>

  <!-- Contact -->
  <section class="uitleg">
    <h2>Vragen?</h2>
    <p class="ondertekst">Neem gerust contact met ons op.</p>
    <a href="contact.php" class="knop tweede">Contact opnemen</a>
  </section>

  <!-- Onderste blok: weer andere tekst als je bent ingelogd -->
  <section class="aanmelden">
    <?php if (isset($_SESSION['gebruiker_id'])): ?>
      <h2>Verder met Game Log</h2>
      <p>Ga naar je lijst om een nieuwe game toe te voegen of de status aan te passen.</p>
      <a href="games.php" class="knop">Naar mijn games</a>
    <?php else: ?>
      <h2>Begin met Game Log</h2>
      <p>Maak een account aan en begin met het bijhouden van je games.</p>
      <a href="register.php" class="knop">Account maken</a>

      <p class="inloggen-tekst">
        Heb je al een account?
        <a href="login.php">Inloggen</a>
      </p>
    <?php endif; ?>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
