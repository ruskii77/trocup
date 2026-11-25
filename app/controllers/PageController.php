<?php
// Pages fixes : mentions légales, confidentialité, conditions d'utilisation
class PageController extends Controller
{
    // « Le concept » : explique l'échange vers le haut aux nouveaux venus
    public function concept(): void
    {
        $this->afficher('pages/concept', [
            'meta' => [
                'description' => NOM_SITE . ' : échange un objet contre un peu mieux, puis recommence. Le principe, les règles et les conseils pour échanger en sécurité.',
                'image' => URL_SITE . '/img/partage.jpg',
            ],
        ], 'Le concept — comment ça marche');
    }

    public function mentions(): void
    {
        $this->afficher('pages/mentions', [], 'Mentions légales');
    }

    public function confidentialite(): void
    {
        $this->afficher('pages/confidentialite', [], 'Politique de confidentialité');
    }

    public function cgu(): void
    {
        $this->afficher('pages/cgu', [], 'Conditions d\'utilisation');
    }
}
