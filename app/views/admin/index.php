<h1>🛠️ Administration</h1>
<p><a href="<?= url('maj/index') ?>">🗄️ Mise à jour de la base de données</a></p>

<h2>🚩 Signalements en attente (<?= count($signalements) ?>)</h2>
<?php if (!$signalements): ?>
    <p class="vide">Aucun signalement, tout va bien.</p>
<?php else: ?>
    <table class="tableau">
        <thead><tr><th>Date</th><th>Annonce</th><th>Propriétaire</th><th>Motif</th><th>Signalé par</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($signalements as $s): ?>
            <tr>
                <td><?= date_fr($s['date']) ?></td>
                <td>
                    <a href="<?= url('objet/voir', ['id' => $s['id_objet']]) ?>"><?= e($s['titre']) ?></a>
                    <?= $s['statut_objet'] !== 'disponible' ? '<span class="badge">déjà retirée</span>' : '' ?>
                </td>
                <td><a href="<?= url('utilisateur/profil', ['id' => $s['id_proprietaire']]) ?>"><?= e($s['pseudo_proprietaire']) ?></a></td>
                <td><?= e($s['motif']) ?></td>
                <td><?= e($s['pseudo_auteur']) ?></td>
                <td class="actions-admin">
                    <form method="post" action="<?= url('admin/retirerObjet') ?>">
                        <?= csrf_champ() ?><input type="hidden" name="id_objet" value="<?= $s['id_objet'] ?>">
                        <button class="bouton bouton-danger bouton-petit">Retirer l'annonce</button>
                    </form>
                    <form method="post" action="<?= url('admin/ignorer') ?>">
                        <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="bouton bouton-secondaire bouton-petit">Classer sans suite</button>
                    </form>
                    <?php if (!$s['est_banni']): ?>
                        <form method="post" action="<?= url('admin/bannir') ?>" onsubmit="return confirm('Bannir ce compte ?');">
                            <?= csrf_champ() ?><input type="hidden" name="id_utilisateur" value="<?= $s['id_proprietaire'] ?>">
                            <button class="bouton bouton-danger bouton-petit">Bannir le propriétaire</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>🙋 Membres signalés (<?= count($signalementsMembres) ?>)</h2>
<?php if (!$signalementsMembres): ?>
    <p class="vide">Aucun membre signalé.</p>
<?php else: ?>
    <table class="tableau">
        <thead><tr><th>Date</th><th>Membre</th><th>Motif</th><th>Signalé par</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($signalementsMembres as $s): ?>
            <tr>
                <td><?= date_fr($s['date']) ?></td>
                <td><a href="<?= url('utilisateur/profil', ['id' => $s['id_signale']]) ?>" class="carte-proprio"><?= avatar($s['pseudo_signale'], $s['avatar_signale'], 24) ?> <?= e($s['pseudo_signale']) ?></a></td>
                <td><?= e($s['motif']) ?></td>
                <td><?= e($s['pseudo_auteur']) ?></td>
                <td class="actions-admin">
                    <form method="post" action="<?= url('admin/ignorerMembre') ?>">
                        <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="bouton bouton-secondaire bouton-petit">Classer sans suite</button>
                    </form>
                    <?php if (!$s['est_banni']): ?>
                        <form method="post" action="<?= url('admin/bannir') ?>" onsubmit="return confirm('Bannir ce membre ?');">
                            <?= csrf_champ() ?><input type="hidden" name="id_utilisateur" value="<?= $s['id_signale'] ?>">
                            <button class="bouton bouton-danger bouton-petit">Bannir</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>👥 Utilisateurs (<?= count($utilisateurs) ?>)</h2>
<table class="tableau">
    <thead><tr><th>Pseudo</th><th>Email</th><th>Ville</th><th>Inscrit le</th><th>Objets</th><th>Rôle</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($utilisateurs as $u): ?>
        <tr class="<?= $u['est_banni'] ? 'ligne-bannie' : '' ?>">
            <td><a href="<?= url('utilisateur/profil', ['id' => $u['id']]) ?>" class="carte-proprio"><?= avatar($u['pseudo'], $u['avatar'], 24) ?> <?= e($u['pseudo']) ?></a></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['ville']) ?></td>
            <td><?= date('d/m/Y', strtotime($u['date_inscription'])) ?></td>
            <td><?= (int) $u['nb_objets'] ?></td>
            <td><?= e($u['role']) ?></td>
            <td>
                <?php if ($u['role'] !== 'admin'): ?>
                    <form method="post" action="<?= url($u['est_banni'] ? 'admin/debannir' : 'admin/bannir') ?>">
                        <?= csrf_champ() ?><input type="hidden" name="id_utilisateur" value="<?= $u['id'] ?>">
                        <button class="bouton bouton-petit <?= $u['est_banni'] ? 'bouton-secondaire' : 'bouton-danger' ?>">
                            <?= $u['est_banni'] ? 'Débannir' : 'Bannir' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
