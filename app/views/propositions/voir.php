<h1>Proposition d'échange <span class="statut statut-<?= e($p['statut']) ?>"><?= libelle_statut($p['statut']) ?></span></h1>

<div class="echange">
    <div class="carte echange-objet">
        <a href="<?= url('utilisateur/profil', ['id' => $p['id_proposeur']]) ?>" class="echange-qui"><?= avatar($p['pseudo_proposeur'], $p['avatar_proposeur'], 28) ?> <?= e($p['pseudo_proposeur']) ?> donne</a>
        <img src="<?= photo_objet($p['photo_propose']) ?>" alt="">
        <a href="<?= url('objet/voir', ['id' => $p['id_objet_propose']]) ?>"><strong><?= e($p['titre_propose']) ?></strong></a>
        <span class="valeur">≈ <?= prix((float) $p['valeur_propose']) ?></span>
    </div>
    <div class="echange-fleche">⇄</div>
    <div class="carte echange-objet">
        <a href="<?= url('utilisateur/profil', ['id' => $p['id_destinataire']]) ?>" class="echange-qui"><?= avatar($p['pseudo_destinataire'], $p['avatar_destinataire'], 28) ?> <?= e($p['pseudo_destinataire']) ?> donne</a>
        <img src="<?= photo_objet($p['photo_demande']) ?>" alt="">
        <a href="<?= url('objet/voir', ['id' => $p['id_objet_demande']]) ?>"><strong><?= e($p['titre_demande']) ?></strong></a>
        <span class="valeur">≈ <?= prix((float) $p['valeur_demande']) ?></span>
    </div>
</div>

<?php if ($p['statut'] === 'en_attente'): ?>
    <div class="actions centre">
        <?php if ($estDestinataire): ?>
            <form method="post" action="<?= url('proposition/accepter') ?>" onsubmit="return confirm('Accepter cet échange ? Les objets changeront de propriétaire.');">
                <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="bouton">✅ Accepter</button>
            </form>
            <form method="post" action="<?= url('proposition/refuser') ?>">
                <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="bouton bouton-danger">Refuser</button>
            </form>
        <?php else: ?>
            <p>En attente de la réponse de <?= e($p['pseudo_destinataire']) ?>.</p>
            <form method="post" action="<?= url('proposition/annuler') ?>">
                <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="bouton bouton-secondaire">Annuler ma proposition</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($p['statut'] === 'acceptee'): ?>
    <div class="alerte alerte-succes">Échange accepté le <?= date_fr($p['date_reponse']) ?> 🎉 Convenez d'un lieu public pour vous retrouver.</div>
<?php endif; ?>

<?php if ($peutDonnerAvis): ?>
    <section class="carte">
        <h2>Laisser un avis</h2>
        <form method="post" action="<?= url('proposition/avis') ?>">
            <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
            <label>Note
                <select name="note" required>
                    <?php for ($n = 5; $n >= 1; $n--): ?>
                        <option value="<?= $n ?>"><?= str_repeat('★', $n) ?> (<?= $n ?>/5)</option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>Commentaire (facultatif) <textarea name="commentaire" rows="2" maxlength="1000"></textarea></label>
            <button type="submit" class="bouton">Publier l'avis</button>
        </form>
    </section>
<?php endif; ?>

<section class="carte discussion">
    <h2>💬 Discussion</h2>
    <?php if (!$messages): ?>
        <p class="vide">Aucun message. Lance la conversation !</p>
    <?php endif; ?>
    <?php $separateurAffiche = false; ?>
    <?php foreach ($messages as $m): ?>
        <?php
        $deMoi = (int) $m['id_expediteur'] === (int) utilisateur_connecte()['id'];
        $estNouveau = !$deMoi && ($derniereLecture === null || $m['date_envoi'] > $derniereLecture);
        if ($estNouveau && !$separateurAffiche): $separateurAffiche = true; ?>
            <div class="separateur-nouveau"><span>Nouveaux messages</span></div>
        <?php endif; ?>
        <div class="bulle <?= $deMoi ? 'bulle-moi' : '' ?> <?= $estNouveau ? 'bulle-nouvelle' : '' ?>">
            <div class="bulle-entete"><?= avatar($m['pseudo'], $m['avatar'], 24) ?> <strong><?= e($m['pseudo']) ?></strong> <span class="petit"><?= date_fr($m['date_envoi']) ?></span></div>
            <p><?= nl2br(e($m['contenu'])) ?></p>
        </div>
    <?php endforeach; ?>

    <form method="post" action="<?= url('proposition/message') ?>" class="envoi-message">
        <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
        <textarea name="contenu" rows="2" maxlength="2000" required placeholder="Écris ton message…"></textarea>
        <button type="submit" class="bouton">Envoyer</button>
    </form>
</section>
