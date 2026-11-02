<?php
// WAT DOET DIT BESTAND?
// De contactpagina. Een bezoeker vult een formulier in en wij sturen een mail.
//
// Het mailen gaat met PHPMailer. Dat is een stukje code van iemand anders, dat we met Composer
// hebben geïnstalleerd (in de map "vendor"). De mailgegevens staan in het bestand .env.

// "use" vertelt PHP welke onderdelen uit de Composer-pakketten we gebruiken
use Dotenv\Dotenv;                    // leest het .env-bestand
use PHPMailer\PHPMailer\PHPMailer;    // verstuurt de mail
use PHPMailer\PHPMailer\Exception;    // de fout als het versturen mislukt

session_start();

$melding = '';
$meldingSoort = '';
$naam = '';
$email = '';
$bericht = '';

// Is het formulier verstuurd?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $naam = trim($_POST['naam'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $bericht = trim($_POST['bericht'] ?? '');

  // Eerst controleren of alles klopt. Daarna pas mailen.
  if ($naam === '' || $email === '' || $bericht === '') {
    $melding = 'Vul alle velden in.';
    $meldingSoort = 'fout';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $melding = 'Vul een geldig e-mailadres in.';
    $meldingSoort = 'fout';
  } elseif (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    // file_exists kijkt of een bestand bestaat. __DIR__ is de map waarin dit bestand staat.
    // Zonder de map vendor kunnen we niet mailen
    $melding = 'Composer is nog niet geïnstalleerd. Typ in de terminal: composer install';
    $meldingSoort = 'fout';
  } elseif (!file_exists(__DIR__ . '/.env')) {
    $melding = 'Het bestand .env ontbreekt. Zie de README (stap 4) om het aan te maken.';
    $meldingSoort = 'fout';
  } else {
    // autoload.php laadt alle Composer-pakketten in één keer
    require __DIR__ . '/vendor/autoload.php';

    // Dotenv leest het .env-bestand en zet de waarden klaar in $_ENV
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Maak een nieuwe mail. "true" betekent: gooi een fout als er iets misgaat.
    $mail = new PHPMailer(true);

    try {
      // De mailserver (staat in .env): welke server en welke poort (een soort deurnummer)
      $mail->isSMTP();
      $mail->Host = $_ENV['MAIL_HOST'];
      $mail->Port = (int) $_ENV['MAIL_PORT'];
      $mail->CharSet = 'UTF-8'; // zodat é en ë goed aankomen

      // Staat er een gebruikersnaam in .env? Dan moet je inloggen bij de mailserver (zoals bij Gmail).
      // Staat er niks? Dan niet (zoals bij Mailpit, de nep-mailserver op je eigen computer).
      if (($_ENV['MAIL_USERNAME'] ?? '') !== '') {
        $mail->SMTPAuth = true;                                  // inloggen: ja
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // versleutelde verbinding
      } else {
        $mail->SMTPAuth = false;                                 // inloggen: nee
        $mail->SMTPSecure = '';                                  // geen versleuteling
        $mail->SMTPAutoTLS = false;                              // ook niet automatisch proberen
      }

      // De mail zelf: van en naar jezelf. Het bericht van de bezoeker staat in de tekst.
      $mail->setFrom($_ENV['MAIL_FROM'], 'Game Log');
      $mail->addAddress($_ENV['MAIL_FROM']);
      $mail->Subject = 'Nieuw bericht van ' . $naam;
      $mail->Body = "Naam: $naam\nE-mail: $email\n\n$bericht"; // \n = nieuwe regel

      $mail->send(); // versturen!

      $melding = 'Bedankt! Je bericht is verstuurd.';
      $meldingSoort = 'succes';

      // Maak het formulier weer leeg
      $naam = '';
      $email = '';
      $bericht = '';
    } catch (Exception $fout) {
      // Het versturen is mislukt: laat zien waarom
      $melding = 'Het bericht kon niet worden verstuurd: ' . $mail->ErrorInfo;
      $meldingSoort = 'fout';
    }
  }
}

$paginaTitel = 'Contact — Game Log';
include 'includes/header.php';
?>

<main class="auth-pagina">
  <section class="auth-formulier-sectie contact-sectie">
    <h1>Contact</h1>
    <p>Een vraag of opmerking? Stuur me een bericht.</p>

    <?php if ($melding !== ''): ?>
      <p class="melding <?= $meldingSoort ?>"><?= htmlspecialchars($melding) ?></p>
    <?php endif; ?>

    <form method="post" class="auth-formulier">
      <label for="naam">Naam</label>
      <input type="text" id="naam" name="naam" required value="<?= htmlspecialchars($naam) ?>" />

      <label for="email">Je e-mailadres</label>
      <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>" />

      <label for="bericht">Bericht</label>
      <textarea id="bericht" name="bericht" required><?= htmlspecialchars($bericht) ?></textarea>

      <button type="submit" class="knop">Verstuur bericht</button>
    </form>
  </section>
</main>

<?php include 'includes/footer.php'; ?>