<?php
// Pages fixes : mentions légales, confidentialité, conditions d'utilisation
class PageController extends Controller
{
    // « Le concept » : explique l'échange vers le haut aux nouveaux venus
    public function concept(): void
    {
        $this->afficher('pages/concept', [
            'meta' => [
                'description' => 'L\'échange vers le haut (troc « trade-up ») : échange un objet contre un peu mieux, puis recommence. Le principe, les règles et les conseils pour échanger en sécurité.',
                'robots' => 'index, follow',
                'canonical' => url_absolue('page/concept'),
            ],
        ], 'Le concept : l\'échange vers le haut, comment ça marche');
    }

    public function mentions(): void
    {
        $this->afficher('pages/mentions', ['meta' => $this->metaIndexable('page/mentions', 'Mentions légales de ' . NOM_SITE . ' : éditeur, hébergeur et contact.')], 'Mentions légales');
    }

    public function confidentialite(): void
    {
        $this->afficher('pages/confidentialite', ['meta' => $this->metaIndexable('page/confidentialite', 'Comment ' . NOM_SITE . ' protège tes données personnelles : données collectées, durées de conservation, cookies et droits RGPD.')], 'Politique de confidentialité');
    }

    public function cgu(): void
    {
        $this->afficher('pages/cgu', ['meta' => $this->metaIndexable('page/cgu', 'Conditions d\'utilisation de ' . NOM_SITE . ' : règles des échanges, objets interdits, modération.')], 'Conditions d\'utilisation');
    }

    // Adresse inconnue : vraie erreur 404 (Google retire la page de ses résultats)
    public function introuvable(): void
    {
        $this->pageIntrouvable();
    }

    private function metaIndexable(string $route, string $description): array
    {
        return ['description' => $description, 'robots' => 'index, follow', 'canonical' => url_absolue($route)];
    }
}
