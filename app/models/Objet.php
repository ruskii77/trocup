<?php
class Objet
{
    // Requête de base : l'objet + le pseudo/ville/département du propriétaire + le nom de la catégorie
    // (+ la catégorie principale si l'annonce est dans une sous-catégorie)
    private const SELECT = 'SELECT o.*, u.pseudo, u.ville, u.departement, u.avatar, c.nom AS categorie,
                                   cp.id AS id_categorie_parente, cp.nom AS categorie_parente
                            FROM objet o
                            JOIN utilisateur u ON u.id = o.id_utilisateur
                            JOIN categorie c ON c.id = o.id_categorie
                            LEFT JOIN categorie cp ON cp.id = c.id_parent';

    // Les tris autorisés (liste blanche : on ne met JAMAIS directement $_GET dans un ORDER BY)
    public const TRIS = [
        'recent'      => ['Plus récents', 'o.date_publication DESC'],
        'valeur_asc'  => ['Valeur croissante', 'o.valeur_estimee ASC, o.date_publication DESC'],
        'valeur_desc' => ['Valeur décroissante', 'o.valeur_estimee DESC, o.date_publication DESC'],
    ];

    // Recherche avec filtres + pagination. Renvoie ['objets' => [...], 'total' => nombre de résultats]
    public static function rechercher(array $f, int $page): array
    {
        $where = "WHERE o.statut = 'disponible' AND u.est_banni = 0";
        $params = [];

        if ($f['q'] !== '') {
            $where .= ' AND (o.titre LIKE ? OR o.description LIKE ?)';
            $params[] = '%' . $f['q'] . '%';
            $params[] = '%' . $f['q'] . '%';
        }
        if ($f['categorie']) {
            // Une catégorie principale inclut toutes ses sous-catégories
            $where .= ' AND (o.id_categorie = ? OR c.id_parent = ?)';
            $params[] = $f['categorie'];
            $params[] = $f['categorie'];
        }
        // Filtres propres à la catégorie (Taille, Pointure, Plateforme…) : l'annonce doit avoir la bonne valeur
        foreach ($f['caracs'] ?? [] as $idCarac => $filtre) {
            if ($filtre['type'] === 'liste') {
                $where .= ' AND EXISTS (SELECT 1 FROM objet_caracteristique oc WHERE oc.id_objet = o.id AND oc.id_caracteristique = ? AND oc.id_option = ?)';
                $params[] = $idCarac;
                $params[] = $filtre['valeur'];
            } else {
                $where .= ' AND EXISTS (SELECT 1 FROM objet_caracteristique oc WHERE oc.id_objet = o.id AND oc.id_caracteristique = ? AND oc.valeur_texte LIKE ?)';
                $params[] = $idCarac;
                $params[] = '%' . addcslashes($filtre['valeur'], '%_\\') . '%';
            }
        }
        if ($f['departement'] !== '') {
            $where .= ' AND u.departement = ?';
            $params[] = $f['departement'];
        }
        if ($f['etat'] !== '') {
            $where .= ' AND o.etat = ?';
            $params[] = $f['etat'];
        }
        if ($f['valeur_min'] !== null) {
            $where .= ' AND o.valeur_estimee >= ?';
            $params[] = $f['valeur_min'];
        }
        if ($f['valeur_max'] !== null) {
            $where .= ' AND o.valeur_estimee <= ?';
            $params[] = $f['valeur_max'];
        }

        $pdo = Database::get();
        $from = ' FROM objet o JOIN utilisateur u ON u.id = o.id_utilisateur JOIN categorie c ON c.id = o.id_categorie ';

        // 1) le nombre total de résultats (pour la pagination)
        $req = $pdo->prepare('SELECT COUNT(*)' . $from . $where);
        $req->execute($params);
        $total = (int) $req->fetchColumn();

        // 2) seulement les annonces de la page demandée
        $ordre = self::TRIS[$f['tri']][1] ?? self::TRIS['recent'][1];
        $decalage = ($page - 1) * ANNONCES_PAR_PAGE;
        $req = $pdo->prepare(self::SELECT . ' ' . $where . ' ORDER BY ' . $ordre . ' LIMIT ' . ANNONCES_PAR_PAGE . ' OFFSET ' . (int) $decalage);
        $req->execute($params);

        return ['objets' => $req->fetchAll(), 'total' => $total];
    }

    // Les dernières annonces publiées (page d'accueil)
    public static function recentes(int $nombre, ?string $departement = null, ?int $saufUtilisateur = null): array
    {
        $sql = self::SELECT . " WHERE o.statut = 'disponible' AND u.est_banni = 0";
        $params = [];
        if ($departement !== null) {
            $sql .= ' AND u.departement = ?';
            $params[] = $departement;
        }
        if ($saufUtilisateur !== null) {
            $sql .= ' AND o.id_utilisateur <> ?';
            $params[] = $saufUtilisateur;
        }
        $req = Database::get()->prepare($sql . ' ORDER BY o.date_publication DESC LIMIT ' . (int) $nombre);
        $req->execute($params);
        return $req->fetchAll();
    }

    public static function nombreDisponibles(): int
    {
        return (int) Database::get()->query(
            "SELECT COUNT(*) FROM objet o JOIN utilisateur u ON u.id = o.id_utilisateur WHERE o.statut = 'disponible' AND u.est_banni = 0"
        )->fetchColumn();
    }

    public static function trouverParId(int $id): ?array
    {
        $req = Database::get()->prepare(self::SELECT . ' WHERE o.id = ?');
        $req->execute([$id]);
        return $req->fetch() ?: null;
    }

    public static function disponiblesDe(int $idUtilisateur): array
    {
        $req = Database::get()->prepare(self::SELECT . " WHERE o.id_utilisateur = ? AND o.statut = 'disponible' ORDER BY o.date_publication DESC");
        $req->execute([$idUtilisateur]);
        return $req->fetchAll();
    }

    public static function creer(array $d): int
    {
        $req = Database::get()->prepare(
            'INSERT INTO objet (titre, description, photo, etat, valeur_estimee, id_utilisateur, id_categorie)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $req->execute([$d['titre'], $d['description'], $d['photo'], $d['etat'], $d['valeur_estimee'], $d['id_utilisateur'], $d['id_categorie']]);
        return (int) Database::get()->lastInsertId();
    }

    public static function modifier(int $id, array $d): void
    {
        $req = Database::get()->prepare(
            'UPDATE objet SET titre = ?, description = ?, photo = ?, etat = ?, valeur_estimee = ?, id_categorie = ? WHERE id = ?'
        );
        $req->execute([$d['titre'], $d['description'], $d['photo'], $d['etat'], $d['valeur_estimee'], $d['id_categorie'], $id]);
    }

    // On ne supprime pas vraiment la ligne : on la "retire".
    // Comme ça, les chaînes d'échanges qui passent par cet objet restent visibles.
    public static function retirer(int $id): void
    {
        $pdo = Database::get();
        $pdo->prepare("UPDATE objet SET statut = 'retire' WHERE id = ?")->execute([$id]);
        // Les propositions encore en attente sur cet objet n'ont plus de sens
        $pdo->prepare(
            "UPDATE proposition SET statut = 'annulee', date_reponse = NOW(6)
             WHERE statut = 'en_attente' AND (id_objet_propose = ? OR id_objet_demande = ?)"
        )->execute([$id, $id]);
    }

    // ---------- Photos supplémentaires (jusqu'à 4 en plus de la photo principale) ----------
    public const MAX_PHOTOS_SUP = 4;

    // Départements qui ont des annonces disponibles, du plus actif au moins actif : [['departement' => '34', 'nombre' => 12], …]
    public static function departementsActifs(int $limite = 100): array
    {
        $req = Database::get()->prepare(
            "SELECT u.departement, COUNT(*) AS nombre, MAX(o.date_publication) AS derniere
             FROM objet o JOIN utilisateur u ON u.id = o.id_utilisateur
             WHERE o.statut = 'disponible' AND u.est_banni = 0 AND u.departement IS NOT NULL AND u.departement <> ''
             GROUP BY u.departement ORDER BY nombre DESC, u.departement LIMIT " . (int) $limite
        );
        $req->execute();
        return $req->fetchAll();
    }

    // Les villes d'un département où il y a le plus d'annonces (texte des pages « troc près de chez vous »)
    public static function villesActives(string $departement, int $limite = 5): array
    {
        $req = Database::get()->prepare(
            "SELECT u.ville FROM objet o JOIN utilisateur u ON u.id = o.id_utilisateur
             WHERE o.statut = 'disponible' AND u.est_banni = 0 AND u.departement = ? AND u.ville <> ''
             GROUP BY u.ville ORDER BY COUNT(*) DESC, u.ville LIMIT " . (int) $limite
        );
        $req->execute([$departement]);
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function photosSup(int $idObjet): array
    {
        $req = Database::get()->prepare('SELECT * FROM photo_objet WHERE id_objet = ? ORDER BY ordre, id');
        $req->execute([$idObjet]);
        return $req->fetchAll();
    }

    public static function ajouterPhotoSup(int $idObjet, string $fichier): void
    {
        Database::get()->prepare('INSERT INTO photo_objet (id_objet, fichier, ordre) VALUES (?, ?, ?)')
            ->execute([$idObjet, $fichier, count(self::photosSup($idObjet))]);
    }

    public static function supprimerPhotoSup(int $idPhoto, int $idObjet): void
    {
        $req = Database::get()->prepare('SELECT fichier FROM photo_objet WHERE id = ? AND id_objet = ?');
        $req->execute([$idPhoto, $idObjet]);
        if ($fichier = $req->fetchColumn()) {
            Image::supprimer($fichier);
            Database::get()->prepare('DELETE FROM photo_objet WHERE id = ?')->execute([$idPhoto]);
        }
    }

    // ---------- Réservé ----------
    public static function changerReserve(int $id, bool $reserve): void
    {
        Database::get()->prepare('UPDATE objet SET est_reserve = ? WHERE id = ?')->execute([$reserve ? 1 : 0, $id]);
    }

    // Les objets de l'utilisateur dont la valeur est la plus proche de celle visée (suggestions d'échange)
    public static function suggestionsPour(int $idUtilisateur, float $valeurVisee, int $nombre = 3): array
    {
        $req = Database::get()->prepare(
            self::SELECT . " WHERE o.id_utilisateur = ? AND o.statut = 'disponible' AND o.est_reserve = 0
              ORDER BY ABS(o.valeur_estimee - ?) ASC LIMIT " . (int) $nombre
        );
        $req->execute([$idUtilisateur, $valeurVisee]);
        return $req->fetchAll();
    }

    // Enregistre la photo envoyée (redimensionnée et compressée) et renvoie son nom de fichier
    public static function enregistrerPhoto(array $fichier): string
    {
        return Image::enregistrer($fichier, 'objet');
    }
}
