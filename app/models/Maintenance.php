<?php
// =====================================================
//  Nettoyage automatique des données (RGPD : ne pas garder les données plus longtemps que nécessaire)
//  Lancé automatiquement au plus une fois par jour, ou à la main depuis la page Admin.
//  Les durées ci-dessous sont celles annoncées dans la politique de confidentialité :
//  si tu les changes ici, change-les aussi là-bas.
// =====================================================
class Maintenance
{
    public const DUREES = [
        'tentatives_connexion'  => '2 jours',
        'compte_non_confirme'   => '7 jours',
        'compte_inactif'        => '3 ans',
        'preuve_cookies'        => '13 mois',
    ];

    private const FICHIER = __DIR__ . '/../../storage/derniere_maintenance.txt';

    // À appeler à chaque page : ne fait quelque chose qu'une fois toutes les 24 h
    public static function siNecessaire(): void
    {
        $derniere = is_file(self::FICHIER) ? (int) file_get_contents(self::FICHIER) : 0;
        if (time() - $derniere > 24 * 3600) {
            @file_put_contents(self::FICHIER, (string) time());
            self::nettoyer();
        }
    }

    public static function derniereExecution(): ?int
    {
        return is_file(self::FICHIER) ? (int) file_get_contents(self::FICHIER) : null;
    }

    // Renvoie le nombre d'éléments supprimés par catégorie
    public static function nettoyer(): array
    {
        $pdo = Database::get();
        $bilan = [];

        $bilan['tentatives de connexion'] = $pdo->exec('DELETE FROM tentative_connexion WHERE date < DATE_SUB(NOW(), INTERVAL 2 DAY)');
        $bilan['limites anti-spam']      = $pdo->exec('DELETE FROM limite_action WHERE date < DATE_SUB(NOW(), INTERVAL 2 DAY)');
        $bilan['liens email expirés']    = $pdo->exec('DELETE FROM jeton WHERE expire_le < NOW()');
        $bilan['preuves de cookies']     = $pdo->exec('DELETE FROM consentement_cookie WHERE date < DATE_SUB(NOW(), INTERVAL 13 MONTH)');

        // Comptes jamais confirmés par email après 7 jours : supprimés (ils n'ont rien pu publier)
        $bilan['comptes non confirmés'] = $pdo->exec(
            "DELETE FROM utilisateur WHERE email_verifie = 0 AND supprime = 0 AND role = 'membre'
             AND date_inscription < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );

        // Comptes sans connexion depuis 3 ans : anonymisés (comme une suppression demandée par le membre)
        $inactifs = $pdo->query(
            "SELECT id FROM utilisateur WHERE supprime = 0 AND role = 'membre'
             AND COALESCE(derniere_connexion, date_inscription) < DATE_SUB(NOW(), INTERVAL 3 YEAR)"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($inactifs as $id) {
            Utilisateur::anonymiser((int) $id);
        }
        $bilan['comptes inactifs anonymisés'] = count($inactifs);

        @file_put_contents(self::FICHIER, (string) time());
        return $bilan;
    }
}
