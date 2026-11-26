<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tous les liens relatifs (css/…, img/…, annonce/12…) partent du dossier du site, même sur annonce/12-velo -->
    <base href="<?= e(base_chemin()) ?>">
    <title><?= $nbNotifs > 0 ? '(' . $nbNotifs . ') ' : '' ?><?= e($titrePage) ?></title>
    <meta name="description" content="<?= e($meta['description']) ?>">
    <meta name="robots" content="<?= e($meta['robots']) ?>">
    <?php if ($meta['canonical']): ?>
        <!-- L'adresse « officielle » de la page : évite que Google voie des doublons (?page=…, filtres…) -->
        <link rel="canonical" href="<?= e($meta['canonical']) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="#1a9e6c">

    <!-- Aperçu quand le lien est partagé sur les réseaux / messageries -->
    <meta property="og:site_name" content="<?= e(NOM_SITE) ?>">
    <meta property="og:title" content="<?= e($titrePage) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:image" content="<?= e($meta['image']) ?>">
    <meta property="og:type" content="<?= e($meta['type']) ?>">
    <meta property="og:locale" content="fr_FR">
    <?php if ($meta['canonical']): ?><meta property="og:url" content="<?= e($meta['canonical']) ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">

    <?php foreach ($meta['jsonld'] as $donnees): ?>
        <!-- Données structurées (schema.org) : ce ne sont que des données, aucun code n'est exécuté -->
        <script type="application/ld+json"><?= json_encode($donnees, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <?php endforeach; ?>

    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
    <!-- ?v=… change quand le fichier change : le navigateur garde le CSS en cache, mais recharge la nouvelle version -->
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/../../../public/css/style.css') ?>">
</head>
<body>
<header class="entete">
    <div class="conteneur entete-contenu">
        <a href="<?= url('accueil') ?>" class="logo" aria-label="<?= e(NOM_SITE) ?> — accueil">
            <img src="img/logo.svg" alt="" width="36" height="36">
            <span class="logo-texte"><?= logo_texte() ?></span>
        </a>
        <?php $moi = utilisateur_connecte(); ?>

        <!-- Sur téléphone : bouton « Menu » qui ouvre la liste des liens.
             Case à cocher + étiquette : marche même sans JavaScript (js/site.js ajoute juste la fermeture au clic dehors). -->
        <input type="checkbox" id="menu-bascule" class="menu-bascule" aria-hidden="true" tabindex="-1">
        <label for="menu-bascule" class="bouton-menu" aria-label="Ouvrir ou fermer le menu" aria-controls="menu-principal" tabindex="0">
            <span class="icone-menu" aria-hidden="true"></span> Menu
            <?php if ($moi): ?>
                <span class="badge-notifs" data-total="<?= $nbNotifs ?>" <?= $nbNotifs ? '' : 'hidden' ?>><?= $nbNotifs > 99 ? '99+' : $nbNotifs ?></span>
            <?php endif; ?>
        </label>

        <nav class="menu" id="menu-principal" aria-label="Menu principal">
            <a href="<?= url('objet/recherche') ?>">🔍 Chercher</a>
            <?php if ($moi): ?>
                <a href="<?= url('objet/creer') ?>" class="bouton bouton-petit">+ Publier</a>
                <a href="<?= url('proposition/mes') ?>" class="lien-notifs">💬 Mes échanges
                    <span id="badge-notifs" class="badge-notifs" data-total="<?= $nbNotifs ?>" <?= $nbNotifs ? '' : 'hidden' ?>><?= $nbNotifs > 99 ? '99+' : $nbNotifs ?></span>
                </a>
                <a href="<?= url('objet/favoris') ?>">❤️ Mes favoris</a>
                <a href="<?= url('utilisateur/profil', ['id' => $moi['id'], 'slug' => $moi['pseudo']]) ?>" class="lien-profil"><?= avatar($moi['pseudo'], $moi['avatar'] ?? null, 30) ?> <span>Mon profil</span></a>
                <a href="<?= url('utilisateur/compte') ?>" class="lien-compte" title="Mon compte : réglages, notifications, mot de passe">⚙️ <span>Mon compte</span></a>
                <?php if (est_admin()): ?>
                    <a href="<?= url('admin/index') ?>" class="lien-admin">🛡️ Admin</a>
                <?php endif; ?>
                <form method="post" action="<?= url('utilisateur/deconnexion') ?>" class="form-inline">
                    <?= csrf_champ() ?>
                    <button type="submit" class="lien-bouton">Déconnexion</button>
                </form>
            <?php else: ?>
                <a href="<?= url('page/concept') ?>">💡 Le concept</a>
                <a href="<?= url('utilisateur/connexion') ?>">Connexion</a>
                <a href="<?= url('utilisateur/inscription') ?>" class="bouton bouton-petit">S'inscrire</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="conteneur">
    <?php foreach (flash_lire() as $f): ?>
        <div class="alerte alerte-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
