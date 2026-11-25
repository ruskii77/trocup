<?php
// Pages fixes : mentions légales, confidentialité, conditions d'utilisation
class PageController extends Controller
{
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
