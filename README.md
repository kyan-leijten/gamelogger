# Game Log

Een website waarmee je bijhoudt welke games je nog wilt spelen, speelt of hebt uitgespeeld.
Schoolproject Web Development, Yonder.

## Wat kan de site?

- Account maken en inloggen (wachtwoord wordt opgeslagen als hash met salt)
- Games toevoegen, de status wijzigen (Want to Play, Playing, Completed) en verwijderen
- Een verlanglijst met sterretjes
- Accountpagina met profielfoto en cijfers
- Adminpagina: gebruikers bekijken, admin maken en verwijderen
- Contactpagina die een mail stuurt (PHPMailer)
- Je games downloaden als backup (JSON), PDF, Excel (.xlsx) of XML
- Draaiend logo en animaties, en het werkt op telefoon, tablet en computer

## Installeren

Je hebt nodig: XAMPP met **PHP 8.2 of hoger** en [Composer](https://getcomposer.org/).

1. Zet de map in `htdocs` van XAMPP en start Apache en MySQL.
2. Zet in `C:\xampp\php\php.ini` de extensies `gd` en `zip` aan (haal de `;` weg voor `extension=gd` en `extension=zip`) en start Apache opnieuw. Zonder deze twee lukt `composer install` niet.
3. Importeer `database.sql` in phpMyAdmin (tabblad "Importeren"). De database `gamelog` wordt dan vanzelf aangemaakt.
4. Typ in de terminal, in de projectmap: `composer install` (maakt de map `vendor`).
5. Maak in de projectmap een nieuw bestand `.env` (met de punt ervoor) met dit erin:
   ```
   MAIL_HOST=127.0.0.1
   MAIL_PORT=1025
   MAIL_USERNAME=
   MAIL_PASSWORD=
   MAIL_FROM=ik@gamelogger.local
   ```
   Dit is de instelling voor Mailpit. Het bestand `.env` staat niet op GitHub.
6. Wil je de contactpagina testen? Download [Mailpit](https://github.com/axllent/mailpit/releases/latest), start `mailpit.exe` en open `http://localhost:8025`. Daar zie je de berichten die de contactpagina verstuurt.
7. Maak jezelf admin met deze query in phpMyAdmin: `UPDATE gebruikers SET rol = 'admin' WHERE gebruikersnaam = 'jouwnaam';` Log daarna opnieuw in.
8. Open `http://localhost/` gevolgd door de naam van je map.

## Mappen

```
gamelogger/
├── index.php, login.php, register.php, logout.php   homepage en inloggen
├── games.php, profiel.php, contact.php, admin.php   de andere pagina's
├── export/            bestanden die iets laten downloaden (backup, pdf, xls, xml)
├── includes/          stukjes code die elke pagina gebruikt (menu, database, controles)
├── css/               de opmaak
├── js/                JavaScript
├── uploads/           geüploade profielfoto's (wordt vanzelf aangemaakt)
├── vendor/            Composer-pakketten (komt van composer install, staat niet op GitHub)
├── database.sql       de database
├── composer.json      welke Composer-pakketten we gebruiken
├── composer.lock      de precieze versies van die pakketten
├── .env               je eigen mailinstellingen (maak je zelf aan, staat niet op GitHub)
└── .gitignore         wat Git moet negeren (vendor, .env en de profielfoto's)
```

## Hoe werkt de code? (kort)

- **Elke pagina** laadt `includes/header.php` (het menu) en `includes/footer.php`. Daardoor is het menu overal hetzelfde.
- **Sessie:** na het inloggen onthoudt de server wie je bent (`$_SESSION`). `controleerLogin()` stuurt je naar de inlogpagina als je niet bent ingelogd.
- **Database:** alle vragen aan de database zijn _prepared statements_ (eerst de vraag met `?`, daarna de waarden). Zo kan niemand de database kapotmaken via een invulveld.
- **try / catch / finally:** gaat er iets mis met de database, dan krijg je een nette melding in plaats van een kapotte pagina.
- **Wachtwoord:** `password_hash` maakt een hash met een willekeurige salt. Bij het inloggen controleert `password_verify` het wachtwoord.
- **Melding na een actie:** na een actie stuurt PHP je terug naar dezelfde pagina met `?melding=...` in de link. De pagina laat dan een melding zien die met CSS inschuift en na 3 seconden verdwijnt.
- **Composer:** een programma dat stukjes code van anderen binnenhaalt (in de map `vendor`): PHPMailer (mail), Dompdf (PDF), PhpSpreadsheet (Excel) en phpdotenv (het `.env`-bestand lezen).

## Auteur

Kyan Leijten, Web Development, Yonder MBO Tilburg
