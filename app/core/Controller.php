<?php
// Classe de base dont héritent tous les contrôleurs.
abstract class Controller
{
    // Remplie par public/index.php : migrations de la base pas encore appliquées
    public static array $majEnAttente = [];

    // Affiche une vue à l'intérieur du gabarit (en-tête + pied de page)
    protected function afficher(string $vue, array $donnees = [], string $titre = ''): void
    {
        extract($donnees); // ['objets' => ...] devient la variable $objets dans la vue
        $titrePage = $titre ? $titre . ' · ' . NOM_SITE : NOM_SITE . ' · ' . SLOGAN;
        // Infos utilisées par les réseaux sociaux quand on partage un lien (aperçu avec image)
        $meta = ($donnees['meta'] ?? []) + [
            'description' => 'Échange tes objets contre un peu mieux à chaque fois et construis ta chaîne d\'échanges. Gratuit, près de chez toi.',
            'image'       => URL_SITE . '/img/partage.jpg',
        ];
        // Nombre de notifications non lues, pour le badge du menu
        $connecte = utilisateur_connecte();
        $nbNotifs = ($connecte && !self::$majEnAttente) ? Notification::total($connecte['id']) : 0;
        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/' . $vue . '.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    protected function rediriger(string $route, array $params = []): void
    {
        header('Location: ' . url($route, $params));
        exit;
    }

    // Bloque la page si personne n'est connecté
    protected function exigerConnexion(): array
    {
        $u = utilisateur_connecte();
        if (!$u) {
            flash('erreur', 'Connecte-toi pour accéder à cette page.');
            $this->rediriger('utilisateur/connexion');
        }
        return $u;
    }

    // Bloque la page si l'utilisateur n'est pas admin
    protected function exigerAdmin(): array
    {
        $u = $this->exigerConnexion();
        if ($u['role'] !== 'admin') {
            flash('erreur', 'Accès réservé à l\'administrateur.');
            $this->rediriger('accueil');
        }
        return $u;
    }

    // Accepte seulement les formulaires envoyés en POST avec un jeton CSRF valide
    protected function exigerPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée.');
        }
        csrf_verifier();
    }

    protected function pageIntrouvable(): void
    {
        http_response_code(404);
        $this->afficher('erreur', ['message' => 'Cette page n\'existe pas ou plus.'], 'Introuvable');
        exit;
    }
}
