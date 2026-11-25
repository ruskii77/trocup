<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nbNotifs > 0 ? '(' . $nbNotifs . ') ' : '' ?><?= e($titrePage) ?></title>
    <meta name="description" content="<?= e($meta['description']) ?>">
    <meta name="theme-color" content="#1a9e6c">

    <!-- Aperçu quand le lien est partagé sur les réseaux / messageries -->
    <meta property="og:site_name" content="<?= e(NOM_SITE) ?>">
    <meta property="og:title" content="<?= e($titrePage) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:image" content="<?= e($meta['image']) ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">

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
        <nav class="menu">
            <a href="<?= url('objet/recherche') ?>">🔍 Chercher</a>
            <?php if ($moi = utilisateur_connecte()): ?>
                <a href="<?= url('objet/creer') ?>" class="bouton bouton-petit">+ Publier</a>
                <a href="<?= url('proposition/mes') ?>" class="lien-notifs">Mes échanges
                    <span id="badge-notifs" class="badge-notifs" data-total="<?= $nbNotifs ?>" <?= $nbNotifs ? '' : 'hidden' ?>><?= $nbNotifs > 99 ? '99+' : $nbNotifs ?></span>
                </a>
                <a href="<?= url('objet/favoris') ?>" title="Mes favoris">❤️</a>
                <a href="<?= url('utilisateur/profil', ['id' => $moi['id']]) ?>" class="lien-profil"><?= avatar($moi['pseudo'], $moi['avatar'] ?? null, 30) ?> <span>Mon profil</span></a>
                <a href="<?= url('utilisateur/compte') ?>" title="Mon compte">⚙️</a>
                <?php if (est_admin()): ?>
                    <a href="<?= url('admin/index') ?>" class="lien-admin">Admin</a>
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
