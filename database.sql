-- DATABASE VOOR GAME LOG
-- Importeer dit bestand in phpMyAdmin (tabblad "Importeren").
-- Het maakt de database "gamelog" met twee tabellen: gebruikers en games.

CREATE DATABASE IF NOT EXISTS gamelog;
USE gamelog;

-- TABEL 1: de accounts
CREATE TABLE IF NOT EXISTS gebruikers (
  id INT AUTO_INCREMENT PRIMARY KEY,                -- een nummer dat vanzelf optelt: 1, 2, 3, ...
  gebruikersnaam VARCHAR(50) NOT NULL UNIQUE,       -- UNIQUE = twee mensen mogen niet dezelfde naam hebben
  email VARCHAR(255) DEFAULT NULL UNIQUE,
  wachtwoord VARCHAR(255) NOT NULL,                 -- hier komt de HASH van het wachtwoord, nooit het wachtwoord zelf
  rol ENUM('gebruiker', 'admin') NOT NULL DEFAULT 'gebruiker',
  profielfoto VARCHAR(255) DEFAULT NULL,
  aangemaakt_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABEL 2: de games
CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gebruiker_id INT NOT NULL,                        -- van welke gebruiker is deze game?
  titel VARCHAR(100) NOT NULL,
  -- enum betekent alleen een van deze opties is toegestaan
  status ENUM('wil_spelen', 'speel_ik', 'uitgespeeld') NOT NULL DEFAULT 'wil_spelen',
  op_verlanglijst TINYINT(1) NOT NULL DEFAULT 0,    -- 0 = niet op de verlanglijst, 1 = wel
  toegevoegd_op TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Verwijder je een gebruiker, dan verdwijnen zijn games automatisch ook
  FOREIGN KEY (gebruiker_id) REFERENCES gebruikers(id) ON DELETE CASCADE
);

-- JEZELF ADMIN MAKEN (vervang 'jouwnaam' door je gebruikersnaam):
-- UPDATE gebruikers SET rol = 'admin' WHERE gebruikersnaam = 'jouwnaam';
