<?php
// Protection contre les attaques « force brute » (un robot qui essaie des milliers de mots de passe)
class TentativeConnexion
{
    // Bloqué si CE compte a trop d'échecs récents, ou si CETTE adresse IP en a beaucoup
    // (une IP peut être partagée par plusieurs personnes, d'où une limite plus haute)
    public static function estBloque(string $email, string $ip): bool
    {
        $pdo = Database::get();
        $req = $pdo->prepare('SELECT COUNT(*) FROM tentative_connexion WHERE email = ? AND date > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $req->execute([$email, DUREE_BLOCAGE_MINUTES]);
        if ($req->fetchColumn() >= MAX_TENTATIVES_CONNEXION) {
            return true;
        }
        $req = $pdo->prepare('SELECT COUNT(*) FROM tentative_connexion WHERE ip = ? AND date > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $req->execute([$ip, DUREE_BLOCAGE_MINUTES]);
        return $req->fetchColumn() >= MAX_TENTATIVES_IP;
    }

    public static function enregistrerEchec(string $email, string $ip): void
    {
        Database::get()->prepare('INSERT INTO tentative_connexion (email, ip) VALUES (?, ?)')->execute([$email, $ip]);
    }

    // Après une connexion réussie, on remet le compteur à zéro pour cet email
    public static function effacer(string $email): void
    {
        Database::get()->prepare('DELETE FROM tentative_connexion WHERE email = ?')->execute([$email]);
    }
}
