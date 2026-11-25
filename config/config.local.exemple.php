<?php
// =====================================================
//  TES réglages personnels.
//  1. Copie ce fichier et renomme la copie en : config.local.php
//  2. Garde seulement les lignes que tu veux changer.
//  config.local.php n'est jamais fourni dans les mises à jour : tes réglages ne sont jamais écrasés.
// =====================================================

// --- Base de données (MAMP par défaut) ---
define('DB_PORT', '8889');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// --- Adresse du site (pour les liens dans les emails) ---
define('URL_SITE', 'http://localhost:8888/projet-echange/public');

// --- Emails réels (plus tard) ---
// define('MAIL_MODE', 'smtp');
// define('SMTP_UTILISATEUR', 'ton.adresse@gmail.com');
// define('SMTP_MOT_DE_PASSE', 'mot-de-passe-application');

// --- Pages légales (obligatoire avant la mise en ligne) ---
// define('EDITEUR_NOM', 'Prénom Nom');
// define('EDITEUR_VILLE', 'Montpellier');
// define('EDITEUR_EMAIL', 'contact@trocup.fr');
// define('HEBERGEUR_NOM', 'Nom de l\'hébergeur');
// define('HEBERGEUR_ADRESSE', 'Adresse et téléphone de l\'hébergeur');
