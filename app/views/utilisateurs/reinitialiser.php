<section class="carte formulaire etroit">
    <h1>Nouveau mot de passe</h1>
    <?php if ($erreur): ?><div class="alerte alerte-erreur"><?= e($erreur) ?></div><?php endif; ?>
    <form method="post" action="<?= url('utilisateur/reinitialiser') ?>">
        <?= csrf_champ() ?>
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
        <label>Nouveau mot de passe <input type="password" name="mot_de_passe" required minlength="8">
            <span class="aide">8 caractères minimum, avec au moins une lettre et un chiffre.</span></label>
        <label>Confirme le mot de passe <input type="password" name="mot_de_passe_confirmation" required minlength="8"></label>
        <button type="submit" class="bouton bouton-grand">Enregistrer</button>
    </form>
</section>
