<?php
// Petit bout de vue réutilisé pour les deux listes
$afficherListe = function (array $liste, bool $recues) use ($notifs) {
    if (!$liste) {
        echo '<p class="vide">Rien pour le moment.</p>';
        return;
    }
    echo '<div class="liste-propositions">';
    foreach ($liste as $p) {
        $autre = $recues ? $p['pseudo_proposeur'] : $p['pseudo_destinataire'];
        $donne = $recues ? $p['titre_demande'] : $p['titre_propose'];
        $recoit = $recues ? $p['titre_propose'] : $p['titre_demande'];
        $n = $notifs[(int) $p['id']] ?? null;
        ?>
        <a href="<?= url('proposition/voir', ['id' => $p['id']]) ?>" class="carte proposition-ligne <?= $n ? 'non-lu' : '' ?>">
            <span class="statut statut-<?= e($p['statut']) ?>"><?= libelle_statut($p['statut']) ?></span>
            <?php if ($n): ?>
                <span class="pastilles">
                    <?php if ($n['nouvelle']): ?><span class="pastille">🆕 Nouvelle proposition</span><?php endif; ?>
                    <?php if ($n['reponse']): ?><span class="pastille">📣 Réponse reçue</span><?php endif; ?>
                    <?php if ($n['messages']): ?><span class="pastille">💬 <?= $n['messages'] ?> nouveau<?= $n['messages'] > 1 ? 'x' : '' ?> message<?= $n['messages'] > 1 ? 's' : '' ?></span><?php endif; ?>
                </span>
            <?php endif; ?>
            <span>Tu donnes <strong><?= e($donne) ?></strong> → tu reçois <strong><?= e($recoit) ?></strong></span>
            <span class="petit">avec <?= e($autre) ?> · <?= date_fr($p['date_proposition']) ?></span>
        </a>
        <?php
    }
    echo '</div>';
};
?>
<h1>Mes échanges</h1>
<?php if ($notifs): ?>
    <div class="alerte alerte-info">🔔 Tu as du nouveau dans <?= count($notifs) ?> échange<?= count($notifs) > 1 ? 's' : '' ?> (surligné<?= count($notifs) > 1 ? 's' : '' ?> en vert).</div>
<?php endif; ?>

<h2>📥 Propositions reçues</h2>
<?php $afficherListe($recues, true); ?>

<h2>📤 Propositions envoyées</h2>
<?php $afficherListe($envoyees, false); ?>
