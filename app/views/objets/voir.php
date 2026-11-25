<?php
// Toutes les photos de l'annonce : la principale + les supplémentaires
$photos = array_merge(
    $objet['photo'] ? [$objet['photo']] : [],
    array_column($photosSup, 'fichier')
);
$connecte = utilisateur_connecte();
?>
<article class="fiche">
    <div class="galerie">
        <div class="galerie-principale">
            <img src="<?= $photos ? 'uploads/' . e($photos[0]) : photo_objet(null) ?>" alt="<?= e($objet['titre']) ?>" class="fiche-photo" id="photo-principale">
            <?php if ($objet['est_reserve']): ?><span class="ruban-reserve">Réservé</span><?php endif; ?>
        </div>
        <?php if (count($photos) > 1): ?>
            <div class="miniatures">
                <?php foreach ($photos as $i => $ph): ?>
                    <button type="button" class="miniature-bouton <?= $i === 0 ? 'active' : '' ?>" data-src="uploads/<?= e($ph) ?>" aria-label="Photo <?= $i + 1 ?>">
                        <img src="uploads/<?= e($ph) ?>" alt="" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="fiche-infos">
        <?php if ($objet['statut'] !== 'disponible'): ?>
            <div class="alerte alerte-erreur">Annonce retirée (visible seulement par l'admin).</div>
        <?php endif; ?>
        <div class="fiche-haut">
            <span class="badge"><?= e($objet['categorie']) ?></span>
            <?php if ($connecte && !$estProprio && $objet['statut'] === 'disponible'): ?>
                <form method="post" action="<?= url('objet/favori') ?>">
                    <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $objet['id'] ?>">
                    <button type="submit" class="bouton-favori <?= $estFavori ? 'actif' : '' ?>" title="<?= $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                        <?= $estFavori ? '❤️' : '🤍' ?> <span><?= $estFavori ? 'Dans tes favoris' : 'Favori' ?></span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <h1><?= e($objet['titre']) ?></h1>
        <p class="valeur grande">≈ <?= prix((float) $objet['valeur_estimee']) ?></p>
        <p><strong>État :</strong> <?= libelle_etat($objet['etat']) ?>
            <?php if ($nbFavoris > 0): ?><span class="petit"> · ❤️ <?= $nbFavoris ?> personne<?= $nbFavoris > 1 ? 's' : '' ?> intéressée<?= $nbFavoris > 1 ? 's' : '' ?></span><?php endif; ?></p>
        <p class="description"><?= nl2br(e($objet['description'])) ?></p>

        <a href="<?= url('utilisateur/profil', ['id' => $objet['id_utilisateur']]) ?>" class="bloc-proprio">
            <?= avatar($objet['pseudo'], $objet['avatar'], 48) ?>
            <span>
                <strong><?= e($objet['pseudo']) ?></strong>
                <span class="petit">📍 <?= e($objet['ville']) ?> (<?= e($objet['departement']) ?>)
                <?php if ($note['nombre'] > 0): ?>
                    · ⭐ <?= number_format((float) $note['moyenne'], 1, ',', '') ?>/5 (<?= $note['nombre'] ?> avis)
                <?php endif; ?></span>
            </span>
            <span class="petit voir-profil">Voir le profil →</span>
        </a>
        <p class="petit">Publié le <?= date_fr($objet['date_publication']) ?></p>

        <?php if ($objet['statut'] === 'disponible'): ?>
            <?php if ($estProprio): ?>
                <div class="actions">
                    <a href="<?= url('objet/modifier', ['id' => $objet['id']]) ?>" class="bouton bouton-secondaire">Modifier</a>
                    <form method="post" action="<?= url('objet/reserver') ?>">
                        <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $objet['id'] ?>">
                        <button type="submit" class="bouton bouton-secondaire"><?= $objet['est_reserve'] ? '🔓 Remettre disponible' : '🔒 Marquer réservé' ?></button>
                    </form>
                    <form method="post" action="<?= url('objet/retirer') ?>" onsubmit="return confirm('Retirer cette annonce ?');">
                        <?= csrf_champ() ?>
                        <input type="hidden" name="id" value="<?= $objet['id'] ?>">
                        <button type="submit" class="bouton bouton-danger">Retirer l'annonce</button>
                    </form>
                </div>
            <?php elseif ($connecte): ?>
                <?php if ($bloque): ?>
                    <div class="alerte alerte-info">Tu ne peux pas échanger avec ce membre.</div>
                <?php elseif ($objet['est_reserve']): ?>
                    <div class="alerte alerte-info">🔒 Cet objet est réservé : son propriétaire est en discussion pour un échange. Ajoute-le en favori pour le suivre !</div>
                <?php else: ?>
                    <a href="<?= url('proposition/nouvelle', ['id_objet' => $objet['id']]) ?>" class="bouton bouton-grand">🔁 Proposer un échange</a>

                    <?php if ($suggestions): ?>
                        <div class="suggestions">
                            <p class="petit"><strong>💡 Tes objets à la valeur la plus proche :</strong></p>
                            <?php foreach ($suggestions as $s): ?>
                                <a href="<?= url('proposition/nouvelle', ['id_objet' => $objet['id'], 'propose' => $s['id']]) ?>" class="suggestion">
                                    <img src="<?= photo_objet($s['photo']) ?>" alt="" loading="lazy">
                                    <span><?= e($s['titre']) ?><small>≈ <?= prix((float) $s['valeur_estimee']) ?></small></span>
                                    <span class="petit">Proposer →</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <details class="signaler">
                    <summary>Signaler cette annonce</summary>
                    <form method="post" action="<?= url('objet/signaler') ?>">
                        <?= csrf_champ() ?>
                        <input type="hidden" name="id" value="<?= $objet['id'] ?>">
                        <input type="text" name="motif" maxlength="255" required placeholder="Pourquoi ? (objet interdit, arnaque…)">
                        <button type="submit" class="bouton bouton-danger bouton-petit">Envoyer le signalement</button>
                    </form>
                </details>
            <?php else: ?>
                <a href="<?= url('utilisateur/connexion') ?>" class="bouton bouton-grand">Connecte-toi pour proposer un échange</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</article>

<script>
// Galerie : cliquer sur une miniature l'affiche en grand
document.querySelectorAll('.miniature-bouton').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
        document.getElementById('photo-principale').src = bouton.dataset.src;
        document.querySelectorAll('.miniature-bouton').forEach(b => b.classList.remove('active'));
        bouton.classList.add('active');
    });
});
</script>
