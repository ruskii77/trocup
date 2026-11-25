<?php
class CookiesController extends Controller
{
    // Reçoit le choix du visiteur (envoyé par public/js/cookies.js) et le garde comme preuve, sans donnée personnelle
    public function journal(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('{}');
        }
        $donnees = json_decode(file_get_contents('php://input'), true);
        if (!is_array($donnees) || empty($donnees['id']) || !preg_match('/^[A-Za-z0-9.\-]{8,64}$/', (string) $donnees['id'])) {
            http_response_code(400);
            exit('{}');
        }
        // On ne garde que les catégories connues, en vrai/faux
        $choix = [];
        foreach (Cookies::categories() as $cle => $cat) {
            if (!$cat['obligatoire']) {
                $choix[$cle] = !empty($donnees[$cle]);
            }
        }
        Database::get()->prepare('INSERT INTO consentement_cookie (id_consentement, choix, version) VALUES (?, ?, ?)')
            ->execute([$donnees['id'], json_encode($choix), Cookies::VERSION]);
        echo '{"ok":true}';
    }
}
