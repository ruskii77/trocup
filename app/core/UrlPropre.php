<?php
// =====================================================
//  URL « propres » (référencement)
//
//  index.php?route=objet/voir&id=12   →   annonce/12-t-shirt-nike-vintage
//  index.php?route=objet/recherche&categorie=3   →   categorie/3-vetements
//
//  - chemin()   : route + paramètres → adresse propre (utilisé par url())
//  - analyser() : adresse propre → route + paramètres (utilisé par public/index.php)
//
//  Le numéro (12) suffit à retrouver la page ; le texte après (-t-shirt-nike…) aide Google
//  et les humains à comprendre de quoi parle la page.
//  Les routes absentes de cette liste gardent la forme index.php?route=… (pages privées, formulaires…).
// =====================================================
class UrlPropre
{
    // Pages fixes : route → adresse
    private const FIXES = [
        'accueil'                => '',
        'page/concept'           => 'le-concept',
        'page/cgu'               => 'conditions-utilisation',
        'page/mentions'          => 'mentions-legales',
        'page/confidentialite'   => 'confidentialite',
        'utilisateur/connexion'  => 'connexion',
        'utilisateur/inscription'=> 'inscription',
        'seo/robots'             => 'robots.txt',
        'seo/sitemap'            => 'sitemap.xml',
    ];

    // Renvoie l'adresse propre, ou null si la route n'en a pas
    public static function chemin(string $route, array $params): ?string
    {
        $slug = isset($params['slug']) ? slug((string) $params['slug']) : '';
        unset($params['slug']);
        $suffixe = fn(string $s) => $s !== '' ? '-' . $s : '';

        if (array_key_exists($route, self::FIXES)) {
            $chemin = self::FIXES[$route] === '' ? './' : self::FIXES[$route];
        } elseif ($route === 'objet/voir' && !empty($params['id'])) {
            $chemin = 'annonce/' . (int) $params['id'] . $suffixe($slug);
            unset($params['id']);
        } elseif ($route === 'utilisateur/profil' && !empty($params['id'])) {
            $chemin = 'membre/' . (int) $params['id'] . $suffixe($slug);
            unset($params['id']);
        } elseif ($route === 'objet/recherche') {
            if (!empty($params['categorie'])) {
                $id = (int) $params['categorie'];
                $chemin = 'categorie/' . $id . $suffixe(slug(self::nomCategorie($id)));
                unset($params['categorie']);
            } else {
                $chemin = 'chercher';
            }
        } else {
            return null;
        }
        // Les autres paramètres (page, filtres…) restent après le « ? »
        return $params ? $chemin . '?' . http_build_query($params) : $chemin;
    }

    // Renvoie [route, paramètres] pour une adresse propre, ou null si elle n'existe pas
    public static function analyser(string $chemin): ?array
    {
        $chemin = trim($chemin, '/');
        $route = array_search($chemin, self::FIXES, true);
        if ($route !== false) {
            return [$route, []];
        }
        if (preg_match('#^annonce/(\d+)(-[a-z0-9-]*)?$#', $chemin, $m)) {
            return ['objet/voir', ['id' => $m[1]]];
        }
        if (preg_match('#^membre/(\d+)(-[a-z0-9-]*)?$#', $chemin, $m)) {
            return ['utilisateur/profil', ['id' => $m[1]]];
        }
        if (preg_match('#^categorie/(\d+)(-[a-z0-9-]*)?$#', $chemin, $m)) {
            return ['objet/recherche', ['categorie' => $m[1]]];
        }
        if ($chemin === 'chercher') {
            return ['objet/recherche', []];
        }
        return null;
    }

    // Nom d'une catégorie (une seule requête pour toute la page, gardée en mémoire)
    private static function nomCategorie(int $id): string
    {
        static $noms = null;
        if ($noms === null) {
            $noms = [];
            try {
                foreach (Database::get()->query('SELECT id, nom FROM categorie') as $c) {
                    $noms[(int) $c['id']] = $c['nom'];
                }
            } catch (PDOException $e) {
                // base pas encore installée : adresse sans le nom, elle marche quand même
            }
        }
        return $noms[$id] ?? '';
    }
}
