-- Migration 003 : profils enrichis (V1.4)
-- Tout est facultatif (NULL) : les comptes existants ne sont pas touchés.

ALTER TABLE utilisateur
  ADD COLUMN bio       VARCHAR(300) NULL,  -- petite présentation
  ADD COLUMN avatar    VARCHAR(255) NULL,  -- nom du fichier de la photo de profil (dans public/uploads/avatars/)
  ADD COLUMN instagram VARCHAR(30)  NULL,  -- on stocke seulement le NOM d'utilisateur, jamais un lien complet :
  ADD COLUMN tiktok    VARCHAR(30)  NULL,  -- le site construit lui-même l'adresse, donc impossible de glisser
  ADD COLUMN snapchat  VARCHAR(30)  NULL,  -- un lien piégé vers un faux site
  ADD COLUMN youtube   VARCHAR(30)  NULL;
