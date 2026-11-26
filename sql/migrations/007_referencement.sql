-- Migration 007 : texte de présentation des catégories (V1.9 — référencement)
-- Affiché sous le titre de la page catégorie et repris dans la description que Google montre dans ses résultats.
-- Modifiable ensuite depuis Admin → Catégories et filtres.

ALTER TABLE categorie ADD COLUMN description VARCHAR(300) NULL;

UPDATE categorie SET description = 'Troc de vêtements entre particuliers : échange t-shirts, pulls, vestes, jeans ou baskets que tu ne portes plus contre une pièce qui te plaît davantage. Gratuit et sans argent.' WHERE nom = 'Vêtements' AND id_parent IS NULL;
UPDATE categorie SET description = 'Échange ton téléphone, ta tablette, ton casque ou ton appareil photo contre un appareil qui te sert vraiment. Troc d''électronique entre particuliers, sans argent.' WHERE nom = 'Électronique' AND id_parent IS NULL;
UPDATE categorie SET description = 'Troc de jeux vidéo, consoles et manettes : PS5, PS4, Xbox, Switch, PC ou rétro. Échange les jeux que tu as finis contre ceux qui te font envie.' WHERE nom = 'Jeux vidéo' AND id_parent IS NULL;
UPDATE categorie SET description = 'Meubles, déco, électroménager, cuisine, jardin : échange les objets de la maison qui ne te servent plus contre ce dont tu as besoin, près de chez toi.' WHERE nom = 'Maison' AND id_parent IS NULL;
UPDATE categorie SET description = 'Vélos, matériel de fitness, sports collectifs, glisse, camping : troque ton équipement de sport contre celui de ta prochaine passion, entre particuliers.' WHERE nom = 'Sport' AND id_parent IS NULL;
UPDATE categorie SET description = 'Romans, BD, mangas, livres jeunesse ou scolaires : échange les livres que tu as lus contre de nouvelles lectures. Troc de livres gratuit entre particuliers.' WHERE nom = 'Livres' AND id_parent IS NULL;
UPDATE categorie SET description = 'Voitures, motos, scooters, trottinettes, pièces et équipements : le troc de véhicules entre particuliers, pour monter d''un cran échange après échange.' WHERE nom = 'Véhicules' AND id_parent IS NULL;
UPDATE categorie SET description = 'Tous les objets qui ne rentrent dans aucune case : collection, instruments, jouets, objets insolites… À échanger contre un peu mieux.' WHERE nom = 'Autre' AND id_parent IS NULL;
