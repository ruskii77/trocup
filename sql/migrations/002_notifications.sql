-- Migration 002 : notifications (V1.2)

CREATE TABLE IF NOT EXISTS lecture (
  id_proposition   INT NOT NULL,
  id_utilisateur   INT NOT NULL,
  derniere_lecture DATETIME(6) NOT NULL,
  PRIMARY KEY (id_proposition, id_utilisateur),
  FOREIGN KEY (id_proposition) REFERENCES proposition(id) ON DELETE CASCADE,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Dates précises à la microseconde : un message envoyé dans la même seconde qu'une lecture ne doit pas être raté
ALTER TABLE message MODIFY date_envoi DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6);
ALTER TABLE proposition MODIFY date_reponse DATETIME(6) NULL;

-- Tout ce qui existe déjà est considéré comme lu (pour ne pas démarrer avec 50 notifications)
INSERT IGNORE INTO lecture (id_proposition, id_utilisateur, derniere_lecture)
SELECT id, id_proposeur, NOW(6) FROM proposition
UNION SELECT id, id_destinataire, NOW(6) FROM proposition;
