<?php
// Jetons à usage unique envoyés par email (confirmation de compte, mot de passe oublié)
class Jeton
{
    // Crée un jeton et renvoie sa valeur EN CLAIR (à mettre dans le lien de l'email).
    // En base, on ne garde que son empreinte SHA-256.
    public static function creer(int $idUtilisateur, string $type, int $dureeMinutes): string
    {
        $pdo = Database::get();
        // Un seul jeton valide à la fois par type : les anciens liens deviennent inutilisables
        $pdo->prepare('UPDATE jeton SET utilise = 1 WHERE id_utilisateur = ? AND type = ? AND utilise = 0')
            ->execute([$idUtilisateur, $type]);

        $jeton = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO jeton (id_utilisateur, type, jeton_hash, expire_le) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))')
            ->execute([$idUtilisateur, $type, hash('sha256', $jeton), $dureeMinutes]);
        return $jeton;
    }

    // Renvoie le jeton s'il est valide (bon type, pas utilisé, pas expiré), sinon null
    public static function trouverValide(string $jeton, string $type): ?array
    {
        $req = Database::get()->prepare(
            'SELECT * FROM jeton WHERE jeton_hash = ? AND type = ? AND utilise = 0 AND expire_le > NOW()'
        );
        $req->execute([hash('sha256', $jeton), $type]);
        return $req->fetch() ?: null;
    }

    public static function marquerUtilise(int $id): void
    {
        Database::get()->prepare('UPDATE jeton SET utilise = 1 WHERE id = ?')->execute([$id]);
    }
}
