-- Migration 008 : notifications par email, réglables une par une (V1.10)
--   notif_interet     : quelqu'un s'intéresse à mon annonce (ajout en favori)
--   notif_proposition : proposition d'échange reçue, acceptée, refusée ou annulée
--   notif_message     : nouveau message dans un échange
-- Remplace l'ancien réglage unique « notif_email » (on reprend le choix déjà fait par chaque membre).

ALTER TABLE utilisateur
  ADD COLUMN notif_interet     TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN notif_proposition TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN notif_message     TINYINT(1) NOT NULL DEFAULT 1;

UPDATE utilisateur SET notif_interet = notif_email, notif_proposition = notif_email, notif_message = notif_email;

ALTER TABLE utilisateur DROP COLUMN notif_email;

-- Dernier email « ton annonce plaît » envoyé pour une annonce (au plus un par jour et par annonce)
CREATE TABLE IF NOT EXISTS notif_interet (
  id_objet  INT NOT NULL PRIMARY KEY,
  envoye_le DATETIME(6) NOT NULL,
  FOREIGN KEY (id_objet) REFERENCES objet(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
