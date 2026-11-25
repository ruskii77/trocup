<?php
class Signalement
{
    public static function creer(int $idObjet, int $idUtilisateur, string $motif): void
    {
        $req = Database::get()->prepare('INSERT INTO signalement (id_objet, id_utilisateur, motif) VALUES (?, ?, ?)');
        $req->execute([$idObjet, $idUtilisateur, $motif]);
    }

    public static function enAttente(): array
    {
        return Database::get()->query(
            "SELECT s.*, o.titre, o.statut AS statut_objet, o.id_utilisateur AS id_proprietaire,
                    proprio.pseudo AS pseudo_proprietaire, proprio.est_banni,
                    auteur.pseudo AS pseudo_auteur
             FROM signalement s
             JOIN objet o ON o.id = s.id_objet
             JOIN utilisateur proprio ON proprio.id = o.id_utilisateur
             JOIN utilisateur auteur ON auteur.id = s.id_utilisateur
             WHERE s.statut = 'en_attente'
             ORDER BY s.date ASC"
        )->fetchAll();
    }

    public static function trouverParId(int $id): ?array
    {
        $req = Database::get()->prepare('SELECT * FROM signalement WHERE id = ?');
        $req->execute([$id]);
        return $req->fetch() ?: null;
    }

    // Marque comme traités tous les signalements d'un objet
    public static function traiterPourObjet(int $idObjet): void
    {
        Database::get()->prepare("UPDATE signalement SET statut = 'traite' WHERE id_objet = ?")->execute([$idObjet]);
    }

    public static function traiter(int $id): void
    {
        Database::get()->prepare("UPDATE signalement SET statut = 'traite' WHERE id = ?")->execute([$id]);
    }
}
