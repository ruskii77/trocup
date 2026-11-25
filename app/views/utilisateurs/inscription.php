<section class="carte formulaire etroit">
    <h1>Créer un compte</h1>

    <?php foreach ($erreurs as $err): ?>
        <div class="alerte alerte-erreur"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('utilisateur/inscription') ?>">
        <?= csrf_champ() ?>
        <label>Pseudo <input type="text" name="pseudo" required value="<?= e($valeurs['pseudo']) ?>"></label>
        <label>Email <input type="email" name="email" required value="<?= e($valeurs['email']) ?>">
            <span class="aide">Un lien de confirmation y sera envoyé.</span></label>
        <div class="ligne">
            <label>Code postal <input type="text" name="code_postal" required inputmode="numeric" pattern="\d{5}" maxlength="5" value="<?= e($valeurs['code_postal']) ?>" placeholder="34000"></label>
            <label>Ville <input type="text" name="ville" required value="<?= e($valeurs['ville']) ?>" placeholder="Montpellier"></label>
        </div>
        <label>Mot de passe <input type="password" name="mot_de_passe" required minlength="8">
            <span class="aide">8 caractères minimum avec 3 types parmi : minuscules, majuscules, chiffres, symboles. Ou une phrase de 16 caractères ou plus.</span></label>
        <label>Confirme le mot de passe <input type="password" name="mot_de_passe_confirmation" required minlength="8"></label>

        <!-- Piège à robots : invisible pour les humains, les robots le remplissent -->
        <div class="pot-de-miel" aria-hidden="true">
            <label>Ne pas remplir <input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>
        </div>

        <label class="case">
            <input type="checkbox" name="cgu" value="1" required>
            <span>J'ai 18 ans ou plus et j'accepte les <a href="<?= url('page/cgu') ?>" target="_blank">conditions d'utilisation</a>
                  et la <a href="<?= url('page/confidentialite') ?>" target="_blank">politique de confidentialité</a>.</span>
        </label>
        <button type="submit" class="bouton bouton-grand">S'inscrire</button>
    </form>
    <p class="petit centre">Déjà inscrit ? <a href="<?= url('utilisateur/connexion') ?>">Connecte-toi</a></p>
</section>
