-- =====================================================
--  Échange vers le haut — script de création de la base
--  À exécuter dans phpMyAdmin (onglet SQL) ou en ligne de commande
-- =====================================================

CREATE DATABASE IF NOT EXISTS echange_vers_le_haut
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE echange_vers_le_haut;

-- On supprime dans l'ordre inverse des dépendances (pratique pour tout recréer)
DROP TABLE IF EXISTS tentative_connexion;
DROP TABLE IF EXISTS jeton;
DROP TABLE IF EXISTS signalement;
DROP TABLE IF EXISTS avis;
DROP TABLE IF EXISTS message;
DROP TABLE IF EXISTS proposition;
DROP TABLE IF EXISTS objet;
DROP TABLE IF EXISTS categorie;
DROP TABLE IF EXISTS utilisateur;

CREATE TABLE utilisateur (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  pseudo            VARCHAR(50)  NOT NULL UNIQUE,
  email             VARCHAR(255) NOT NULL UNIQUE,
  mot_de_passe_hash VARCHAR(255) NOT NULL,
  ville             VARCHAR(100) NOT NULL,
  code_postal       CHAR(5)      NOT NULL,
  departement       VARCHAR(3)   NOT NULL,          -- ex : '34', '2A', '974' (calculé depuis le code postal)
  email_verifie     TINYINT(1)   NOT NULL DEFAULT 0, -- 1 quand l'utilisateur a cliqué sur le lien reçu par email
  date_inscription  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  role              ENUM('membre','admin') NOT NULL DEFAULT 'membre',
  est_banni         TINYINT(1)   NOT NULL DEFAULT 0,
  INDEX (departement)
) ENGINE=InnoDB;

CREATE TABLE categorie (
  id  INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE objet (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  titre            VARCHAR(100) NOT NULL,
  description      TEXT         NOT NULL,
  photo            VARCHAR(255) NULL,
  etat             ENUM('neuf','tres_bon','bon','use') NOT NULL DEFAULT 'bon',
  valeur_estimee   DECIMAL(10,2) NOT NULL DEFAULT 0,
  statut           ENUM('disponible','retire') NOT NULL DEFAULT 'disponible',
  date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_utilisateur   INT NOT NULL,
  id_categorie     INT NOT NULL,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE,
  FOREIGN KEY (id_categorie)   REFERENCES categorie(id),
  INDEX (statut, date_publication)  -- accélère la recherche quand il y a beaucoup d'annonces
) ENGINE=InnoDB;

-- Une proposition : « je te donne mon objet X contre ton objet Y »
-- id_proposeur / id_destinataire gardent qui étaient les propriétaires AU MOMENT de l'échange,
-- car après un échange accepté, les objets changent de propriétaire.
CREATE TABLE proposition (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  id_objet_propose  INT NOT NULL,
  id_objet_demande  INT NOT NULL,
  id_proposeur      INT NOT NULL,
  id_destinataire   INT NOT NULL,
  statut            ENUM('en_attente','acceptee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
  date_proposition  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_reponse      DATETIME NULL,
  FOREIGN KEY (id_objet_propose) REFERENCES objet(id) ON DELETE CASCADE,
  FOREIGN KEY (id_objet_demande) REFERENCES objet(id) ON DELETE CASCADE,
  FOREIGN KEY (id_proposeur)     REFERENCES utilisateur(id) ON DELETE CASCADE,
  FOREIGN KEY (id_destinataire)  REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE message (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  contenu        TEXT NOT NULL,
  date_envoi     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_proposition INT NOT NULL,
  id_expediteur  INT NOT NULL,
  FOREIGN KEY (id_proposition) REFERENCES proposition(id) ON DELETE CASCADE,
  FOREIGN KEY (id_expediteur)  REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE avis (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  note            TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
  commentaire     TEXT NULL,
  date            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_proposition  INT NOT NULL,
  id_auteur       INT NOT NULL,
  id_destinataire INT NOT NULL,
  UNIQUE (id_proposition, id_auteur),  -- un seul avis par personne et par échange
  FOREIGN KEY (id_proposition)  REFERENCES proposition(id) ON DELETE CASCADE,
  FOREIGN KEY (id_auteur)       REFERENCES utilisateur(id) ON DELETE CASCADE,
  FOREIGN KEY (id_destinataire) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE signalement (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  motif          VARCHAR(255) NOT NULL,
  date           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut         ENUM('en_attente','traite') NOT NULL DEFAULT 'en_attente',
  id_objet       INT NOT NULL,
  id_utilisateur INT NOT NULL,
  FOREIGN KEY (id_objet)       REFERENCES objet(id) ON DELETE CASCADE,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Jetons envoyés par email (confirmation du compte, mot de passe oublié).
-- On stocke seulement l'EMPREINTE (hash) du jeton : même si la base fuite, les liens sont inutilisables.
CREATE TABLE jeton (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur INT NOT NULL,
  type           ENUM('verification','reinitialisation') NOT NULL,
  jeton_hash     CHAR(64) NOT NULL UNIQUE,
  expire_le      DATETIME NOT NULL,
  utilise        TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tentatives de connexion ratées : sert à bloquer les robots qui testent des mots de passe
CREATE TABLE tentative_connexion (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  ip    VARCHAR(45)  NOT NULL,
  date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (email), INDEX (ip)
) ENGINE=InnoDB;

INSERT INTO categorie (nom) VALUES
  ('Électronique'), ('Jeux vidéo'), ('Vêtements'), ('Maison'),
  ('Sport'), ('Livres'), ('Véhicules'), ('Autre');

-- =====================================================
--  Pour devenir admin : inscris-toi sur le site, confirme ton email, puis exécute :
--  UPDATE utilisateur SET role = 'admin' WHERE email = 'ton.email@exemple.fr';
-- =====================================================
