-- Migration 004 : V1.5 — RGPD, anti-spam, photos multiples, favoris, réservé, signalements de membres, blocages, emails

-- Compte supprimé (anonymisé) + préférence d'emails de notification
ALTER TABLE utilisateur
  ADD COLUMN supprime    TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN notif_email TINYINT(1) NOT NULL DEFAULT 1;

-- Annonce « réservée » pendant qu'on discute d'un échange
ALTER TABLE objet ADD COLUMN est_reserve TINYINT(1) NOT NULL DEFAULT 0;

-- Photos supplémentaires d'une annonce (la photo principale reste dans objet.photo)
CREATE TABLE IF NOT EXISTS photo_objet (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  id_objet INT NOT NULL,
  fichier  VARCHAR(255) NOT NULL,
  ordre    TINYINT NOT NULL DEFAULT 0,
  FOREIGN KEY (id_objet) REFERENCES objet(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favori (
  id_utilisateur INT NOT NULL,
  id_objet       INT NOT NULL,
  date           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_utilisateur, id_objet),
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE,
  FOREIGN KEY (id_objet)       REFERENCES objet(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS signalement_membre (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  id_signale INT NOT NULL,
  id_auteur  INT NOT NULL,
  motif      VARCHAR(255) NOT NULL,
  date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut     ENUM('en_attente','traite') NOT NULL DEFAULT 'en_attente',
  FOREIGN KEY (id_signale) REFERENCES utilisateur(id) ON DELETE CASCADE,
  FOREIGN KEY (id_auteur)  REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- « Je ne veux plus avoir affaire à cette personne »
CREATE TABLE IF NOT EXISTS blocage (
  id_bloqueur INT NOT NULL,
  id_bloque   INT NOT NULL,
  date        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_bloqueur, id_bloque),
  FOREIGN KEY (id_bloqueur) REFERENCES utilisateur(id) ON DELETE CASCADE,
  FOREIGN KEY (id_bloque)   REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Dernier email de notification envoyé pour un échange (pour ne pas envoyer un email par message)
CREATE TABLE IF NOT EXISTS notif_email (
  id_proposition INT NOT NULL,
  id_utilisateur INT NOT NULL,
  envoye_le      DATETIME(6) NOT NULL,
  PRIMARY KEY (id_proposition, id_utilisateur),
  FOREIGN KEY (id_proposition) REFERENCES proposition(id) ON DELETE CASCADE,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Journal des actions limitées (anti-spam) : inscriptions, propositions, messages…
CREATE TABLE IF NOT EXISTS limite_action (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  cle  VARCHAR(100) NOT NULL,
  date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (cle, date)
) ENGINE=InnoDB;
