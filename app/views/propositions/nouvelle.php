<section class="carte formulaire">
    <h1>Proposer un échange</h1>
    <p>Tu veux : <strong><?= e($objetDemande['titre']) ?></strong> (≈ <?= prix((float) $objetDemande['valeur_estimee']) ?>)
        de <?= e($objetDemande['pseudo']) ?></p>

    <?php if (!$mesObjets): ?>
        <div class="alerte alerte-erreur">Tu n'as aucun objet disponible à proposer.</div>
        <a href="<?= url('objet/creer') ?>" class="bouton">Publier un objet d'abord</a>
    <?php else: ?>
        <form method="post" action="<?= url('proposition/nouvelle', ['id_objet' => $objetDemande['id']]) ?>">
            <?= csrf_champ() ?>
            <input type="hidden" name="id_objet_demande" value="<?= $objetDemande['id'] ?>">

            <p><strong>Ce que tu proposes en échange :</strong></p>
            <div class="choix-objets">
                <?php foreach ($mesObjets as $i => $o): ?>
                    <label class="choix-objet">
                        <input type="radio" name="id_objet_propose" value="<?= $o['id'] ?>" <?= ($preselection ? (int) $o['id'] === $preselection : $i === 0) ? 'checked' : '' ?>>
                        <img src="<?= photo_objet($o['photo']) ?>" alt="">
                        <span><?= e($o['titre']) ?><br><small>≈ <?= prix((float) $o['valeur_estimee']) ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>Un petit mot (facultatif)
                <textarea name="message" rows="3" maxlength="2000" placeholder="Pourquoi ton objet vaut le coup…"></textarea>
            </label>

            <button type="submit" class="bouton bouton-grand">Envoyer la proposition</button>
        </form>
    <?php endif; ?>
</section>
