<?php
class Categorie
{
    public static function toutes(): array
    {
        return Database::get()->query('SELECT * FROM categorie ORDER BY nom')->fetchAll();
    }

    // Les catégories avec le nombre d'annonces disponibles dans chacune (page d'accueil)
    public static function avecNombre(): array
    {
        return Database::get()->query(
            "SELECT c.id, c.nom, COUNT(u.id) AS nombre
             FROM categorie c
             LEFT JOIN objet o ON o.id_categorie = c.id AND o.statut = 'disponible'
             LEFT JOIN utilisateur u ON u.id = o.id_utilisateur AND u.est_banni = 0
             GROUP BY c.id, c.nom ORDER BY c.nom"
        )->fetchAll();
    }

    public static function existe(int $id): bool
    {
        $req = Database::get()->prepare('SELECT COUNT(*) FROM categorie WHERE id = ?');
        $req->execute([$id]);
        return $req->fetchColumn() > 0;
    }
}
