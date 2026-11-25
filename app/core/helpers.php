<?php
// Petites fonctions utilisées partout dans le site.

// Échappe un texte avant de l'afficher (protection contre les failles XSS)
function e($texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES, 'UTF-8');
}

// Construit un lien vers une page : url('objet/voir', ['id' => 3]) → index.php?route=objet/voir&id=3
function url(string $route, array $params = []): string
{
    return 'index.php?' . http_build_query(['route' => $route] + $params);
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
