<?php
// =====================================================
//  Fichiers lus par les moteurs de recherche (Google, Bing…)
//    robots.txt  : ce que les robots peuvent visiter + où est le plan du site
//    sitemap.xml : la liste de toutes les pages à indexer (accueil, catégories, annonces…)
//  À déclarer une fois dans Google Search Console.
// =====================================================
class SeoController extends Controller
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        // Les pages privées sont de toute façon en « noindex » ; ici on évite juste que les robots perdent du temps dessus.
        echo "User-agent: *\n";
        echo "Disallow: /index.php?route=admin\n";
        echo "Disallow: /index.php?route=maj\n";
        echo "Disallow: /index.php?route=proposition\n";
        echo "Disallow: /index.php?route=notification\n";
        echo "Disallow: /index.php?route=cookies\n";
        echo "\n";
        echo 'Sitemap: ' . url_absolue('seo/sitemap') . "\n";
    }

    public function sitemap(): void
    {
        $pdo = Database::get();
        $pages = [];
        $ajouter = function (string $adresse, ?string $date = null) use (&$pages) {
            $pages[] = ['loc' => $adresse, 'lastmod' => $date ? date('Y-m-d', strtotime($date)) : null];
        };

        // Pages fixes
        foreach (['accueil', 'objet/recherche', 'page/concept', 'page/cgu', 'page/mentions', 'page/confidentialite'] as $route) {
            $ajouter(url_absolue($route));
        }

        // Catégories principales + sous-catégories qui ont au moins une annonce (une page vide n'intéresse pas Google)
        $categories = $pdo->query(
            "SELECT c.id, c.id_parent, MAX(o.date_publication) AS derniere
             FROM categorie c
             LEFT JOIN objet o ON o.id_categorie = c.id AND o.statut = 'disponible'
             GROUP BY c.id, c.id_parent"
        )->fetchAll();
        foreach ($categories as $c) {
            if ($c['id_parent'] === null || $c['derniere'] !== null) {
                $ajouter(url_absolue('objet/recherche', ['categorie' => $c['id']]), $c['derniere']);
            }
        }

        // Pages « troc près de chez vous » des départements qui ont des annonces
        foreach (Objet::departementsActifs() as $d) {
            $ajouter(url_absolue('objet/recherche', ['departement' => $d['departement']]), $d['derniere']);
        }

        // Annonces visibles
        $annonces = $pdo->query(
            "SELECT o.id, o.titre, o.date_publication
             FROM objet o JOIN utilisateur u ON u.id = o.id_utilisateur
             WHERE o.statut = 'disponible' AND u.est_banni = 0
             ORDER BY o.date_publication DESC
             LIMIT 45000"
        );
        foreach ($annonces as $o) {
            $ajouter(url_absolue('objet/voir', ['id' => $o['id'], 'slug' => $o['titre']]), $o['date_publication']);
        }

        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($pages as $p) {
            echo '  <url><loc>' . e($p['loc']) . '</loc>' . ($p['lastmod'] ? '<lastmod>' . $p['lastmod'] . '</lastmod>' : '') . "</url>\n";
        }
        echo "</urlset>\n";
    }
}
