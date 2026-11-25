<section class="carte formulaire">
    <?php if ($installation): ?>
        <h1>🚀 Installation du site</h1>
        <p>La base de données est vide. Clique sur le bouton pour créer toutes les tables.</p>
    <?php else: ?>
        <h1>🛠️ Mise à jour de la base de données</h1>
        <p>Les mises à jour ajoutent ou modifient des tables <strong>sans effacer tes données</strong>
           (comptes, objets, messages, photos).</p>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><pre class="erreur-sql"><?= e($erreur) ?></pre></div>
    <?php endif; ?>

    <?php if ($enAttente): ?>
        <h2><?= count($enAttente) ?> mise(s) à jour en attente</h2>
        <ul class="liste-maj">
            <?php foreach ($enAttente as $f): ?><li>⏳ <?= e($f) ?></li><?php endforeach; ?>
        </ul>
        <?php if (!$installation): ?>
            <p class="petit">Conseil : fais d'abord une sauvegarde dans phpMyAdmin (base → onglet <em>Exporter</em> → Exécuter).</p>
        <?php endif; ?>
        <form method="post" action="<?= url('maj/index') ?>">
            <?= csrf_champ() ?>
            <button type="submit" class="bouton bouton-grand"><?= $installation ? 'Installer le site' : 'Appliquer les mises à jour' ?></button>
        </form>
    <?php else: ?>
        <div class="alerte alerte-succes">✅ La base est à jour.</div>
    <?php endif; ?>

    <?php if ($appliquees): ?>
        <h2>Déjà appliquées</h2>
        <ul class="liste-maj">
            <?php foreach ($appliquees as $f): ?><li>✅ <?= e($f) ?></li><?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
