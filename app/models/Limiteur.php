<?php
// Anti-spam : limite le nombre d'actions (inscriptions, propositions, messages…) sur une durée.
// Exemple : Limiteur::autoriser('message:12', 30, 10) → 30 messages max en 10 minutes pour l'utilisateur 12
class Limiteur
{
    // Les limites du site, réunies ici pour être faciles à ajuster : [maximum, minutes]
    public const REGLES = [
        'inscription' => [5, 60],    // par adresse IP
        'objet'       => [20, 1440], // annonces publiées par jour
        'proposition' => [15, 60],
        'message'     => [40, 10],
        'signalement' => [10, 1440],
        'mdp_oublie'  => [5, 60],    // par adresse IP
        'mdp_oublie_compte' => [3, 60], // par compte (protège sa boîte mail)
        'renvoi_ip'   => [5, 60],    // « Renvoyer l'email de confirmation »
        'renvoi_compte' => [3, 60],
        'cookies'     => [30, 60],   // preuves de choix des cookies, par adresse IP
    ];

    // Renvoie true si l'action est permise (et la compte), false si la limite est atteinte
    public static function autoriser(string $type, string $qui): bool
    {
        if (self::atteinte($type, $qui)) {
            return false;
        }
        self::compter($type, $qui);
        return true;
    }

    // Vérifie sans compter (utile quand on ne veut compter que les actions réussies, comme les inscriptions)
    public static function atteinte(string $type, string $qui): bool
    {
        [$max, $minutes] = self::REGLES[$type];
        $req = Database::get()->prepare('SELECT COUNT(*) FROM limite_action WHERE cle = ? AND date > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $req->execute([$type . ':' . $qui, $minutes]);
        return $req->fetchColumn() >= $max;
    }

    public static function compter(string $type, string $qui): void
    {
        $pdo = Database::get();
        $pdo->prepare('INSERT INTO limite_action (cle) VALUES (?)')->execute([$type . ':' . $qui]);

        // Petit ménage de temps en temps : on supprime les lignes de plus de 2 jours
        if (random_int(1, 100) === 1) {
            $pdo->exec('DELETE FROM limite_action WHERE date < DATE_SUB(NOW(), INTERVAL 2 DAY)');
        }
    }
}
