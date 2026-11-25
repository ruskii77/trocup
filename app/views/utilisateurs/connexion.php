<section class="carte formulaire etroit">
    <h1>Connexion</h1>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
    <?php endif; ?>

    <?php if ($nonVerifie): ?>
        <form method="post" action="<?= url('utilisateur/renvoyerVerification') ?>" class="centre">
            <?= csrf_champ() ?>
            <input type="hidden" name="email" value="<?= e($email) ?>">
            <button type="submit" class="bouton bouton-secondaire bouton-petit">Renvoyer l'email de confirmation</button>
        </form>
    <?php endif; ?>

    <form method="post" action="<?= url('utilisateur/connexion') ?>">
        <?= csrf_champ() ?>
        <label>Email <input type="email" name="email" required value="<?= e($email) ?>"></label>
        <label>Mot de passe <input type="password" name="mot_de_passe" required></label>
        <p class="droite petit"><a href="<?= url('utilisateur/motDePasseOublie') ?>">Mot de passe oublié ?</a></p>
        <button type="submit" class="bouton bouton-grand">Se connecter</button>
    </form>
    <p class="petit centre">Pas encore de compte ? <a href="<?= url('utilisateur/inscription') ?>">Inscris-toi</a></p>
</section>
