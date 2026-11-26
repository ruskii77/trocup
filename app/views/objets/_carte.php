<?php // Petit morceau de vue réutilisé partout : la carte d'une annonce. Attend une variable $o ?>
<a href="<?= url('objet/voir', ['id' => $o['id'], 'slug' => $o['titre']]) ?>" class="carte-objet">
    <div class="carte-objet-photo">
        <img src="<?= photo_objet($o['photo']) ?>" alt="<?= e($o['titre']) ?> à échanger" loading="lazy">
        <?php if (!empty($o['est_reserve'])): ?><span class="ruban-reserve">Réservé</span><?php endif; ?>
    </div>
    <div class="carte-objet-infos">
        <span class="badge"><?= e($o['categorie']) ?></span>
        <h3><?= e($o['titre']) ?></h3>
        <p class="valeur">≈ <?= prix((float) $o['valeur_estimee']) ?></p>
        <p class="petit carte-proprio"><?= avatar($o['pseudo'], $o['avatar'] ?? null, 22) ?> <?= e($o['pseudo']) ?> · 📍 <?= e($o['ville']) ?> (<?= e($o['departement']) ?>)</p>
    </div>
</a>
