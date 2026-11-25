<?php
// =====================================================
//  Caractéristiques = les filtres propres à chaque catégorie
//  (Taille pour les T-shirts, Pointure pour les chaussures, Plateforme pour les jeux vidéo…)
//
//  Deux types :
//    - « liste » : on choisit dans une liste (S, M, L…)  → on enregistre l'id de l'option
//    - « texte » : on écrit librement (Marque)            → on enregistre le texte
//
//  Une caractéristique rattachée à une catégorie principale vaut aussi pour ses sous-catégories.
// =====================================================
class Caracteristique
{
    public const LONGUEUR_TEXTE = 50;

    // Toutes les caractéristiques avec leurs options et les catégories où elles s'appliquent
    // (sous-catégories comprises) : ['id', 'nom', 'type', 'obligatoire', 'options' => [id => valeur], 'categories' => [ids], 'rattachees' => [ids]]
    public static function toutes(): array
    {
        $pdo = Database::get();
        $caracs = [];
        foreach ($pdo->query('SELECT * FROM caracteristique ORDER BY ordre, nom') as $c) {
            $caracs[$c['id']] = $c + ['options' => [], 'categories' => [], 'rattachees' => []];
        }
        foreach ($pdo->query('SELECT * FROM caracteristique_option ORDER BY ordre, id') as $o) {
            $caracs[$o['id_caracteristique']]['options'][$o['id']] = $o['valeur'];
        }
        // La catégorie rattachée + ses sous-catégories
        $liens = $pdo->query(
            'SELECT cc.id_caracteristique, cc.id_categorie AS rattachee, c.id AS categorie
             FROM categorie_caracteristique cc
             JOIN categorie c ON c.id = cc.id_categorie OR c.id_parent = cc.id_categorie'
        );
        foreach ($liens as $l) {
            $caracs[$l['id_caracteristique']]['categories'][] = (int) $l['categorie'];
            $caracs[$l['id_caracteristique']]['rattachees'][] = (int) $l['rattachee'];
        }
        foreach ($caracs as &$c) {
            $c['categories'] = array_values(array_unique($c['categories']));
            $c['rattachees'] = array_values(array_unique($c['rattachees']));
        }
        return $caracs;
    }

    // Les caractéristiques qui s'appliquent à une catégorie (la sienne + celles de sa catégorie principale)
    public static function pourCategorie(?int $idCategorie): array
    {
        if (!$idCategorie) {
            return [];
        }
        return array_filter(self::toutes(), fn($c) => in_array($idCategorie, $c['categories'], true));
    }

    // Vérifie les valeurs envoyées par le formulaire d'annonce ($saisie = $_POST['c'] : [id caractéristique => valeur])
    // Renvoie [valeurs propres, erreurs]. Les caractéristiques qui ne concernent pas la catégorie sont ignorées.
    public static function valider(int $idCategorie, $saisie): array
    {
        $saisie = is_array($saisie) ? $saisie : [];
        $valeurs = [];
        $erreurs = [];
        foreach (self::pourCategorie($idCategorie) as $id => $c) {
            $brut = is_string($saisie[$id] ?? null) ? trim($saisie[$id]) : '';
            if ($brut === '') {
                if ($c['obligatoire']) {
                    $erreurs[] = 'Indique : ' . $c['nom'] . '.';
                }
                continue;
            }
            if ($c['type'] === 'liste') {
                if (!isset($c['options'][(int) $brut])) {
                    $erreurs[] = 'Choix invalide pour : ' . $c['nom'] . '.';
                    continue;
                }
                $valeurs[$id] = (int) $brut;
            } else {
                $valeurs[$id] = mb_substr($brut, 0, self::LONGUEUR_TEXTE);
            }
        }
        return [$valeurs, $erreurs];
    }

    // Remplace les caractéristiques d'une annonce par celles du formulaire
    public static function enregistrer(int $idObjet, array $valeurs): void
    {
        $pdo = Database::get();
        $pdo->prepare('DELETE FROM objet_caracteristique WHERE id_objet = ?')->execute([$idObjet]);
        $req = $pdo->prepare('INSERT INTO objet_caracteristique (id_objet, id_caracteristique, id_option, valeur_texte) VALUES (?, ?, ?, ?)');
        foreach ($valeurs as $idCarac => $v) {
            $req->execute([$idObjet, $idCarac, is_int($v) ? $v : null, is_int($v) ? null : $v]);
        }
    }

    // Les valeurs d'une annonce pour pré-remplir le formulaire : [id caractéristique => id option ou texte]
    public static function valeursObjet(int $idObjet): array
    {
        $req = Database::get()->prepare('SELECT id_caracteristique, id_option, valeur_texte FROM objet_caracteristique WHERE id_objet = ?');
        $req->execute([$idObjet]);
        $valeurs = [];
        foreach ($req as $l) {
            $valeurs[$l['id_caracteristique']] = $l['id_option'] !== null ? (int) $l['id_option'] : $l['valeur_texte'];
        }
        return $valeurs;
    }

    // Ce qu'on affiche sur l'annonce : [['nom' => 'Taille', 'valeur' => 'M'], …]
    public static function affichageObjet(int $idObjet): array
    {
        $req = Database::get()->prepare(
            'SELECT c.nom, COALESCE(o.valeur, oc.valeur_texte) AS valeur
             FROM objet_caracteristique oc
             JOIN caracteristique c ON c.id = oc.id_caracteristique
             LEFT JOIN caracteristique_option o ON o.id = oc.id_option
             WHERE oc.id_objet = ?
             ORDER BY c.ordre, c.nom'
        );
        $req->execute([$idObjet]);
        return $req->fetchAll();
    }

    // Lit les filtres de la page Chercher ($_GET['c']) pour une catégorie donnée
    // Renvoie [id caractéristique => ['type' => …, 'valeur' => …]]
    public static function filtresRecherche(?int $idCategorie, $saisie): array
    {
        $saisie = is_array($saisie) ? $saisie : [];
        $filtres = [];
        foreach (self::pourCategorie($idCategorie) as $id => $c) {
            $brut = is_string($saisie[$id] ?? null) ? trim($saisie[$id]) : '';
            if ($brut === '') {
                continue;
            }
            if ($c['type'] === 'liste' && isset($c['options'][(int) $brut])) {
                $filtres[$id] = ['type' => 'liste', 'valeur' => (int) $brut];
            } elseif ($c['type'] === 'texte') {
                $filtres[$id] = ['type' => 'texte', 'valeur' => mb_substr($brut, 0, self::LONGUEUR_TEXTE)];
            }
        }
        return $filtres;
    }

    // ---------- Gestion depuis l'admin ----------

    // $options : liste de textes (pour le type liste). $categories : ids des catégories rattachées.
    public static function creer(string $nom, string $type, bool $obligatoire, array $options, array $categories): void
    {
        $pdo = Database::get();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO caracteristique (nom, type, obligatoire, ordre) VALUES (?, ?, ?, 5)')
            ->execute([$nom, $type, $obligatoire ? 1 : 0]);
        $id = (int) $pdo->lastInsertId();
        foreach ($options as $valeur) {
            self::ajouterOption($id, $valeur);
        }
        self::changerCategories($id, $categories);
        $pdo->commit();
    }

    public static function changerCategories(int $id, array $categories): void
    {
        $pdo = Database::get();
        $pdo->prepare('DELETE FROM categorie_caracteristique WHERE id_caracteristique = ?')->execute([$id]);
        $req = $pdo->prepare('INSERT IGNORE INTO categorie_caracteristique (id_categorie, id_caracteristique) VALUES (?, ?)');
        foreach ($categories as $idCategorie) {
            $req->execute([(int) $idCategorie, $id]);
        }
    }

    public static function changerObligatoire(int $id, bool $obligatoire): void
    {
        Database::get()->prepare('UPDATE caracteristique SET obligatoire = ? WHERE id = ?')->execute([$obligatoire ? 1 : 0, $id]);
    }

    public static function ajouterOption(int $id, string $valeur): void
    {
        Database::get()->prepare(
            'INSERT IGNORE INTO caracteristique_option (id_caracteristique, valeur, ordre)
             SELECT ?, ?, COALESCE(MAX(ordre), 0) + 1 FROM caracteristique_option WHERE id_caracteristique = ?'
        )->execute([$id, $valeur, $id]);
    }

    // Supprimer une option efface aussi ce choix dans les annonces qui l'avaient (ON DELETE CASCADE)
    public static function supprimerOption(int $idOption): void
    {
        Database::get()->prepare('DELETE FROM caracteristique_option WHERE id = ?')->execute([$idOption]);
    }

    public static function supprimer(int $id): void
    {
        Database::get()->prepare('DELETE FROM caracteristique WHERE id = ?')->execute([$id]);
    }

    public static function nomExiste(string $nom): bool
    {
        $req = Database::get()->prepare('SELECT COUNT(*) FROM caracteristique WHERE nom = ?');
        $req->execute([$nom]);
        return $req->fetchColumn() > 0;
    }
}
