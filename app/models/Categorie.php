<?php
// Catégories à deux niveaux : une catégorie principale (Vêtements) peut avoir des sous-catégories (T-shirts, Chaussures…)
class Categorie
{
    // Toutes les catégories (principales et sous-catégories), à plat
    public static function toutes(): array
    {
        return Database::get()->query('SELECT * FROM categorie ORDER BY id_parent IS NOT NULL, ordre, nom')->fetchAll();
    }

    // Les catégories principales, chacune avec la liste de ses sous-catégories dans ['enfants']
    // « Autre » est toujours en dernier (ordre 99)
    public static function arbre(): array
    {
        $principales = [];
        $enfants = [];
        foreach (self::toutes() as $c) {
            if ($c['id_parent'] === null) {
                $principales[$c['id']] = $c + ['enfants' => []];
            } else {
                $enfants[] = $c;
            }
        }
        foreach ($enfants as $c) {
            if (isset($principales[$c['id_parent']])) {
                $principales[$c['id_parent']]['enfants'][] = $c;
            }
        }
        return array_values($principales);
    }

    // Catégories principales avec le nombre d'annonces disponibles, sous-catégories comprises (page d'accueil)
    public static function avecNombre(): array
    {
        return Database::get()->query(
            "SELECT p.id, p.nom, COUNT(u.id) AS nombre
             FROM categorie p
             LEFT JOIN categorie c ON c.id = p.id OR c.id_parent = p.id
             LEFT JOIN objet o ON o.id_categorie = c.id AND o.statut = 'disponible'
             LEFT JOIN utilisateur u ON u.id = o.id_utilisateur AND u.est_banni = 0
             WHERE p.id_parent IS NULL
             GROUP BY p.id, p.nom, p.ordre ORDER BY p.ordre, p.nom"
        )->fetchAll();
    }

    // Les sous-catégories d'une catégorie principale, avec leur nombre d'annonces (raccourcis sur la page Chercher)
    public static function enfantsAvecNombre(int $idParent): array
    {
        $req = Database::get()->prepare(
            "SELECT c.id, c.nom, COUNT(u.id) AS nombre
             FROM categorie c
             LEFT JOIN objet o ON o.id_categorie = c.id AND o.statut = 'disponible'
             LEFT JOIN utilisateur u ON u.id = o.id_utilisateur AND u.est_banni = 0
             WHERE c.id_parent = ?
             GROUP BY c.id, c.nom, c.ordre ORDER BY c.ordre, c.nom"
        );
        $req->execute([$idParent]);
        return $req->fetchAll();
    }

    public static function trouver(int $id): ?array
    {
        $req = Database::get()->prepare('SELECT * FROM categorie WHERE id = ?');
        $req->execute([$id]);
        return $req->fetch() ?: null;
    }

    public static function existe(int $id): bool
    {
        return self::trouver($id) !== null;
    }

    // ---------- Gestion depuis l'admin ----------

    public static function creerSousCategorie(string $nom, int $idParent): void
    {
        $req = Database::get()->prepare(
            'INSERT INTO categorie (nom, id_parent, ordre)
             SELECT ?, ?, COALESCE(MAX(ordre), 0) + 1 FROM categorie WHERE id_parent = ?'
        );
        $req->execute([$nom, $idParent, $idParent]);
    }

    public static function renommer(int $id, string $nom): void
    {
        Database::get()->prepare('UPDATE categorie SET nom = ? WHERE id = ?')->execute([$nom, $id]);
    }

    // Texte de présentation de la catégorie (référencement)
    public static function changerDescription(int $id, ?string $description): void
    {
        Database::get()->prepare('UPDATE categorie SET description = ? WHERE id = ?')->execute([$description, $id]);
    }

    public static function nombreObjets(int $id): int
    {
        // Toutes les annonces, même retirées : elles font partie de l'historique des chaînes
        $req = Database::get()->prepare('SELECT COUNT(*) FROM objet WHERE id_categorie = ?');
        $req->execute([$id]);
        return (int) $req->fetchColumn();
    }

    // Supprime une sous-catégorie vide. Ses annonces éventuelles passent dans la catégorie principale.
    public static function supprimerSousCategorie(int $id): void
    {
        $c = self::trouver($id);
        if (!$c || $c['id_parent'] === null) {
            return; // on ne supprime jamais une catégorie principale
        }
        $pdo = Database::get();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE objet SET id_categorie = ? WHERE id_categorie = ?')->execute([$c['id_parent'], $id]);
        $pdo->prepare('DELETE FROM categorie WHERE id = ?')->execute([$id]);
        $pdo->commit();
    }
}
