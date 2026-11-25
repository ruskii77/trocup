-- Migration 005 : conformité RGPD (V1.7)

-- Date de dernière connexion : sert à supprimer les comptes inactifs depuis trop longtemps
ALTER TABLE utilisateur ADD COLUMN derniere_connexion DATETIME NULL;
UPDATE utilisateur SET derniere_connexion = date_inscription WHERE derniere_connexion IS NULL;

-- Preuve des choix de cookies (anonyme : un identifiant au hasard, les choix, la date — aucune adresse IP)
CREATE TABLE IF NOT EXISTS consentement_cookie (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  id_consentement VARCHAR(64) NOT NULL,
  choix           VARCHAR(255) NOT NULL,
  version         SMALLINT NOT NULL,
  date            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (date)
) ENGINE=InnoDB;
