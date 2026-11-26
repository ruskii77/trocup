<?php
// Petites fonctions utilisées partout dans le site.

// Échappe un texte avant de l'afficher (protection contre les failles XSS)
function e($texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

// Construit un lien vers une page, relatif au dossier du site (voir <base> dans header.php).
//   url('objet/voir', ['id' => 3, 'slug' => 'T-shirt Nike']) → annonce/3-t-shirt-nike     (URL « propre », bonne pour Google)
//                                                          ou → index.php?route=objet/voir&id=3  (si la réécriture d'URL n'est pas active)
function url(string $route, array $params = []): string
{
    if (urls_propres()) {
        $propre = UrlPropre::chemin($route, $params);
        if ($propre !== null) {
            return $propre;
        }
    }
    unset($params['slug']); // le slug ne sert qu'aux URL propres
    return 'index.php?' . http_build_query(['route' => $route] + $params);
}

// La même adresse en version complète (https://visota.fr/annonce/3-…) : emails, balise canonical, sitemap
function url_absolue(string $route, array $params = []): string
{
    return URL_SITE . '/' . ltrim(url($route, $params), './');
}

// Dossier du site vu depuis le navigateur : « / » en ligne, « /projet-echange/public/ » sous MAMP
function base_chemin(): string
{
    static $base = null;
    return $base ??= rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/') . '/';
}

// Vrai si Apache réécrit les adresses (règle de public/.htaccess, module mod_rewrite actif)
function urls_propres(): bool
{
    return !empty($_SERVER['URLS_PROPRES']) || !empty($_SERVER['REDIRECT_URLS_PROPRES']);
}

// « T-shirt Nike vintage ! » → « t-shirt-nike-vintage » (pour les adresses)
function slug(string $texte, int $longueur = 60): string
{
    $texte = strtr(mb_strtolower($texte, 'UTF-8'), [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
        'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae', '&' => ' et ',
    ]);
    $texte = trim(preg_replace('/[^a-z0-9]+/', '-', $texte), '-');
    if (strlen($texte) > $longueur) {
        // on coupe proprement entre deux mots
        $texte = substr($texte, 0, $longueur);
        $texte = strpos($texte, '-') !== false ? substr($texte, 0, strrpos($texte, '-')) : $texte;
    }
    return $texte;
}

// ---------- Jeton CSRF (protège les formulaires contre les envois piégés) ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_champ(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verifier(): void
{
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(400);
        // Envoi plus gros que la limite de PHP (post_max_size) : PHP jette tout le formulaire, jeton compris
        if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            exit('Tes photos sont trop lourdes au total (maximum ' . ini_get('post_max_size') . ' par envoi). '
                . 'Reviens en arrière et réessaie avec moins de photos ou des photos plus légères.');
        }
        exit('Formulaire invalide ou expiré. Recharge la page et réessaie.');
    }
}

// ---------- Messages flash (affichés une seule fois, après une redirection) ----------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_lire(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---------- Utilisateur connecté ----------
function utilisateur_connecte(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

// Petite empreinte du mot de passe (chiffré) gardée dans la session : si le mot de passe change, elle ne correspond
// plus et les sessions ouvertes ailleurs sont fermées. Ce n'est pas le mot de passe, ni même son empreinte complète.
function empreinte_session(string $hashMotDePasse): string
{
    return substr(hash_hmac('sha256', $hashMotDePasse, CLE_SECRETE), 0, 16);
}

function est_admin(): bool
{
    return (utilisateur_connecte()['role'] ?? '') === 'admin';
}

// ---------- Affichage ----------
function libelle_etat(string $etat): string
{
    return ['neuf' => 'Neuf', 'tres_bon' => 'Très bon état', 'bon' => 'Bon état', 'use' => 'Usé'][$etat] ?? $etat;
}

function libelle_statut(string $statut): string
{
    return [
        'en_attente' => 'En attente', 'acceptee' => 'Acceptée',
        'refusee' => 'Refusée', 'annulee' => 'Annulée', 'traite' => 'Traité',
    ][$statut] ?? $statut;
}

function prix(float $valeur): string
{
    return number_format($valeur, 0, ',', ' ') . ' €';
}

// Adresse d'un fichier CSS ou JS avec sa date de modification : 'js/site.js' → 'js/site.js?v=1732650000'.
// Les navigateurs gardent ces fichiers en mémoire une semaine (voir public/.htaccess) : quand le fichier change,
// le numéro change aussi, et tous les téléphones téléchargent la nouvelle version dès la page suivante.
function fichier_version(string $chemin): string
{
    $fichier = __DIR__ . '/../../public/' . $chemin;
    return e($chemin) . (is_file($fichier) ? '?v=' . filemtime($fichier) : '');
}

function photo_objet(?string $photo): string
{
    return $photo ? 'uploads/' . e($photo) : 'img/pas-de-photo.svg';
}

// Le nom du site pour le logo, avec la fin en couleur (réglage NOM_ACCENT)
function logo_texte(): string
{
    $nom = NOM_SITE;
    if (NOM_ACCENT !== '' && str_ends_with($nom, NOM_ACCENT)) {
        return e(substr($nom, 0, -strlen(NOM_ACCENT))) . '<span>' . e(NOM_ACCENT) . '</span>';
    }
    return e($nom);
}

// Nom du site en minuscules sans accents ni espaces, pour les noms de fichiers : 'Visota' → 'visota'
function slug_site(): string
{
    return strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', NOM_SITE)));
}

// Photo de profil ronde, ou à défaut la première lettre du pseudo sur une couleur qui lui est propre
function avatar(?string $pseudo, ?string $fichier, int $taille = 40): string
{
    $style = 'width:' . $taille . 'px;height:' . $taille . 'px;font-size:' . round($taille * .45) . 'px';
    if ($fichier) {
        return '<img src="uploads/' . e($fichier) . '" alt="" class="avatar-rond" style="' . $style . '" loading="lazy">';
    }
    $couleurs = ['#1f9d6b', '#ff8a3d', '#5b6cff', '#e0527a', '#14a3b8', '#9b59b6', '#d4a017', '#2d6a4f'];
    $couleur = $couleurs[crc32((string) $pseudo) % count($couleurs)];
    return '<span class="avatar-rond avatar-lettre" style="' . $style . ';background:' . $couleur . '">'
         . e(mb_strtoupper(mb_substr((string) $pseudo, 0, 1))) . '</span>';
}

// Liste des réseaux renseignés par un utilisateur : [['nom' => 'Instagram', 'pseudo' => 'vlad', 'url' => '…'], …]
function reseaux_de(array $u): array
{
    $liste = [];
    foreach (Utilisateur::RESEAUX as $cle => [$nom, , $modele]) {
        if (!empty($u[$cle])) {
            $liste[] = ['cle' => $cle, 'nom' => $nom, 'pseudo' => $u[$cle], 'url' => sprintf($modele, rawurlencode($u[$cle]))];
        }
    }
    return $liste;
}

// Badges gagnés par un membre (calculés à partir de ses chiffres, rien à stocker)
function badges(array $stats, array $chaine, array $note, array $profil): array
{
    $nb = $stats['echanges'];
    $mult = $chaine && $chaine[0]['donne_valeur'] > 0 ? end($chaine)['recu_valeur'] / $chaine[0]['donne_valeur'] : 0;
    $tous = [
        ['🌱', 'Premier échange', 'A réalisé son premier échange', $nb >= 1],
        ['🔗', 'Chaîne de 5', '5 échanges réalisés', $nb >= 5],
        ['🏅', 'Chaîne de 10', '10 échanges réalisés', $nb >= 10],
        ['🚀', '×10', 'A multiplié la valeur de départ par 10', $mult >= 10],
        ['💎', '×100', 'A multiplié la valeur de départ par 100', $mult >= 100],
        ['⭐', 'Membre fiable', 'Au moins 3 avis avec une moyenne de 4,5 ou plus', $note['nombre'] >= 3 && $note['moyenne'] >= 4.5],
        ['📸', 'Profil complet', 'Photo, bio et au moins un réseau', $profil['avatar'] && $profil['bio'] && reseaux_de($profil)],
    ];
    return array_values(array_filter($tous, fn($b) => $b[3]));
}

function date_fr(string $date): string
{
    return date('d/m/Y à H:i', strtotime($date));
}

// ---------- Référencement : données structurées ----------
// Fil d'Ariane lu par Google (Accueil › Vêtements › T-shirts) : $etapes = [['Vêtements', url absolue], …]
function fil_ariane_jsonld(array $etapes): array
{
    $elements = [];
    foreach (array_merge([[NOM_SITE, url_absolue('accueil')]], $etapes) as $i => [$nom, $adresse]) {
        $elements[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $nom, 'item' => $adresse];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elements];
}

// Coupe un texte proprement pour une meta description (environ 155 caractères, sans couper un mot)
function resume(string $texte, int $longueur = 155): string
{
    $texte = trim(preg_replace('/\s+/', ' ', $texte));
    if (mb_strlen($texte) <= $longueur) {
        return $texte;
    }
    $coupe = mb_substr($texte, 0, $longueur);
    $espace = mb_strrpos($coupe, ' ');
    return rtrim(mb_substr($coupe, 0, $espace ?: $longueur), ' ,.;:') . '…';
}

// « Vêtements » → « de vêtements » ; « Électronique » → « d'électronique » (titres du type « Troc de vêtements »)
function de_categorie(string $nom): string
{
    if ($nom === 'Autre') {
        return "d'objets divers";
    }
    $minuscule = mb_strtolower(mb_substr($nom, 0, 1)) . mb_substr($nom, 1);
    return preg_match('/^[aeiouyhéèêàâîôûœ]/iu', $minuscule) ? "d'" . $minuscule : 'de ' . $minuscule;
}
