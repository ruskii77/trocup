-- =====================================================
--  À exécuter UNE SEULE FOIS, à la toute première installation.
--  Crée la base vide. Les tables sont créées ensuite par le site lui-même :
--  ouvre le site, il affiche la page « Installation du site ».
-- =====================================================
CREATE DATABASE IF NOT EXISTS echange_vers_le_haut
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
