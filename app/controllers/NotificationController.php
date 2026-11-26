<?php
class NotificationController extends Controller
{
    // Appelé toutes les 30 secondes par le JavaScript de la page (public/js/notifications.js).
    // Renvoie du JSON, pas une page HTML : {"total": 3}
    public function compte(): void
    {
        header('Content-Type: application/json');
        $u = utilisateur_connecte();
        echo json_encode(['total' => $u ? Notification::total($u['id']) : 0]);
    }

    // Lien « Ne plus recevoir ces emails » (en bas de chaque email de notification) : marche sans être connecté.
    //  - GET  : page de confirmation (un antivirus qui « clique » sur les liens d'un email ne désinscrit personne)
    //  - POST : désinscription. Aussi utilisé par le bouton « Se désabonner » de Gmail/Outlook (en-tête List-Unsubscribe).
    public function desabonner(): void
    {
        $id = (int) ($_GET['u'] ?? 0);
        $type = (string) ($_GET['t'] ?? '');
        $jeton = (string) ($_GET['j'] ?? '');
        if (!NotifEmail::jetonValide($id, $type, $jeton) || !Utilisateur::trouverParId($id)) {
            $this->pageIntrouvable();
        }
        $fait = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Utilisateur::changerNotif($id, $type, false);
            $fait = true;
        }
        $this->afficher('notifications/desabonner', [
            'type' => NotifEmail::TYPES[$type],
            'fait' => $fait,
            'lien' => url('notification/desabonner', ['u' => $id, 't' => $type, 'j' => $jeton]),
        ], 'Notifications par email');
    }
}
