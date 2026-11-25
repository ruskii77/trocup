<?php
$depart  = $chaine ? $chaine[0]['donne_valeur'] : null;
$arrivee = $chaine ? end($chaine)['recu_valeur'] : null;
$multiplicateur = ($depart && $depart > 0) ? $arrivee / $depart : null;
$reseaux = reseaux_de($profil);
$mesBadges = badges($stats, $chaine, $note, $profil);
$connecte = utilisateur_connecte();
?>
<section class="profil-carte">
    <div class="profil-banniere"></div>
    <div class="profil-contenu">
        <div class="profil-avatar"><?= avatar($profil['pseudo'], $profil['avatar'], 112) ?></div>

        <div class="profil-identite">
            <h1><?= e($profil['pseudo']) ?> <?= $profil['role'] === 'admin' ? '<span class="badge">Admin</span>' : '' ?></h1>
            <p class="petit">
                📍 <?= e($profil['ville']) ?>, <?= e(nom_departement($profil['departement'])) ?>
                · membre depuis <?= date('m/Y', strtotime($profil['date_inscription'])) ?>
            </p>
            <?php if ($profil['est_banni']): ?><div class="alerte alerte-erreur">Compte banni</div><?php endif; ?>
            <?php if ($profil['bio']): ?>
                <p class="profil-bio"><?= nl2br(e($profil['bio'])) ?></p>
            <?php elseif ($estMoi): ?>
                <p class="profil-bio vide">Ajoute une bio et une photo dans <a href="<?= url('utilisateur/compte') ?>">Mon compte</a> pour donner envie d'échanger avec toi.</p>
            <?php endif; ?>
            <?php if ($reseaux): ?>
                <div class="profil-reseaux">
                    <?php foreach ($reseaux as $r): ?>
                        <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener nofollow ugc" class="reseau-pastille reseau-<?= $r['cle'] ?>">
                            <?= $r['nom'] ?> <span>@<?= e($r['pseudo']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($estMoi): ?>
            <a href="<?= url('utilisateur/compte') ?>" class="bouton bouton-secondaire bouton-petit profil-modifier">✏️ Modifier</a>
        <?php endif; ?>
    </div>

    <?php if ($mesBadges): ?>
        <div class="badges-profil">
            <?php foreach ($mesBadges as [$icone, $nom, $explication]): ?>
                <span class="badge-succes" title="<?= e($explication) ?>"><?= $icone ?> <?= e($nom) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="profil-stats">
        <div><strong><?= $stats['echanges'] ?></strong><span>échange<?= $stats['echanges'] > 1 ? 's' : '' ?></span></div>
        <div><strong><?= count($objets) ?></strong><span>objet<?= count($objets) > 1 ? 's' : '' ?> dispo</span></div>
        <div><strong><?= $note['nombre'] > 0 ? number_format((float) $note['moyenne'], 1, ',', '') . ' ★' : '—' ?></strong><span><?= (int) $note['nombre'] ?> avis</span></div>
        <div><strong><?= $multiplicateur ? '×' . number_format($multiplicateur, $multiplicateur < 10 ? 1 : 0, ',', ' ') : '—' ?></strong><span>valeur de départ</span></div>
    </div>
</section>

<div class="titre-section">
    <h2>🔗 Chaîne d'échanges</h2>
    <?php if ($chaine): ?>
        <?php
        // Données pour fabriquer l'image à partager (voir public/js/partage-chaine.js)
        $etapes = [['titre' => $chaine[0]['donne_titre'], 'valeur' => $chaine[0]['donne_valeur'], 'photo' => $chaine[0]['donne_photo']]];
        foreach ($chaine as $c) {
            $etapes[] = ['titre' => $c['recu_titre'], 'valeur' => $c['recu_valeur'], 'photo' => $c['recu_photo']];
        }
        ?>
        <button type="button" class="bouton bouton-petit" id="partager-chaine"
                data-pseudo="<?= e($profil['pseudo']) ?>" data-site="<?= e(NOM_SITE) ?>" data-accent="<?= e(NOM_ACCENT) ?>"
                data-slogan="<?= e(SLOGAN) ?>" data-fichier="ma-chaine-<?= slug_site() ?>.png"
                data-etapes="<?= e(json_encode($etapes, JSON_UNESCAPED_UNICODE)) ?>">📤 <?= $estMoi ? 'Partager ma chaîne' : 'Partager cette chaîne' ?></button>
    <?php endif; ?>
</div>
<?php if (!$chaine): ?>
    <p class="vide"><?= $estMoi ? 'Ta chaîne commence à ton premier échange accepté. À toi de jouer !' : 'Pas encore d\'échange réalisé.' ?></p>
<?php else: ?>
    <p>Parti de <strong><?= e($chaine[0]['donne_titre']) ?></strong> (≈ <?= prix($depart) ?>)
       → aujourd'hui <strong><?= e(end($chaine)['recu_titre']) ?></strong> (≈ <?= prix($arrivee) ?>)
       en <?= count($chaine) ?> échange<?= count($chaine) > 1 ? 's' : '' ?>.</p>
    <ol class="chaine">
        <li class="maillon">
            <img src="<?= photo_objet($chaine[0]['donne_photo']) ?>" alt="" loading="lazy">
            <strong><?= e($chaine[0]['donne_titre']) ?></strong>
            <span class="valeur">≈ <?= prix($chaine[0]['donne_valeur']) ?></span>
            <span class="petit">Départ</span>
        </li>
        <?php foreach ($chaine as $etape): ?>
            <li class="fleche">→</li>
            <li class="maillon">
                <img src="<?= photo_objet($etape['recu_photo']) ?>" alt="" loading="lazy">
                <strong><?= e($etape['recu_titre']) ?></strong>
                <span class="valeur">≈ <?= prix($etape['recu_valeur']) ?></span>
                <span class="petit">avec <?= e($etape['avec']) ?>, le <?= date('d/m/Y', strtotime($etape['date'])) ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<h2>📦 Objets disponibles</h2>
<?php if (!$objets): ?>
    <p class="vide">Aucun objet en ce moment.
        <?php if ($estMoi): ?><a href="<?= url('objet/creer') ?>">Publier un objet</a><?php endif; ?></p>
<?php else: ?>
    <div class="grille"><?php foreach ($objets as $o) require __DIR__ . '/../objets/_carte.php'; ?></div>
<?php endif; ?>

<h2>⭐ Avis reçus</h2>
<?php if (!$avis): ?>
    <p class="vide">Pas encore d'avis.</p>
<?php else: ?>
    <?php foreach ($avis as $a): ?>
        <div class="carte avis">
            <div class="avis-entete">
                <?= avatar($a['pseudo_auteur'], $a['avatar_auteur'], 32) ?>
                <a href="<?= url('utilisateur/profil', ['id' => $a['id_auteur']]) ?>"><strong><?= e($a['pseudo_auteur']) ?></strong></a>
                <span class="etoiles"><?= str_repeat('★', (int) $a['note']) . str_repeat('☆', 5 - (int) $a['note']) ?></span>
                <span class="petit"><?= date_fr($a['date']) ?></span>
            </div>
            <?php if ($a['commentaire']): ?><p><?= nl2br(e($a['commentaire'])) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($connecte && !$estMoi): ?>
    <details class="signaler">
        <summary>Signaler ou bloquer <?= e($profil['pseudo']) ?></summary>
        <div class="deux-colonnes">
            <form method="post" action="<?= url('utilisateur/signaler') ?>">
                <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $profil['id'] ?>">
                <input type="text" name="motif" maxlength="255" required placeholder="Pourquoi ? (arnaque, insultes, faux profil…)">
                <button type="submit" class="bouton bouton-danger bouton-petit">Signaler ce membre</button>
            </form>
            <form method="post" action="<?= url('utilisateur/bloquer') ?>">
                <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $profil['id'] ?>">
                <p class="petit">Bloquer empêche toute proposition et tout message entre vous.</p>
                <button type="submit" class="bouton bouton-secondaire bouton-petit"><?= $jeBloque ? '✅ Débloquer' : '🚫 Bloquer' ?></button>
            </form>
        </div>
    </details>
<?php endif; ?>

<div id="apercu-partage" class="apercu-partage" hidden>
    <div class="apercu-boite">
        <img id="apercu-image" alt="Image de la chaîne à partager">
        <div class="actions centre">
            <button type="button" class="bouton" id="apercu-partager">📤 Partager</button>
            <a class="bouton bouton-secondaire" id="apercu-telecharger" download="ma-chaine-<?= slug_site() ?>.png">⬇️ Télécharger</a>
            <button type="button" class="bouton bouton-secondaire" id="apercu-fermer">Fermer</button>
        </div>
    </div>
</div>
<script src="js/partage-chaine.js"></script>
