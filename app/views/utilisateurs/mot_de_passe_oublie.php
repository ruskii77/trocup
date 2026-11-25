<section class="carte formulaire etroit">
    <h1>Mot de passe oublié</h1>
    <p>Indique l'email de ton compte : tu recevras un lien pour choisir un nouveau mot de passe (valable 1 heure).</p>
    <form method="post" action="<?= url('utilisateur/motDePasseOublie') ?>">
        <?= csrf_champ() ?>
        <label>Email <input type="email" name="email" required></label>
        <button type="submit" class="bouton bouton-grand">Recevoir le lien</button>
    </form>
    <p class="petit centre"><a href="<?= url('utilisateur/connexion') ?>">Retour à la connexion</a></p>
</section>
