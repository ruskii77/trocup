<?php
class Favori
{
    // Ajoute ou retire des favoris. Renvoie true si l'objet EST maintenant en favori.
    public static function basculer(int $idUtilisateur, int $idObjet): bool
    {
        $pdo = Database::get();
        if (self::estFavori($idUtilisateur, $idObjet)) {
            $pdo->prepare('DELETE FROM favori WHERE id_utilisateur = ? AND id_objet = ?')->execute([$idUtilisateur, $idObjet]);
            return false;
        }
        $pdo->prepare('INSERT INTO favori (id_utilisateur, id_objet) VALUES (?, ?)')->execute([$idUtilisateur, $idObjet]);
        return true;
    }

    public static function estFavori(int $idUtilisateur, int $idObjet): bool
    {
        $req = Database::get()->prepare('SELECT COUNT(*) FROM favori WHERE id_utilisateur = ? AND id_objet = ?');
        $req->execute([$idUtilisateur, $idObjet]);
        return $req->fetchColumn() > 0;
    }

    public static function nombrePour(int $idObjet): int
    {
        $req = Database::get()->prepare('SELECT COUNT(*) FROM favori WHERE id_objet = ?');
        $req->execute([$idObjet]);
        return (int) $req->fetchColumn();
    }

    // Les annonces mises en favori, encore disponibles
    public static function objetsDe(int $idUtilisateur): array
    {
        $req = Database::get()->prepare(
            "SELECT o.*, u.pseudo, u.ville, u.departement, u.avatar, c.nom AS categorie
             FROM favori f
             JOIN objet o ON o.id = f.id_objet
             JOIN utilisateur u ON u.id = o.id_utilisateur
             JOIN categorie c ON c.id = o.id_categorie
             WHERE f.id_utilisateur = ? AND o.statut = 'disponible' AND u.est_banni = 0
             ORDER BY f.date DESC"
        );
        $req->execute([$idUtilisateur]);
        return $req->fetchAll();
    }
}
