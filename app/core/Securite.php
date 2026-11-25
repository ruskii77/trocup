<?php
// =====================================================
//  Réglages de sécurité appliqués à chaque page (appelés au tout début de public/index.php)
// =====================================================
class Securite
{
    // Durée d'inactivité après laquelle on est déconnecté automatiquement
    public const INACTIVITE_MAX = 2 * 3600; // 2 heures

    public static function demarrer(): void
    {
        self::erreurs();
        self::entetes();
        self::session();
    }

    // En production, les erreurs PHP ne doivent JAMAIS s'afficher aux visiteurs
    // (elles révèlent des chemins de fichiers, des requêtes SQL…) : on les écrit dans un journal.
    private static function erreurs(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', MODE_DEV ? '1' : '0');
        ini_set('log_errors', '1');

        set_exception_handler(function (Throwable $e) {
            error_log($e); // détail complet dans le journal d'erreurs du serveur
            http_response_code(500);
            echo MODE_DEV
                ? '<h1>Erreur</h1><pre>' . e((string) $e) . '</pre>'
                : '<h1>Oups…</h1><p>Le site rencontre un problème technique. Réessaie dans quelques minutes.</p>';
        });
    }

    // En-têtes HTTP de sécurité : des instructions données au navigateur
    private static function entetes(): void
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        // Politique de sécurité du contenu (CSP) : le navigateur n'exécute QUE les scripts venant de notre site.
        // Si un pirate réussit à glisser un <script> dans une page, le navigateur refusera de l'exécuter.
        // ⚠️ Si un jour tu ajoutes un service externe (statistiques, vidéo YouTube…), il faudra l'autoriser ici.
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
             . "img-src 'self' data: blob:; connect-src 'self'; font-src 'self'; object-src 'none'; "
             . "base-uri 'self'; form-action 'self'; frame-ancestors 'self'"
             . ($https ? '; upgrade-insecure-requests' : ''));
        header('X-Content-Type-Options: nosniff');           // ne pas « deviner » le type des fichiers
        header('X-Frame-Options: SAMEORIGIN');               // interdit d'afficher le site dans une iframe ailleurs
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        if ($https) {
            // HSTS : pendant 1 an, le navigateur utilisera toujours https pour ce site
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    private static function session(): void
    {
        ini_set('session.use_strict_mode', '1');  // refuse les identifiants de session inventés
        ini_set('session.use_only_cookies', '1');
        session_name('visota_session');
        session_set_cookie_params([
            'httponly' => true,                    // inaccessible au JavaScript
            'samesite' => 'Lax',                   // pas envoyé par les formulaires d'autres sites
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();

        // Déconnexion automatique après 2 h sans activité
        if (isset($_SESSION['derniere_activite']) && time() - $_SESSION['derniere_activite'] > self::INACTIVITE_MAX) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['flash'][] = ['type' => 'info', 'message' => 'Tu as été déconnecté après 2 heures d\'inactivité.'];
        }
        $_SESSION['derniere_activite'] = time();
    }
}
