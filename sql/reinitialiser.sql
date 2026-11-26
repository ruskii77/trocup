-- =====================================================
--  ⚠️ DANGER : EFFACE TOUTES LES DONNÉES (comptes, objets, messages…)
--  À utiliser seulement si tu veux repartir de zéro.
--  Après l'avoir exécuté, ouvre le site : il te proposera de tout réinstaller.
--  Pour une simple mise à jour, tu n'en as PAS besoin.
-- =====================================================
USE echange_vers_le_haut;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS migration, commune, notif_interet, objet_caracteristique, categorie_caracteristique, caracteristique_option, caracteristique, consentement_cookie, limite_action, notif_email, blocage, signalement_membre, favori, photo_objet, lecture, tentative_connexion, jeton, signalement, avis, message, proposition, objet, categorie, utilisateur;
SET FOREIGN_KEY_CHECKS = 1;
