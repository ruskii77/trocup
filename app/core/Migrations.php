<?php
// =====================================================
//  Système de MIGRATIONS : mettre à jour la base sans perdre les données
//
//  Chaque changement de structure de la base est un fichier numéroté dans sql/migrations/ :
//     001_base.sql, 002_notifications.sql, 003_...
//  La table `migration` retient ceux qui ont déjà été appliqués.
//  À chaque mise à jour du site, on applique seulement les NOUVEAUX fichiers.
//  → Les comptes, objets, messages… restent intacts.
// =====================================================
class Migrations
{
    private const DOSSIER = __DIR__ . '/../../sql/migrations/';

    public static function tableExiste(string $nom): bool
    {
        $req = Database::get()->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $req->execute([$nom]);
        return $req->fetchColumn() > 0;
    }

    // Base toute neuve (aucune table du site) : c'est une installation
    public static function baseVide(): bool
    {
        return !self::tableExiste('utilisateur');
    }

    // Noms des fichiers déjà appliqués
    public static function appliquees(): array
    {
        if (!self::tableExiste('migration')) {
            return [];
        }
        return Database::get()->query('SELECT nom FROM migration ORDER BY nom')->fetchAll(PDO::FETCH_COLUMN);
    }

    // Fichiers de sql/migrations/ pas encore appliqués, dans l'ordre des numéros
    public static function enAttente(): array
    {
        $fichiers = array_map('basename', glob(self::DOSSIER . '*.sql') ?: []);
        sort($fichiers);
        return array_values(array_diff($fichiers, self::appliquees()));
    }

    // Applique toutes les migrations en attente. Renvoie la liste des fichiers appliqués.
    public static function appliquer(): array
    {
        $pdo = Database::get();
        $pdo->exec('CREATE TABLE IF NOT EXISTS migration (
                        nom  VARCHAR(255) PRIMARY KEY,
                        date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB');

        $faites = [];
        foreach (self::enAttente() as $fichier) {
            foreach (self::decouper(file_get_contents(self::DOSSIER . $fichier)) as $requete) {
                try {
                    $pdo->exec($requete);
                } catch (PDOException $e) {
                    // On s'arrête au premier problème et on dit précisément où
                    throw new RuntimeException("Erreur dans $fichier : " . $e->getMessage() . "\nRequête : " . mb_substr($requete, 0, 300));
                }
            }
            $pdo->prepare('INSERT INTO migration (nom) VALUES (?)')->execute([$fichier]);
            $faites[] = $fichier;
        }
        return $faites;
    }

    // Découpe un fichier SQL en requêtes séparées (une requête se termine par ; en fin de ligne)
    private static function decouper(string $sql): array
    {
        $sql = preg_replace('/--[^\n]*/', '', $sql); // enlève les commentaires
        $requetes = preg_split('/;\s*(\r?\n|$)/', $sql);
        return array_values(array_filter(array_map('trim', $requetes), fn($r) => $r !== ''));
    }
}
