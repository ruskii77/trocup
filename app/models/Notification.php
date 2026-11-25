<?php
// Notifications des échanges : nouveaux messages, nouvelles propositions reçues, réponses reçues.
// Principe : on retient quand l'utilisateur a ouvert chaque échange pour la dernière fois (table lecture).
// Tout ce qui est arrivé après compte comme « non lu ».
class Notification
{
    // Pour chaque échange de l'utilisateur : combien de choses non lues, et de quel type
    public static function parProposition(int $idUtilisateur): array
    {
        $u = $idUtilisateur;
        $req = Database::get()->prepare(
            "SELECT p.id,
                    -- messages de l'autre personne arrivés depuis ma dernière visite
                    (SELECT COUNT(*) FROM message m
                      WHERE m.id_proposition = p.id AND m.id_expediteur <> ?
                        AND (l.derniere_lecture IS NULL OR m.date_envoi > l.derniere_lecture)) AS messages,
                    -- proposition reçue que je n'ai jamais ouverte
                    (p.id_destinataire = ? AND p.statut = 'en_attente' AND l.derniere_lecture IS NULL) AS nouvelle,
                    -- ma proposition a été acceptée ou refusée depuis ma dernière visite
                    (p.id_proposeur = ? AND p.statut IN ('acceptee', 'refusee')
                      AND (l.derniere_lecture IS NULL OR p.date_reponse > l.derniere_lecture)) AS reponse
             FROM proposition p
             LEFT JOIN lecture l ON l.id_proposition = p.id AND l.id_utilisateur = ?
             WHERE p.id_proposeur = ? OR p.id_destinataire = ?"
        );
        $req->execute([$u, $u, $u, $u, $u, $u]);

        $resultat = [];
        foreach ($req->fetchAll() as $ligne) {
            $total = (int) $ligne['messages'] + (int) $ligne['nouvelle'] + (int) $ligne['reponse'];
            if ($total > 0) {
                $resultat[(int) $ligne['id']] = [
                    'messages' => (int) $ligne['messages'],
                    'nouvelle' => (bool) $ligne['nouvelle'],
                    'reponse'  => (bool) $ligne['reponse'],
                    'total'    => $total,
                ];
            }
        }
        return $resultat;
    }

    // Le chiffre affiché dans le badge du menu
    public static function total(int $idUtilisateur): int
    {
        return array_sum(array_column(self::parProposition($idUtilisateur), 'total'));
    }

    // Date de la dernière visite (null si jamais ouvert) : sert à repérer les nouveaux messages
    public static function derniereLecture(int $idProposition, int $idUtilisateur): ?string
    {
        $req = Database::get()->prepare('SELECT derniere_lecture FROM lecture WHERE id_proposition = ? AND id_utilisateur = ?');
        $req->execute([$idProposition, $idUtilisateur]);
        return $req->fetchColumn() ?: null;
    }

    // L'utilisateur vient d'ouvrir l'échange : tout est lu
    public static function marquerLu(int $idProposition, int $idUtilisateur): void
    {
        Database::get()->prepare(
            'INSERT INTO lecture (id_proposition, id_utilisateur, derniere_lecture) VALUES (?, ?, NOW(6))
             ON DUPLICATE KEY UPDATE derniere_lecture = NOW(6)'
        )->execute([$idProposition, $idUtilisateur]);
    }
}
