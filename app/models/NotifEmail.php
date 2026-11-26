<?php
// =====================================================
//  Emails de notification, réglables un par un dans « Mon compte » :
//    interet     → quelqu'un ajoute une de mes annonces à ses favoris (au plus 1 email par jour et par annonce)
//    proposition → proposition reçue, acceptée, refusée ou annulée (toujours envoyé : c'est rare et important)
//    message     → nouveau message (anti-spam : pas de nouvel email tant que l'échange n'a pas été relu)
//
//  Chaque email contient un lien « Ne plus recevoir ces emails » qui marche sans se connecter.
//  Le lien est signé (HMAC) : impossible de désinscrire quelqu'un d'autre en changeant le numéro.
// =====================================================
class NotifEmail
{
    public const TYPES = [
        'interet'     => ['colonne' => 'notif_interet',     'libelle' => 'Intérêt pour mes annonces', 'detail' => 'quand quelqu\'un ajoute une de mes annonces à ses favoris (au plus un email par jour et par annonce)'],
        'proposition' => ['colonne' => 'notif_proposition', 'libelle' => 'Propositions d\'échange',   'detail' => 'quand je reçois une proposition, ou qu\'une proposition est acceptée, refusée ou annulée'],
        'message'     => ['colonne' => 'notif_message',     'libelle' => 'Messages',                  'detail' => 'quand je reçois un message (un seul email par échange tant que je ne l\'ai pas relu)'],
    ];

    // Proposition ou message dans un échange
    public static function prevenir(array $proposition, int $idDestinataire, string $quoi, string $type = 'message'): void
    {
        $u = self::destinataire($idDestinataire, $type);
        if (!$u) {
            return;
        }
        $pdo = Database::get();
        if ($type === 'message') {
            $req = $pdo->prepare('SELECT envoye_le FROM notif_email WHERE id_proposition = ? AND id_utilisateur = ?');
            $req->execute([$proposition['id'], $idDestinataire]);
            $dernierEmail = $req->fetchColumn();
            $derniereLecture = Notification::derniereLecture($proposition['id'], $idDestinataire);
            // Déjà prévenu, et pas revenu lire depuis → on n'envoie rien de plus
            if ($dernierEmail && (!$derniereLecture || $derniereLecture < $dernierEmail)) {
                return;
            }
        }

        self::envoyer(
            $u, $type, $quoi . ' sur ' . NOM_SITE, $quoi,
            "Bonjour {$u['pseudo']},\n" . $quoi . " pour l'échange « {$proposition['titre_propose']} ⇄ {$proposition['titre_demande']} ».",
            url_absolue('proposition/voir', ['id' => $proposition['id']]), 'Voir l\'échange'
        );
        $pdo->prepare('INSERT INTO notif_email (id_proposition, id_utilisateur, envoye_le) VALUES (?, ?, NOW(6))
                       ON DUPLICATE KEY UPDATE envoye_le = NOW(6)')->execute([$proposition['id'], $idDestinataire]);
    }

    // Quelqu'un vient d'ajouter $objet à ses favoris. On ne dit pas qui (vie privée), juste combien.
    public static function interet(array $objet, int $idInteresse): void
    {
        if ((int) $objet['id_utilisateur'] === $idInteresse) {
            return; // ses propres annonces
        }
        $u = self::destinataire((int) $objet['id_utilisateur'], 'interet');
        if (!$u) {
            return;
        }
        $pdo = Database::get();
        $req = $pdo->prepare('SELECT COUNT(*) FROM notif_interet WHERE id_objet = ? AND envoye_le > NOW(6) - INTERVAL 1 DAY');
        $req->execute([$objet['id']]);
        if ($req->fetchColumn() > 0) {
            return; // déjà prévenu aujourd'hui pour cette annonce
        }
        $nb = Favori::nombrePour((int) $objet['id']);
        self::envoyer(
            $u, 'interet', 'Ton annonce « ' . $objet['titre'] . ' » intéresse quelqu\'un', 'Ton annonce plaît ❤️',
            "Bonjour {$u['pseudo']},\nQuelqu'un vient d'ajouter « {$objet['titre']} » à ses favoris"
                . ($nb > 1 ? " ($nb personnes en tout)." : '.')
                . "\nC'est le bon moment pour vérifier que ton annonce est complète : photos, taille, état… Une proposition d'échange pourrait bientôt arriver !",
            url_absolue('objet/voir', ['id' => $objet['id'], 'slug' => $objet['titre']]), 'Voir mon annonce'
        );
        $pdo->prepare('INSERT INTO notif_interet (id_objet, envoye_le) VALUES (?, NOW(6)) ON DUPLICATE KEY UPDATE envoye_le = NOW(6)')
            ->execute([$objet['id']]);
    }

    // ---------- Désinscription en un clic ----------

    public static function jeton(int $idUtilisateur, string $type): string
    {
        return hash_hmac('sha256', $idUtilisateur . '|' . $type, CLE_SECRETE);
    }

    public static function jetonValide(int $idUtilisateur, string $type, string $jeton): bool
    {
        return isset(self::TYPES[$type]) && hash_equals(self::jeton($idUtilisateur, $type), $jeton);
    }

    public static function lienDesabonnement(int $idUtilisateur, string $type): string
    {
        return url_absolue('notification/desabonner', ['u' => $idUtilisateur, 't' => $type, 'j' => self::jeton($idUtilisateur, $type)]);
    }

    // ---------- Outils internes ----------

    // Le membre, s'il veut bien recevoir ce type d'email (et que son compte est actif), sinon null
    private static function destinataire(int $id, string $type): ?array
    {
        $u = Utilisateur::trouverParId($id);
        if (!$u || empty($u[self::TYPES[$type]['colonne']]) || $u['supprime'] || $u['est_banni'] || !$u['email_verifie']) {
            return null;
        }
        return $u;
    }

    private static function envoyer(array $u, string $type, string $sujet, string $titre, string $texte, string $lien, string $bouton): void
    {
        Mailer::envoyer($u['email'], $sujet, $titre, $texte, $lien, $bouton, [
            'raison'        => self::TYPES[$type]['libelle'],
            'desabonnement' => self::lienDesabonnement((int) $u['id'], $type),
            'reglages'      => url_absolue('utilisateur/compte') . '#notifications',
        ]);
    }
}
