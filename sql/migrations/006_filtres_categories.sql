-- Migration 006 : sous-catégories + filtres propres à chaque catégorie (V1.8)
--
-- Modèle (Merise) :
--   categorie (id, nom, #id_parent)            une catégorie peut avoir une catégorie « parente »
--   caracteristique (id, nom, type, obligatoire)  ex : Taille, Pointure, Plateforme, Marque
--   caracteristique_option (id, #id_caracteristique, valeur)   ex : S, M, L pour Taille
--   categorie_caracteristique (#id_categorie, #id_caracteristique)   quelle catégorie a quel filtre
--   objet_caracteristique (#id_objet, #id_caracteristique, #id_option, valeur_texte)   la valeur choisie par l'annonce
--
-- Une caractéristique rattachée à une catégorie principale (ex : Marque → Vêtements)
-- vaut aussi pour toutes ses sous-catégories (T-shirts, Pulls…).

ALTER TABLE categorie
  ADD COLUMN id_parent INT NULL,
  ADD COLUMN ordre SMALLINT NOT NULL DEFAULT 0,
  ADD CONSTRAINT fk_categorie_parent FOREIGN KEY (id_parent) REFERENCES categorie(id);

CREATE TABLE IF NOT EXISTS caracteristique (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nom         VARCHAR(50) NOT NULL UNIQUE,
  type        ENUM('liste','texte') NOT NULL DEFAULT 'liste',
  obligatoire TINYINT(1) NOT NULL DEFAULT 0,
  ordre       SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS caracteristique_option (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  id_caracteristique INT NOT NULL,
  valeur             VARCHAR(50) NOT NULL,
  ordre              SMALLINT NOT NULL DEFAULT 0,
  UNIQUE (id_caracteristique, valeur),
  FOREIGN KEY (id_caracteristique) REFERENCES caracteristique(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorie_caracteristique (
  id_categorie       INT NOT NULL,
  id_caracteristique INT NOT NULL,
  PRIMARY KEY (id_categorie, id_caracteristique),
  FOREIGN KEY (id_categorie)       REFERENCES categorie(id) ON DELETE CASCADE,
  FOREIGN KEY (id_caracteristique) REFERENCES caracteristique(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS objet_caracteristique (
  id_objet           INT NOT NULL,
  id_caracteristique INT NOT NULL,
  id_option          INT NULL,
  valeur_texte       VARCHAR(100) NULL,
  PRIMARY KEY (id_objet, id_caracteristique),
  INDEX (id_caracteristique, id_option),
  FOREIGN KEY (id_objet)           REFERENCES objet(id) ON DELETE CASCADE,
  FOREIGN KEY (id_caracteristique) REFERENCES caracteristique(id) ON DELETE CASCADE,
  FOREIGN KEY (id_option)          REFERENCES caracteristique_option(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Ordre d'affichage des catégories principales ----------
UPDATE categorie SET ordre = 1 WHERE nom = 'Vêtements';
UPDATE categorie SET ordre = 2 WHERE nom = 'Électronique';
UPDATE categorie SET ordre = 3 WHERE nom = 'Jeux vidéo';
UPDATE categorie SET ordre = 4 WHERE nom = 'Maison';
UPDATE categorie SET ordre = 5 WHERE nom = 'Sport';
UPDATE categorie SET ordre = 6 WHERE nom = 'Livres';
UPDATE categorie SET ordre = 7 WHERE nom = 'Véhicules';
UPDATE categorie SET ordre = 99 WHERE nom = 'Autre';

-- ---------- Sous-catégories (modifiables ensuite depuis l'admin) ----------
INSERT IGNORE INTO categorie (nom, id_parent, ordre)
SELECT s.nom, p.id, s.ordre FROM categorie p JOIN (
            SELECT 'Vêtements' AS parent, 'T-shirts & polos' AS nom, 1 AS ordre
  UNION ALL SELECT 'Vêtements', 'Pulls & sweats', 2
  UNION ALL SELECT 'Vêtements', 'Vestes & manteaux', 3
  UNION ALL SELECT 'Vêtements', 'Pantalons & jeans', 4
  UNION ALL SELECT 'Vêtements', 'Robes & jupes', 5
  UNION ALL SELECT 'Vêtements', 'Chaussures', 6
  UNION ALL SELECT 'Vêtements', 'Sacs & accessoires de mode', 7
  UNION ALL SELECT 'Électronique', 'Téléphones', 1
  UNION ALL SELECT 'Électronique', 'Ordinateurs & tablettes', 2
  UNION ALL SELECT 'Électronique', 'Audio & casques', 3
  UNION ALL SELECT 'Électronique', 'TV & vidéo', 4
  UNION ALL SELECT 'Électronique', 'Photo', 5
  UNION ALL SELECT 'Jeux vidéo', 'Jeux', 1
  UNION ALL SELECT 'Jeux vidéo', 'Consoles', 2
  UNION ALL SELECT 'Jeux vidéo', 'Manettes & accessoires', 3
  UNION ALL SELECT 'Maison', 'Meubles', 1
  UNION ALL SELECT 'Maison', 'Décoration', 2
  UNION ALL SELECT 'Maison', 'Électroménager', 3
  UNION ALL SELECT 'Maison', 'Cuisine', 4
  UNION ALL SELECT 'Maison', 'Jardin & bricolage', 5
  UNION ALL SELECT 'Sport', 'Vélos', 1
  UNION ALL SELECT 'Sport', 'Fitness & musculation', 2
  UNION ALL SELECT 'Sport', 'Sports collectifs', 3
  UNION ALL SELECT 'Sport', 'Glisse', 4
  UNION ALL SELECT 'Sport', 'Camping & randonnée', 5
  UNION ALL SELECT 'Livres', 'Romans', 1
  UNION ALL SELECT 'Livres', 'BD & mangas', 2
  UNION ALL SELECT 'Livres', 'Jeunesse', 3
  UNION ALL SELECT 'Livres', 'Scolaire & pratique', 4
  UNION ALL SELECT 'Véhicules', 'Voitures', 1
  UNION ALL SELECT 'Véhicules', 'Motos & scooters', 2
  UNION ALL SELECT 'Véhicules', 'Trottinettes & vélos électriques', 3
  UNION ALL SELECT 'Véhicules', 'Pièces & équipements', 4
) s ON s.parent = p.nom;

-- ---------- Les filtres ----------
INSERT IGNORE INTO caracteristique (nom, type, obligatoire, ordre) VALUES
  ('Taille', 'liste', 1, 1),
  ('Taille pantalon', 'liste', 1, 1),
  ('Pointure', 'liste', 1, 1),
  ('Plateforme', 'liste', 1, 1),
  ('Pour', 'liste', 0, 2),
  ('Carburant', 'liste', 0, 2),
  ('Couleur', 'liste', 0, 3),
  ('Marque', 'texte', 0, 4);

-- Les choix possibles de chaque filtre « liste »
INSERT IGNORE INTO caracteristique_option (id_caracteristique, valeur, ordre)
SELECT c.id, o.valeur, o.ordre FROM caracteristique c JOIN (
            SELECT 'Taille' AS carac, 'XS' AS valeur, 1 AS ordre
  UNION ALL SELECT 'Taille', 'S', 2
  UNION ALL SELECT 'Taille', 'M', 3
  UNION ALL SELECT 'Taille', 'L', 4
  UNION ALL SELECT 'Taille', 'XL', 5
  UNION ALL SELECT 'Taille', 'XXL', 6
  UNION ALL SELECT 'Taille', '3XL et plus', 7
  UNION ALL SELECT 'Taille', 'Taille enfant', 8
  UNION ALL SELECT 'Taille pantalon', '34 (W24)', 1
  UNION ALL SELECT 'Taille pantalon', '36 (W26)', 2
  UNION ALL SELECT 'Taille pantalon', '38 (W28)', 3
  UNION ALL SELECT 'Taille pantalon', '40 (W30)', 4
  UNION ALL SELECT 'Taille pantalon', '42 (W32)', 5
  UNION ALL SELECT 'Taille pantalon', '44 (W34)', 6
  UNION ALL SELECT 'Taille pantalon', '46 (W36)', 7
  UNION ALL SELECT 'Taille pantalon', '48 et plus', 8
  UNION ALL SELECT 'Taille pantalon', 'Taille enfant', 9
  UNION ALL SELECT 'Pointure', '35 ou moins', 1
  UNION ALL SELECT 'Pointure', '36', 2
  UNION ALL SELECT 'Pointure', '37', 3
  UNION ALL SELECT 'Pointure', '38', 4
  UNION ALL SELECT 'Pointure', '39', 5
  UNION ALL SELECT 'Pointure', '40', 6
  UNION ALL SELECT 'Pointure', '41', 7
  UNION ALL SELECT 'Pointure', '42', 8
  UNION ALL SELECT 'Pointure', '43', 9
  UNION ALL SELECT 'Pointure', '44', 10
  UNION ALL SELECT 'Pointure', '45', 11
  UNION ALL SELECT 'Pointure', '46 et plus', 12
  UNION ALL SELECT 'Plateforme', 'PS5', 1
  UNION ALL SELECT 'Plateforme', 'PS4', 2
  UNION ALL SELECT 'Plateforme', 'Xbox Series', 3
  UNION ALL SELECT 'Plateforme', 'Xbox One', 4
  UNION ALL SELECT 'Plateforme', 'Nintendo Switch', 5
  UNION ALL SELECT 'Plateforme', 'PC', 6
  UNION ALL SELECT 'Plateforme', 'Rétro (anciennes consoles)', 7
  UNION ALL SELECT 'Plateforme', 'Autre', 8
  UNION ALL SELECT 'Pour', 'Homme', 1
  UNION ALL SELECT 'Pour', 'Femme', 2
  UNION ALL SELECT 'Pour', 'Mixte', 3
  UNION ALL SELECT 'Pour', 'Enfant', 4
  UNION ALL SELECT 'Carburant', 'Essence', 1
  UNION ALL SELECT 'Carburant', 'Diesel', 2
  UNION ALL SELECT 'Carburant', 'Électrique', 3
  UNION ALL SELECT 'Carburant', 'Hybride', 4
  UNION ALL SELECT 'Carburant', 'GPL', 5
  UNION ALL SELECT 'Couleur', 'Noir', 1
  UNION ALL SELECT 'Couleur', 'Blanc', 2
  UNION ALL SELECT 'Couleur', 'Gris', 3
  UNION ALL SELECT 'Couleur', 'Bleu', 4
  UNION ALL SELECT 'Couleur', 'Rouge', 5
  UNION ALL SELECT 'Couleur', 'Vert', 6
  UNION ALL SELECT 'Couleur', 'Jaune', 7
  UNION ALL SELECT 'Couleur', 'Rose', 8
  UNION ALL SELECT 'Couleur', 'Marron', 9
  UNION ALL SELECT 'Couleur', 'Beige', 10
  UNION ALL SELECT 'Couleur', 'Multicolore', 11
) o ON o.carac = c.nom;

-- Quelle catégorie a quel filtre
INSERT IGNORE INTO categorie_caracteristique (id_categorie, id_caracteristique)
SELECT cat.id, car.id FROM categorie cat JOIN (
            SELECT 'T-shirts & polos' AS categorie, 'Taille' AS carac
  UNION ALL SELECT 'Pulls & sweats', 'Taille'
  UNION ALL SELECT 'Vestes & manteaux', 'Taille'
  UNION ALL SELECT 'Robes & jupes', 'Taille'
  UNION ALL SELECT 'Pantalons & jeans', 'Taille pantalon'
  UNION ALL SELECT 'Chaussures', 'Pointure'
  UNION ALL SELECT 'Vêtements', 'Pour'
  UNION ALL SELECT 'Vêtements', 'Couleur'
  UNION ALL SELECT 'Vêtements', 'Marque'
  UNION ALL SELECT 'Jeux vidéo', 'Plateforme'
  UNION ALL SELECT 'Électronique', 'Marque'
  UNION ALL SELECT 'Sport', 'Marque'
  UNION ALL SELECT 'Véhicules', 'Marque'
  UNION ALL SELECT 'Voitures', 'Carburant'
  UNION ALL SELECT 'Motos & scooters', 'Carburant'
) l ON l.categorie = cat.nom
JOIN caracteristique car ON car.nom = l.carac;
