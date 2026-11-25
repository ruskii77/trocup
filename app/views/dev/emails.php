<h1>📬 Boîte mail de test</h1>
<div class="alerte alerte-info">Mode développement : les emails du site sont enregistrés ici au lieu d'être envoyés
    (réglage <code>MAIL_MODE</code> dans <code>config/config.php</code>). Cette page disparaît quand <code>MODE_DEV</code> vaut false.</div>

<?php if (!$emails): ?>
    <p class="vide">Aucun email pour le moment.</p>
<?php endif; ?>

<?php foreach ($emails as $m): ?>
    <details class="carte email-test">
        <summary><strong><?= e($m['sujet']) ?></strong> → <?= e($m['a']) ?> <span class="petit">· <?= date_fr($m['date']) ?></span></summary>
        <div class="email-contenu"><?= $m['html'] /* HTML généré par le site lui-même (déjà échappé dans Mailer) */ ?></div>
    </details>
<?php endforeach; ?>
