<?php
// =====================================================
//  Configuration du site
//
//  ⚠️ Ne modifie pas ce fichier pour TES réglages : il est remplacé à chaque mise à jour.
//  Mets-les dans config/config.local.php (copie config.local.exemple.php).
//  Ce fichier-là n'est jamais écrasé, et ses valeurs passent avant celles d'ici.
// =====================================================
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// ---------- Base de données ----------
// Valeurs par défaut de MAMP : port 8889, utilisateur "root", mot de passe "root".
// (XAMPP : port 3306, utilisateur "root", mot de passe vide '')
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', getenv('DB_PORT') ?: '8889');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'echange_vers_le_haut');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root');

// ---------- Site ----------
// Le nom du site : c'est le SEUL endroit à modifier pour le changer partout (site, emails, images de partage)
defined('NOM_SITE') || define('NOM_SITE', 'Visota');
// Partie du nom écrite en orange dans le logo ('' = tout le nom dans la même couleur). Ex. avec 'Visota' : 'ta' → Viso[ta]
defined('NOM_ACCENT') || define('NOM_ACCENT', 'ta');
defined('SLOGAN') || define('SLOGAN', 'Pars d\'un trombone. Vise une voiture.');

// Adresse complète du site, utilisée pour les liens dans les emails (sans / à la fin)
defined('URL_SITE') || define('URL_SITE', getenv('URL_SITE') ?: 'http://localhost:8888/projet-echange/public');

// true pendant que tu développes, false une fois le site en ligne
defined('MODE_DEV') || define('MODE_DEV', true);

// ---------- Pages légales (à compléter avant la mise en ligne, dans config.local.php) ----------
defined('EDITEUR_NOM')       || define('EDITEUR_NOM', '[Ton prénom et nom]');
defined('EDITEUR_VILLE')     || define('EDITEUR_VILLE', '[Ta ville]');
defined('EDITEUR_EMAIL')     || define('EDITEUR_EMAIL', '[ton.email@exemple.fr]');
defined('HEBERGEUR_NOM')     || define('HEBERGEUR_NOM', '[Nom de l\'hébergeur]');
defined('HEBERGEUR_ADRESSE') || define('HEBERGEUR_ADRESSE', '[Adresse et téléphone de l\'hébergeur]');

// ---------- Emails ----------
// 'fichier' : les emails ne partent pas, ils sont enregistrés dans storage/emails/
//             et consultables sur le site via « Boîte mail de test » (pratique en local)
// 'smtp'    : les emails partent vraiment (remplis les réglages SMTP ci-dessous)
defined('MAIL_MODE') || define('MAIL_MODE', getenv('MAIL_MODE') ?: 'fichier');
defined('MAIL_EXPEDITEUR') || define('MAIL_EXPEDITEUR', 'no-reply@echange-vers-le-haut.fr');

// Réglages SMTP (exemple avec Gmail : il faut un « mot de passe d'application » Google,
// pas ton mot de passe Gmail habituel)
defined('SMTP_HOTE') || define('SMTP_HOTE', 'smtp.gmail.com');
defined('SMTP_PORT') || define('SMTP_PORT', 587);
defined('SMTP_UTILISATEUR') || define('SMTP_UTILISATEUR', 'ton.adresse@gmail.com');
defined('SMTP_MOT_DE_PASSE') || define('SMTP_MOT_DE_PASSE', 'mot-de-passe-application');

// ---------- Fichiers ----------
defined('DOSSIER_UPLOADS') || define('DOSSIER_UPLOADS', __DIR__ . '/../public/uploads/');
defined('DOSSIER_EMAILS') || define('DOSSIER_EMAILS', __DIR__ . '/../storage/emails/');
defined('TAILLE_MAX_PHOTO') || define('TAILLE_MAX_PHOTO', 2 * 1024 * 1024); // 2 Mo

// ---------- Sécurité ----------
defined('MAX_TENTATIVES_CONNEXION') || define('MAX_TENTATIVES_CONNEXION', 5);  // tentatives ratées autorisées sur un même compte…
defined('MAX_TENTATIVES_IP') || define('MAX_TENTATIVES_IP', 20);        // …ou depuis une même adresse IP…
defined('DUREE_BLOCAGE_MINUTES') || define('DUREE_BLOCAGE_MINUTES', 15);    // …sur cette durée avant blocage
defined('ANNONCES_PAR_PAGE') || define('ANNONCES_PAR_PAGE', 12);
