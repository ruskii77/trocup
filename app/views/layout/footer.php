</main>

<footer class="pied">
    <div class="conteneur pied-contenu">
        <div>
            <a href="<?= url('accueil') ?>" class="logo">
                <img src="img/logo.svg" alt="" width="30" height="30">
                <span class="logo-texte"><?= logo_texte() ?></span>
            </a>
            <p class="petit"><?= e(SLOGAN) ?><br>Le troc entre particuliers, un échange après l'autre.</p>
        </div>
        <div>
            <strong>Explorer</strong>
            <a href="<?= url('objet/recherche') ?>">Chercher un objet</a>
            <a href="<?= url('objet/creer') ?>">Publier un objet</a>
            <a href="<?= url('accueil') ?>#comment">Comment ça marche</a>
        </div>
        <div>
            <strong>Sécurité</strong>
            <p class="petit">Échangez dans un lieu public et vérifiez l'objet avant l'échange.<br>
               Interdits : armes, médicaments, drogues, contrefaçons, animaux, objets volés.</p>
        </div>
    </div>
    <div class="conteneur pied-bas petit">
        © <?= date('Y') ?> <?= e(NOM_SITE) ?>
        · <a href="<?= url('page/mentions') ?>">Mentions légales</a>
        · <a href="<?= url('page/confidentialite') ?>">Confidentialité</a>
        · <a href="<?= url('page/cgu') ?>">Conditions d'utilisation</a>
        <?php if (MODE_DEV && MAIL_MODE === 'fichier'): ?>
            · 🛠️ Mode développement · <a href="<?= url('dev/emails') ?>">📬 Boîte mail de test</a>
        <?php endif; ?>
    </div>
</footer>
<?php if (utilisateur_connecte()): ?>
    <script src="js/notifications.js"></script>
<?php endif; ?>
</body>
</html>
