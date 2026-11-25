<?php
// Emails « Tu as un nouveau message » — sans spammer :
// on n'envoie un nouvel email pour un échange que si la personne a lu l'échange depuis le dernier email.
class NotifEmail
{
    public static function prevenir(array $proposition, int $idDestinataire, string $quoi): void
    {
        $u = Utilisateur::trouverParId($idDestinataire);
        if (!$u || !$u['notif_email'] || $u['supprime'] || $u['est_banni']) {
            return;
        }
        $pdo = Database::get();
        $req = $pdo->prepare('SELECT envoye_le FROM notif_email WHERE id_proposition = ? AND id_utilisateur = ?');
        $req->execute([$proposition['id'], $idDestinataire]);
        $dernierEmail = $req->fetchColumn();
        $derniereLecture = Notification::derniereLecture($proposition['id'], $idDestinataire);

        // Déjà prévenu, et pas revenu lire depuis → on n'envoie rien de plus
        if ($dernierEmail && (!$derniereLecture || $derniereLecture < $dernierEmail)) {
            return;
        }

        Mailer::envoyer(
            $u['email'], $quoi . ' sur ' . NOM_SITE, $quoi,
            "Bonjour {$u['pseudo']},\n" . $quoi . " pour l'échange « {$proposition['titre_propose']} ⇄ {$proposition['titre_demande']} ».",
            URL_SITE . '/' . url('proposition/voir', ['id' => $proposition['id']]), 'Voir l\'échange'
        );
        $pdo->prepare('INSERT INTO notif_email (id_proposition, id_utilisateur, envoye_le) VALUES (?, ?, NOW(6))
                       ON DUPLICATE KEY UPDATE envoye_le = NOW(6)')->execute([$proposition['id'], $idDestinataire]);
    }
}
