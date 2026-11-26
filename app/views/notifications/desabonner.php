<section class="carte formulaire etroit centre">
    <h1>🔔 Notifications par email</h1>
    <?php if ($fait): ?>
        <p>C'est fait : tu ne recevras plus les emails <strong>« <?= e($type['libelle']) ?> »</strong>.</p>
        <p class="petit">Tu peux les réactiver quand tu veux dans <a href="<?= url('utilisateur/compte') ?>#notifications">Mon compte → Notifications</a>.</p>
    <?php else: ?>
        <p>Ne plus recevoir les emails <strong>« <?= e($type['libelle']) ?> »</strong> ?</p>
        <p class="petit">Ce sont les emails envoyés <?= e($type['detail']) ?>.
           Les emails importants pour ton compte (confirmation, mot de passe oublié) continueront d'arriver.</p>
        <form method="post" action="<?= e($lien) ?>">
            <button class="bouton">Oui, ne plus recevoir ces emails</button>
        </form>
    <?php endif; ?>
</section>
