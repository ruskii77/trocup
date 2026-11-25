<?php $moi = utilisateur_connecte(); ?>
<section class="hero-v2">
    <div class="hero-texte">
        <span class="sur-titre">Le troc qui monte ↗</span>
        <h1>Pars d'un trombone.<br><span class="degrade">Vise une voiture.</span></h1>
        <p class="hero-intro">Sur <?= e(NOM_SITE) ?>, tu échanges un objet contre un peu mieux… puis tu recommences avec ce que tu as reçu.
           Échange après échange, ta <strong>chaîne</strong> grandit. Gratuit, entre particuliers, près de chez toi.</p>

        <form method="get" class="barre-recherche">
            <input type="hidden" name="route" value="objet/recherche">
            <input type="search" name="q" placeholder="Que cherches-tu ? (console, vélo…)" aria-label="Rechercher">
            <select name="departement" aria-label="Département">
                <option value="">Toute la France</option>
                <?php foreach (departements() as $code => $nom): ?>
                    <option value="<?= e($code) ?>" <?= $departement === $code ? 'selected' : '' ?>><?= e($code . ' – ' . $nom) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bouton">🔍 Chercher</button>
        </form>

        <div class="hero-actions">
            <?php if ($moi): ?>
                <a href="<?= url('objet/creer') ?>" class="bouton bouton-grand">+ Publier un objet</a>
            <?php else: ?>
                <a href="<?= url('utilisateur/inscription') ?>" class="bouton bouton-grand">Commencer ma chaîne — gratuit</a>
            <?php endif; ?>
            <a href="#comment" class="lien-fort">Comment ça marche ?</a>
        </div>
    </div>

    <!-- Exemple illustré d'une chaîne d'échanges -->
    <div class="hero-visuel" aria-hidden="true">
        <div class="etape-chaine" style="--i:0"><span class="emoji">📎</span><span>Trombone</span><b>1 €</b></div>
        <div class="etape-chaine" style="--i:1"><span class="emoji">🖊️</span><span>Stylo design</span><b>25 €</b></div>
        <div class="etape-chaine" style="--i:2"><span class="emoji">🎧</span><span>Casque audio</span><b>60 €</b></div>
        <div class="etape-chaine" style="--i:3"><span class="emoji">🛴</span><span>Trottinette</span><b>180 €</b></div>
        <div class="etape-chaine etape-finale" style="--i:4"><span class="emoji">🚗</span><span>Et toi, jusqu'où ?</span><b>?</b></div>
    </div>
</section>

<section class="chiffres">
    <div><strong><?= $total ?></strong><span>objet<?= $total > 1 ? 's' : '' ?> à échanger</span></div>
    <div><strong><?= $nbMembres ?></strong><span>membre<?= $nbMembres > 1 ? 's' : '' ?></span></div>
    <div><strong><?= $nbEchanges ?></strong><span>échange<?= $nbEchanges > 1 ? 's' : '' ?> réalisé<?= $nbEchanges > 1 ? 's' : '' ?></span></div>
    <div><strong>0 €</strong><span>pour s'inscrire</span></div>
</section>

<h2>Parcourir par catégorie</h2>
<div class="categories">
    <?php
    $icones = ['Électronique' => '📱', 'Jeux vidéo' => '🎮', 'Vêtements' => '👕', 'Maison' => '🛋️',
               'Sport' => '⚽', 'Livres' => '📚', 'Véhicules' => '🚲', 'Autre' => '📦'];
    foreach ($categories as $c): ?>
        <a href="<?= url('objet/recherche', ['categorie' => $c['id']]) ?>" class="tuile-categorie">
            <span class="tuile-icone"><?= $icones[$c['nom']] ?? '📦' ?></span>
            <strong><?= e($c['nom']) ?></strong>
            <span class="petit"><?= (int) $c['nombre'] ?> objet<?= $c['nombre'] > 1 ? 's' : '' ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($meilleurs): ?>
    <h2>🏆 Les plus belles chaînes</h2>
    <div class="podium">
        <?php foreach ($meilleurs as $rang => $m): ?>
            <a href="<?= url('utilisateur/profil', ['id' => $m['id']]) ?>" class="carte-chaine">
                <span class="rang"><?= ['🥇', '🥈', '🥉'][$rang] ?></span>
                <div class="carte-chaine-qui"><?= avatar($m['pseudo'], $m['avatar'], 44) ?>
                    <span><strong><?= e($m['pseudo']) ?></strong><span class="petit">📍 <?= e($m['ville']) ?> (<?= e($m['departement']) ?>)</span></span>
                </div>
                <div class="carte-chaine-parcours">
                    <span><?= e($m['depart']) ?><small><?= prix((float) $m['valeur_depart']) ?></small></span>
                    <span class="fleche-mini">→ <?= (int) $m['nb'] ?> échange<?= $m['nb'] > 1 ? 's' : '' ?> →</span>
                    <span><?= e($m['arrivee']) ?><small><?= prix((float) $m['valeur_arrivee']) ?></small></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($departement): ?>
    <div class="titre-section">
        <h2>📍 Près de chez toi — <?= e(nom_departement($departement)) ?></h2>
        <a href="<?= url('objet/recherche', ['departement' => $departement]) ?>">Tout voir →</a>
    </div>
    <?php if (!$pres): ?>
        <p class="vide">Pas encore d'autre annonce dans ton département. Parle du site autour de toi !</p>
    <?php else: ?>
        <div class="grille"><?php foreach ($pres as $o) require __DIR__ . '/objets/_carte.php'; ?></div>
    <?php endif; ?>
<?php endif; ?>

<div class="titre-section">
    <h2>🆕 Dernières annonces</h2>
    <a href="<?= url('objet/recherche') ?>">Toutes les annonces →</a>
</div>
<?php if (!$recentes): ?>
    <p class="vide">Aucun objet pour le moment. <a href="<?= url('objet/creer') ?>">Publie le premier !</a></p>
<?php else: ?>
    <div class="grille"><?php foreach ($recentes as $o) require __DIR__ . '/objets/_carte.php'; ?></div>
<?php endif; ?>

<section class="comment-ca-marche" id="comment">
    <h2>Comment ça marche ?</h2>
    <div class="etapes">
        <div><span>1</span><strong>Publie un objet</strong><p class="petit">Même tout petit : un trombone, un stylo, un vieux jeu. Une photo, un titre, c'est en ligne.</p></div>
        <div><span>2</span><strong>Propose un échange</strong><p class="petit">Repère un objet un peu plus intéressant et propose le tien. Discutez par message.</p></div>
        <div><span>3</span><strong>Monte d'un cran</strong><p class="petit">L'échange est accepté ? Tu recommences avec l'objet reçu. Ta chaîne s'affiche sur ton profil.</p></div>
    </div>
</section>

<section class="avantages">
    <div><span>🆓</span><strong>100 % gratuit</strong><p class="petit">Pas d'argent entre membres, que des objets.</p></div>
    <div><span>📍</span><strong>Près de chez toi</strong><p class="petit">Recherche par département pour échanger en main propre.</p></div>
    <div><span>⭐</span><strong>Membres notés</strong><p class="petit">Un avis après chaque échange pour savoir à qui tu as affaire.</p></div>
    <div><span>🛡️</span><strong>Modéré</strong><p class="petit">Annonces signalables, comptes vérifiés par email.</p></div>
</section>

<?php if (!$moi): ?>
    <section class="appel-final">
        <h2>Ton trombone t'attend dans un tiroir.</h2>
        <p>Inscris-toi en 30 secondes et lance ta première chaîne d'échanges.</p>
        <a href="<?= url('utilisateur/inscription') ?>" class="bouton bouton-grand bouton-blanc">Créer mon compte</a>
    </section>
<?php endif; ?>
