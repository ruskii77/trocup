<?php
class Utilisateur
{
    public static function trouverParId(int $id): ?array
    {
        $req = Database::get()->prepare('SELECT * FROM utilisateur WHERE id = ?');
        $req->execute([$id]);
        return $req->fetch() ?: null;
    }

    public static function trouverParEmail(string $email): ?array
    {
        $req = Database::get()->prepare('SELECT * FROM utilisateur WHERE email = ?');
        $req->execute([$email]);
        return $req->fetch() ?: null;
    }

    public static function pseudoExiste(string $pseudo): bool
    {
        $req = Database::get()->prepare('SELECT COUNT(*) FROM utilisateur WHERE pseudo = ?');
        $req->execute([$pseudo]);
        return $req->fetchColumn() > 0;
    }

    public static function creer(string $pseudo, string $email, string $motDePasse, string $ville, string $codePostal, string $departement): int
    {
        $req = Database::get()->prepare(
            'INSERT INTO utilisateur (pseudo, email, mot_de_passe_hash, ville, code_postal, departement) VALUES (?, ?, ?, ?, ?, ?)'
        );
        // password_hash : le mot de passe n'est JAMAIS stocké en clair
        $req->execute([$pseudo, $email, password_hash($motDePasse, PASSWORD_DEFAULT), $ville, $codePostal, $departement]);
        return (int) Database::get()->lastInsertId();
    }

    public static function marquerEmailVerifie(int $id): void
    {
        Database::get()->prepare('UPDATE utilisateur SET email_verifie = 1 WHERE id = ?')->execute([$id]);
    }

    public static function changerMotDePasse(int $id, string $motDePasse): void
    {
        Database::get()->prepare('UPDATE utilisateur SET mot_de_passe_hash = ? WHERE id = ?')
            ->execute([password_hash($motDePasse, PASSWORD_DEFAULT), $id]);
    }

    public static function modifierLocalisation(int $id, string $ville, string $codePostal, string $departement): void
    {
        Database::get()->prepare('UPDATE utilisateur SET ville = ?, code_postal = ?, departement = ? WHERE id = ?')
            ->execute([$ville, $codePostal, $departement, $id]);
    }

    // Réseaux sociaux proposés : nom affiché, règle pour le nom d'utilisateur, adresse du profil
    public const RESEAUX = [
        'instagram' => ['Instagram', '/^[A-Za-z0-9._]{1,30}$/',   'https://www.instagram.com/%s'],
        'tiktok'    => ['TikTok',    '/^[A-Za-z0-9._]{2,24}$/',   'https://www.tiktok.com/@%s'],
        'snapchat'  => ['Snapchat',  '/^[A-Za-z][A-Za-z0-9._-]{2,14}$/', 'https://www.snapchat.com/add/%s'],
        'youtube'   => ['YouTube',   '/^[A-Za-z0-9._-]{3,30}$/',  'https://www.youtube.com/@%s'],
    ];

    // Nettoie ce que l'utilisateur a tapé : « @vlad », « instagram.com/vlad/ » ou « vlad » → « vlad »
    public static function nettoyerReseau(string $saisie): string
    {
        $saisie = trim($saisie);
        if (str_contains($saisie, '/')) {
            $morceaux = array_values(array_filter(explode('/', parse_url($saisie, PHP_URL_PATH) ?? $saisie)));
            $saisie = end($morceaux) ?: '';
        }
        return ltrim($saisie, '@');
    }

    public static function modifierProfilPublic(int $id, ?string $bio, array $reseaux): void
    {
        Database::get()->prepare(
            'UPDATE utilisateur SET bio = ?, instagram = ?, tiktok = ?, snapchat = ?, youtube = ? WHERE id = ?'
        )->execute([$bio, $reseaux['instagram'], $reseaux['tiktok'], $reseaux['snapchat'], $reseaux['youtube'], $id]);
    }

    public static function changerAvatar(int $id, ?string $avatar): void
    {
        Database::get()->prepare('UPDATE utilisateur SET avatar = ? WHERE id = ?')->execute([$avatar, $id]);
    }

    // Chiffres affichés sur le profil
    public static function statistiques(int $id): array
    {
        $req = Database::get()->prepare(
            "SELECT COUNT(*) FROM proposition WHERE statut = 'acceptee' AND (id_proposeur = ? OR id_destinataire = ?)"
        );
        $req->execute([$id, $id]);
        return ['echanges' => (int) $req->fetchColumn()];
    }

    // Les membres qui ont fait le plus d'échanges (pour « Les plus belles chaînes »)
    public static function meilleursEchangeurs(int $nombre): array
    {
        $req = Database::get()->prepare(
            "SELECT u.id, u.pseudo, u.avatar, u.ville, u.departement, COUNT(*) AS nb
             FROM utilisateur u
             JOIN proposition p ON p.statut = 'acceptee' AND (p.id_proposeur = u.id OR p.id_destinataire = u.id)
             WHERE u.est_banni = 0 AND u.supprime = 0
             GROUP BY u.id, u.pseudo, u.avatar, u.ville, u.departement
             ORDER BY nb DESC, MAX(p.date_reponse) DESC
             LIMIT " . (int) $nombre
        );
        $req->execute();
        return $req->fetchAll();
    }

    public static function compter(): int
    {
        return (int) Database::get()->query('SELECT COUNT(*) FROM utilisateur WHERE est_banni = 0 AND email_verifie = 1 AND supprime = 0')->fetchColumn();
    }

    public static function changerNotifEmail(int $id, bool $actif): void
    {
        Database::get()->prepare('UPDATE utilisateur SET notif_email = ? WHERE id = ?')->execute([$actif ? 1 : 0, $id]);
    }

    // RGPD — droit à l'effacement.
    // On ne supprime pas la ligne (les chaînes d'échanges des AUTRES membres seraient cassées) :
    // on efface toutes les infos personnelles et on remplace le pseudo par « membre supprimé ».
    public static function anonymiser(int $id): void
    {
        $u = self::trouverParId($id);
        $pdo = Database::get();
        foreach (Objet::disponiblesDe($id) as $o) {
            Objet::retirer($o['id']);
        }
        Image::supprimer($u['avatar']);
        $pdo->prepare(
            "UPDATE utilisateur SET pseudo = ?, email = ?, mot_de_passe_hash = ?, ville = '—', code_postal = '00000',
                    departement = '', bio = NULL, avatar = NULL, instagram = NULL, tiktok = NULL, snapchat = NULL,
                    youtube = NULL, email_verifie = 0, notif_email = 0, supprime = 1
             WHERE id = ?"
        )->execute(['membre_supprime_' . $id, 'supprime-' . $id . '@invalid', password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $id]);
        foreach (['jeton', 'favori', 'notif_email'] as $table) {
            $pdo->prepare("DELETE FROM $table WHERE id_utilisateur = ?")->execute([$id]);
        }
        $pdo->prepare('DELETE FROM blocage WHERE id_bloqueur = ? OR id_bloque = ?')->execute([$id, $id]);
        $pdo->prepare('DELETE FROM tentative_connexion WHERE email = ?')->execute([$u['email']]);
    }

    // RGPD — droit d'accès et de portabilité : toutes les données du membre, lisibles par une machine
    public static function exporter(int $id): array
    {
        $pdo = Database::get();
        $u = self::trouverParId($id);
        unset($u['mot_de_passe_hash']);
        $requete = function (string $sql, array $params) use ($pdo) {
            $req = $pdo->prepare($sql);
            $req->execute($params);
            return $req->fetchAll();
        };
        return [
            'export_genere_le' => date('c'),
            'site'             => NOM_SITE,
            'compte'           => $u,
            'objets'           => $requete('SELECT id, titre, description, etat, valeur_estimee, statut, date_publication FROM objet WHERE id_utilisateur = ?', [$id]),
            'propositions'     => $requete('SELECT id, id_objet_propose, id_objet_demande, id_proposeur, id_destinataire, statut, date_proposition, date_reponse FROM proposition WHERE id_proposeur = ? OR id_destinataire = ?', [$id, $id]),
            'messages_envoyes' => $requete('SELECT id_proposition, contenu, date_envoi FROM message WHERE id_expediteur = ?', [$id]),
            'avis_donnes'      => $requete('SELECT id_proposition, note, commentaire, date FROM avis WHERE id_auteur = ?', [$id]),
            'avis_recus'       => $requete('SELECT id_proposition, note, commentaire, date FROM avis WHERE id_destinataire = ?', [$id]),
            'favoris'          => $requete('SELECT id_objet, date FROM favori WHERE id_utilisateur = ?', [$id]),
            'signalements'     => $requete('SELECT id_objet, motif, date FROM signalement WHERE id_utilisateur = ?', [$id]),
        ];
    }

    // Règles du mot de passe : renvoie un message d'erreur, ou null si le mot de passe est correct
    public static function erreurMotDePasse(string $mdp, string $confirmation): ?string
    {
        if (strlen($mdp) < 8 || !preg_match('/[A-Za-z]/', $mdp) || !preg_match('/\d/', $mdp)) {
            return 'Le mot de passe doit faire au moins 8 caractères, avec au moins une lettre et un chiffre.';
        }
        if ($mdp !== $confirmation) {
            return 'Les deux mots de passe ne correspondent pas.';
        }
        return null;
    }

    public static function definirBannissement(int $id, bool $banni): void
    {
        // On ne peut pas bannir un admin
        $req = Database::get()->prepare("UPDATE utilisateur SET est_banni = ? WHERE id = ? AND role <> 'admin'");
        $req->execute([$banni ? 1 : 0, $id]);
    }

    public static function supprimer(int $id): void
    {
        Database::get()->prepare('DELETE FROM utilisateur WHERE id = ?')->execute([$id]);
    }

    public static function tous(): array
    {
        return Database::get()->query(
            'SELECT u.*, (SELECT COUNT(*) FROM objet o WHERE o.id_utilisateur = u.id AND o.statut = \'disponible\') AS nb_objets
             FROM utilisateur u ORDER BY u.date_inscription DESC'
        )->fetchAll();
    }

    // Moyenne des notes reçues et nombre d'avis
    public static function noteMoyenne(int $id): array
    {
        $req = Database::get()->prepare('SELECT AVG(note) AS moyenne, COUNT(*) AS nombre FROM avis WHERE id_destinataire = ?');
        $req->execute([$id]);
        return $req->fetch();
    }
}
