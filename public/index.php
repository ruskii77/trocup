<?php
// =====================================================
//  Point d'entrée unique du site (le "routeur")
//  Toutes les pages passent par ici : index.php?route=controleur/action
//  ou une adresse « propre » (annonce/12-velo, categorie/3-vetements…) que public/.htaccess envoie ici
// =====================================================

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/core/helpers.php';
require __DIR__ . '/../app/core/Securite.php';

// Erreurs masquées en production, en-têtes de sécurité, session sécurisée (voir app/core/Securite.php)
Securite::demarrer();

require __DIR__ . '/../app/core/departements.php';
require __DIR__ . '/../app/core/Mailer.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/Controller.php';
require __DIR__ . '/../app/core/Migrations.php';
require __DIR__ . '/../app/core/Image.php';
require __DIR__ . '/../app/core/Cookies.php';
require __DIR__ . '/../app/core/UrlPropre.php';

// Chargement automatique des classes : "ObjetController" → app/controllers/ObjetController.php
spl_autoload_register(function (string $classe) {
    foreach (['controllers', 'models'] as $dossier) {
        $fichier = __DIR__ . '/../app/' . $dossier . '/' . $classe . '.php';
        if (is_file($fichier)) {
            require $fichier;
            return;
        }
    }
});

// Liste blanche des pages autorisées : rien d'autre ne peut être appelé
$routes = [
    'accueil'                => ['ObjetController', 'accueil'],
    'objet/recherche'        => ['ObjetController', 'recherche'],
    'objet/liste'            => ['ObjetController', 'recherche'], // ancienne adresse, gardée pour les vieux liens
    'objet/voir'             => ['ObjetController', 'voir'],
    'objet/creer'            => ['ObjetController', 'creer'],
    'objet/modifier'         => ['ObjetController', 'modifier'],
    'objet/retirer'          => ['ObjetController', 'retirer'],
    'objet/signaler'         => ['ObjetController', 'signaler'],
    'objet/favori'           => ['ObjetController', 'favori'],
    'objet/favoris'          => ['ObjetController', 'favoris'],
    'objet/reserver'         => ['ObjetController', 'reserver'],
    'utilisateur/signaler'   => ['UtilisateurController', 'signaler'],
    'utilisateur/bloquer'    => ['UtilisateurController', 'bloquer'],
    'admin/ignorerMembre'    => ['AdminController', 'ignorerMembre'],
    'utilisateur/inscription'=> ['UtilisateurController', 'inscription'],
    'utilisateur/connexion'  => ['UtilisateurController', 'connexion'],
    'utilisateur/deconnexion'=> ['UtilisateurController', 'deconnexion'],
    'utilisateur/profil'     => ['UtilisateurController', 'profil'],
    'utilisateur/compte'     => ['UtilisateurController', 'compte'],
    'utilisateur/verificationEnvoyee'  => ['UtilisateurController', 'verificationEnvoyee'],
    'utilisateur/verifier'             => ['UtilisateurController', 'verifier'],
    'utilisateur/renvoyerVerification' => ['UtilisateurController', 'renvoyerVerification'],
    'utilisateur/motDePasseOublie'     => ['UtilisateurController', 'motDePasseOublie'],
    'utilisateur/reinitialiser'        => ['UtilisateurController', 'reinitialiser'],
    'proposition/nouvelle'   => ['PropositionController', 'nouvelle'],
    'proposition/mes'        => ['PropositionController', 'mes'],
    'proposition/voir'       => ['PropositionController', 'voir'],
    'proposition/accepter'   => ['PropositionController', 'accepter'],
    'proposition/refuser'    => ['PropositionController', 'refuser'],
    'proposition/annuler'    => ['PropositionController', 'annuler'],
    'proposition/message'    => ['PropositionController', 'message'],
    'proposition/avis'       => ['PropositionController', 'avis'],
    'admin/index'            => ['AdminController', 'index'],
    'admin/retirerObjet'     => ['AdminController', 'retirerObjet'],
    'admin/ignorer'          => ['AdminController', 'ignorer'],
    'admin/bannir'           => ['AdminController', 'bannir'],
    'admin/debannir'         => ['AdminController', 'debannir'],
    'dev/emails'             => ['DevController', 'emails'],
    'page/mentions'          => ['PageController', 'mentions'],
    'page/confidentialite'   => ['PageController', 'confidentialite'],
    'page/cgu'               => ['PageController', 'cgu'],
    'utilisateur/exporter'   => ['UtilisateurController', 'exporter'],
    'utilisateur/supprimerCompte' => ['UtilisateurController', 'supprimerCompte'],
    'notification/compte'    => ['NotificationController', 'compte'],
    'maj/index'              => ['MajController', 'index'],
    'cookies/journal'        => ['CookiesController', 'journal'],
    'admin/nettoyer'         => ['AdminController', 'nettoyer'],
    'admin/categories'       => ['AdminController', 'categories'],
    'page/concept'           => ['PageController', 'concept'],
    'page/introuvable'       => ['PageController', 'introuvable'],
    'notification/desabonner'=> ['NotificationController', 'desabonner'],
    'geo/communes'           => ['GeoController', 'communes'],
    'geo/recherche'          => ['GeoController', 'recherche'],
    'geo/autour'             => ['GeoController', 'autour'],
    'seo/robots'             => ['SeoController', 'robots'],
    'seo/sitemap'            => ['SeoController', 'sitemap'],
];

try {
    $route = $_GET['route'] ?? null;
    if ($route === null) {
        // Adresse propre : on retrouve la page à partir du chemin (ex : annonce/12-velo → objet/voir, id 12)
        $chemin = substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', strlen(base_chemin()) - 1);
        $chemin = rawurldecode(trim($chemin, '/'));
        if ($chemin === '' || $chemin === 'index.php') {
            $route = 'accueil';
        } elseif ($trouve = UrlPropre::analyser($chemin)) {
            [$route, $params] = $trouve;
            $_GET += $params;
        } else {
            $route = 'page/introuvable';
        }
    }
    // Route inconnue → vraie page 404 (Google ne doit pas indexer des pages qui n'existent pas)
    if (!isset($routes[$route])) {
        $route = 'page/introuvable';
    }

    // ---------- La base est-elle à jour ? ----------
    $enAttente = Migrations::enAttente();
    Controller::$majEnAttente = $enAttente;
    if ($enAttente) {
        if (Migrations::baseVide()) {
            // Premier lancement : on envoie sur la page d'installation
            unset($_SESSION['utilisateur']);
            $route = 'maj/index';
        } elseif (!in_array($route, ['maj/index', 'utilisateur/connexion', 'utilisateur/deconnexion', 'seo/robots'], true)) {
            if (est_admin()) {
                $route = 'maj/index'; // l'admin est envoyé sur la page de mise à jour
            } else {
                http_response_code(503);
                exit('<h1>Site en maintenance</h1><p>Une mise à jour est en cours, reviens dans quelques minutes.</p>'
                   . '<p><a href="index.php?route=utilisateur/connexion">Connexion administrateur</a></p>');
            }
        }
    }

    // Si l'utilisateur connecté a été banni entre-temps, on le déconnecte
    if (!Migrations::baseVide() && $u = utilisateur_connecte()) {
        $frais = Utilisateur::trouverParId($u['id']);
        if (!$frais || $frais['est_banni']) {
            $_SESSION = [];
            flash('erreur', 'Ton compte a été suspendu.');
        } else {
            // garde le rôle et le département à jour
            $_SESSION['utilisateur']['role'] = $frais['role'];
            $_SESSION['utilisateur']['departement'] = $frais['departement'];
            $_SESSION['utilisateur']['avatar'] = $frais['avatar'] ?? null;
        }
    }

    // Nettoyage RGPD automatique (au plus une fois par jour)
    if (!$enAttente) {
        Maintenance::siNecessaire();
    }

    [$classe, $methode] = $routes[$route];
    (new $classe())->$methode();

} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage()); // le détail va dans les logs, jamais à l'écran
    echo MODE_DEV
        ? '<h1>Erreur de base de données</h1><p>' . e($e->getMessage()) . '</p><p>Vérifie que MySQL est démarré et config/config.local.php.</p>'
        : '<h1>Oups…</h1><p>Le site rencontre un problème technique. Réessaie dans quelques minutes.</p>';
}
