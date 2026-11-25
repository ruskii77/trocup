<?php
// =====================================================
//  Gestion des cookies et du consentement (règles CNIL)
//
//  - Les cookies « nécessaires » (connexion, mémorisation du choix) n'ont pas besoin de consentement.
//  - Tous les autres (statistiques, vidéos, réseaux sociaux, publicité…) ne peuvent être déposés
//    QU'APRÈS un « oui » explicite. Ne rien répondre = refuser.
//  - Refuser doit être aussi simple qu'accepter, et le choix est retenu 6 mois.
// =====================================================
class Cookies
{
    public const NOM = 'visota_consentement';
    public const DUREE = 182 * 24 * 3600; // 6 mois, durée recommandée par la CNIL

    // Change ce numéro si tu ajoutes un nouveau service : tout le monde sera re-questionné
    public const VERSION = 1;

    // Les catégories proposées dans le bandeau.
    // 'services' = la liste de ce que la catégorie contient VRAIMENT (à tenir à jour honnêtement).
    public static function categories(): array
    {
        return [
            'necessaires' => [
                'nom'         => 'Nécessaires',
                'description' => 'Indispensables au fonctionnement du site : rester connecté, sécuriser les formulaires, mémoriser ton choix sur les cookies. Ils ne peuvent pas être désactivés.',
                'obligatoire' => true,
                'services'    => [
                    ['visota_session', 'Garder ta connexion et protéger les formulaires', 'Jusqu\'à la fermeture du navigateur (déconnexion après 2 h d\'inactivité)'],
                    [self::NOM, 'Mémoriser ton choix sur les cookies', '6 mois'],
                ],
            ],
            'mesure' => [
                'nom'         => 'Mesure d\'audience',
                'description' => 'Compter les visites et voir quelles pages sont les plus consultées, pour améliorer le site.',
                'obligatoire' => false,
                'services'    => [], // aucun outil de statistiques n'est installé pour l'instant
            ],
            'externes' => [
                'nom'         => 'Contenus externes et réseaux sociaux',
                'description' => 'Afficher des vidéos (YouTube, TikTok…) ou des contenus de réseaux sociaux intégrés dans les pages. Ces services peuvent déposer leurs propres cookies.',
                'obligatoire' => false,
                'services'    => [], // aucun contenu externe intégré pour l'instant
            ],
        ];
    }

    // Le choix enregistré du visiteur, ou null s'il n'a pas encore répondu (ou si la version a changé)
    public static function choix(): ?array
    {
        $brut = $_COOKIE[self::NOM] ?? null;
        $choix = $brut ? json_decode($brut, true) : null;
        if (!is_array($choix) || ($choix['v'] ?? 0) !== self::VERSION) {
            return null;
        }
        return $choix;
    }

    // Côté PHP : « ai-je le droit de charger ce service ? »  Exemple : if (Cookies::accepte('mesure')) { … }
    public static function accepte(string $categorie): bool
    {
        if (self::categories()[$categorie]['obligatoire'] ?? false) {
            return true;
        }
        return (bool) (self::choix()[$categorie] ?? false);
    }
}
