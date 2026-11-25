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
}
