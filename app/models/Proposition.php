<?php
class Proposition
{
    // La proposition + les infos des deux objets et des deux personnes
    private const SELECT = 'SELECT p.*,
               op.titre AS titre_propose, op.photo AS photo_propose, op.valeur_estimee AS valeur_propose,
               od.titre AS titre_demande, od.photo AS photo_demande, od.valeur_estimee AS valeur_demande,
               up.pseudo AS pseudo_proposeur, ud.pseudo AS pseudo_destinataire,
               up.avatar AS avatar_proposeur, ud.avatar AS avatar_destinataire
        FROM proposition p
        JOIN objet op ON op.id = p.id_objet_propose
        JOIN objet od ON od.id = p.id_objet_demande
        JOIN utilisateur up ON up.id = p.id_proposeur
        JOIN utilisateur ud ON ud.id = p.id_destinataire';

    public static function nombreAcceptees(): int
    {
        return (int) Database::get()->query("SELECT COUNT(*) FROM proposition WHERE statut = 'acceptee'")->fetchColumn();
    }

    public static function trouverParId(int $id): ?array
    {
        $req = Database::get()->prepare(self::SELECT . ' WHERE p.id = ?');
        $req->execute([$id]);
        return $req->fetch() ?: null;
    }

    public static function creer(int $idObjetPropose, int $idObjetDemande, int $idProposeur, int $idDestinataire): int
    {
        $req = Database::get()->prepare(
            'INSERT INTO proposition (id_objet_propose, id_objet_demande, id_proposeur, id_destinataire) VALUES (?, ?, ?, ?)'
        );
        $req->execute([$idObjetPropose, $idObjetDemande, $idProposeur, $idDestinataire]);
        return (int) Database::get()->lastInsertId();
    }

    public static function existeDejaEnAttente(int $idObjetPropose, int $idObjetDemande): bool
    {
        $req = Database::get()->prepare(
            "SELECT COUNT(*) FROM proposition WHERE id_objet_propose = ? AND id_objet_demande = ? AND statut = 'en_attente'"
        );
        $req->execute([$idObjetPropose, $idObjetDemande]);
        return $req->fetchColumn() > 0;
    }

    public static function recues(int $idUtilisateur): array
    {
        $req = Database::get()->prepare(self::SELECT . ' WHERE p.id_destinataire = ? ORDER BY p.date_proposition DESC');
        $req->execute([$idUtilisateur]);
        return $req->fetchAll();
    }

    public static function envoyees(int $idUtilisateur): array
    {
        $req = Database::get()->prepare(self::SELECT . ' WHERE p.id_proposeur = ? ORDER BY p.date_proposition DESC');
        $req->execute([$idUtilisateur]);
        return $req->fetchAll();
    }

    public static function changerStatut(int $id, string $statut): void
    {
        $req = Database::get()->prepare('UPDATE proposition SET statut = ?, date_reponse = NOW(6) WHERE id = ?');
        $req->execute([$statut, $id]);
    }

    // Accepter un échange : tout se fait dans une TRANSACTION (tout réussit ou rien ne change)
    public static function accepter(array $p): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            self::changerStatut($p['id'], 'acceptee');

            // Les deux objets changent de propriétaire
            $req = $pdo->prepare('UPDATE objet SET id_utilisateur = ?, est_reserve = 0 WHERE id = ?');
            $req->execute([$p['id_destinataire'], $p['id_objet_propose']]);
            $req->execute([$p['id_proposeur'], $p['id_objet_demande']]);

            // Les autres propositions en attente sur ces deux objets sont annulées
            $pdo->prepare(
                "UPDATE proposition SET statut = 'annulee', date_reponse = NOW(6)
                 WHERE statut = 'en_attente' AND id <> ?
                   AND (id_objet_propose IN (?, ?) OR id_objet_demande IN (?, ?))"
            )->execute([$p['id'], $p['id_objet_propose'], $p['id_objet_demande'], $p['id_objet_propose'], $p['id_objet_demande']]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // La chaîne d'échanges d'un utilisateur : ce qu'il a donné et reçu, dans l'ordre
    public static function chaine(int $idUtilisateur): array
    {
        $req = Database::get()->prepare(self::SELECT . "
            WHERE p.statut = 'acceptee' AND (p.id_proposeur = ? OR p.id_destinataire = ?)
            ORDER BY p.date_reponse ASC, p.id ASC");
        $req->execute([$idUtilisateur, $idUtilisateur]);

        $etapes = [];
        foreach ($req->fetchAll() as $p) {
            $estProposeur = (int) $p['id_proposeur'] === $idUtilisateur;
            $etapes[] = [
                'date'          => $p['date_reponse'],
                'donne_titre'   => $estProposeur ? $p['titre_propose'] : $p['titre_demande'],
                'donne_photo'   => $estProposeur ? $p['photo_propose'] : $p['photo_demande'],
                'donne_valeur'  => (float) ($estProposeur ? $p['valeur_propose'] : $p['valeur_demande']),
                'recu_titre'    => $estProposeur ? $p['titre_demande'] : $p['titre_propose'],
                'recu_photo'    => $estProposeur ? $p['photo_demande'] : $p['photo_propose'],
                'recu_valeur'   => (float) ($estProposeur ? $p['valeur_demande'] : $p['valeur_propose']),
                'avec'          => $estProposeur ? $p['pseudo_destinataire'] : $p['pseudo_proposeur'],
            ];
        }
        return $etapes;
    }
}
