<section class="carte formulaire etroit centre">
    <div class="icone-grande">📬</div>
    <h1>Vérifie ta boîte mail</h1>
    <p>Un lien de confirmation a été envoyé<?= $email ? ' à <strong>' . e($email) . '</strong>' : '' ?>.
       Clique dessus pour activer ton compte (pense à regarder dans les spams).</p>

    <?php if (MODE_DEV && MAIL_MODE === 'fichier'): ?>
        <div class="alerte alerte-info">Mode développement : les emails ne partent pas vraiment.
            Ouvre la <a href="<?= url('dev/emails') ?>">boîte mail de test</a> pour cliquer sur le lien.</div>
    <?php endif; ?>

    <form method="post" action="<?= url('utilisateur/renvoyerVerification') ?>">
        <?= csrf_champ() ?>
        <input type="email" name="email" required placeholder="Ton email" value="<?= e($email ?? '') ?>">
        <button type="submit" class="bouton bouton-secondaire">Renvoyer l'email</button>
    </form>
</section>
