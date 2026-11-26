<div class="titre-section">
    <h1>Mon compte</h1>
    <a href="<?= url('utilisateur/profil', ['id' => $u['id']]) ?>">Voir mon profil public →</a>
</div>

<?php foreach ($erreurs as $err): ?>
    <div class="alerte alerte-erreur"><?= e($err) ?></div>
<?php endforeach; ?>

<section class="carte">
    <h2>✨ Mon profil public <span class="petit">— tout est facultatif</span></h2>

    <div class="compte-profil">
        <div class="compte-avatar">
            <?= avatar($u['pseudo'], $u['avatar'], 120) ?>
            <form method="post" action="<?= url('utilisateur/compte') ?>" enctype="multipart/form-data">
                <?= csrf_champ() ?>
                <input type="hidden" name="action" value="avatar">
                <label class="bouton bouton-secondaire bouton-petit bouton-fichier">
                    📷 <?= $u['avatar'] ? 'Changer la photo' : 'Ajouter une photo' ?>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" data-heic data-envoi-auto>
                </label>
                <noscript><button type="submit" class="bouton bouton-petit">Envoyer</button></noscript>
            </form>
            <?php if ($u['avatar']): ?>
                <form method="post" action="<?= url('utilisateur/compte') ?>">
                    <?= csrf_champ() ?>
                    <input type="hidden" name="action" value="avatar">
                    <input type="hidden" name="supprimer" value="1">
                    <button type="submit" class="lien-bouton petit">Supprimer la photo</button>
                </form>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= url('utilisateur/compte') ?>" class="compte-infos">
            <?= csrf_champ() ?>
            <input type="hidden" name="action" value="profil">

            <label>Bio
                <textarea name="bio" rows="3" maxlength="300" placeholder="Qui es-tu ? Ce que tu aimes échanger, ton objectif de chaîne…"
                          data-compteur="300"><?= e($u['bio'] ?? '') ?></textarea>
                <span class="aide"><?= mb_strlen($u['bio'] ?? '') ?> / 300</span>
            </label>

            <p class="label-groupe">Mes réseaux</p>
            <div class="reseaux-saisie">
                <?php foreach (Utilisateur::RESEAUX as $cle => [$nom]): ?>
                    <label class="champ-reseau">
                        <span class="reseau-pastille reseau-<?= $cle ?>"><?= $nom ?></span>
                        <span class="prefixe">@</span>
                        <input type="text" name="<?= $cle ?>" value="<?= e($u[$cle] ?? '') ?>" placeholder="ton pseudo" aria-label="<?= $nom ?>">
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="aide">Tape juste ton nom d'utilisateur (tu peux aussi coller le lien de ton profil).</p>

            <button type="submit" class="bouton">Enregistrer mon profil</button>
        </form>
    </div>
</section>

<div class="deux-colonnes">
    <section class="carte">
        <h2>📍 Ma localisation</h2>
        <p class="petit">Elle sert à montrer tes annonces aux gens près de chez toi.</p>
        <form method="post" action="<?= url('utilisateur/compte') ?>">
            <?= csrf_champ() ?>
            <input type="hidden" name="action" value="localisation">
            <label>Code postal <input type="text" name="code_postal" required inputmode="numeric" pattern="\d{5}" maxlength="5" autocomplete="postal-code"
                                      value="<?= e($u['code_postal']) ?>" data-code-postal="ville-compte"></label>
            <label>Ville <input type="text" name="ville" id="ville-compte" required autocomplete="off" list="ville-compte-liste" value="<?= e($u['ville']) ?>">
                <datalist id="ville-compte-liste"></datalist></label>
            <p class="petit">Département actuel : <strong><?= e(nom_departement($u['departement'])) ?> (<?= e($u['departement']) ?>)</strong></p>
            <button type="submit" class="bouton">Enregistrer</button>
        </form>
    </section>

    <section class="carte">
        <h2>🔒 Changer mon mot de passe</h2>
        <form method="post" action="<?= url('utilisateur/compte') ?>">
            <?= csrf_champ() ?>
            <input type="hidden" name="action" value="mot_de_passe">
            <label>Mot de passe actuel <input type="password" name="ancien" required></label>
            <label>Nouveau mot de passe <input type="password" name="mot_de_passe" required minlength="8"></label>
            <label>Confirme le nouveau <input type="password" name="mot_de_passe_confirmation" required minlength="8"></label>
            <button type="submit" class="bouton">Changer</button>
        </form>
    </section>
</div>

<section class="carte" id="notifications">
    <h2>🔔 Notifications par email</h2>
    <p class="petit">Choisis ce pour quoi tu veux être prévenu par email. Chaque email contient aussi un lien pour arrêter en un clic.</p>
    <form method="post" action="<?= url('utilisateur/compte') ?>" class="liste-notifs">
        <?= csrf_champ() ?>
        <input type="hidden" name="action" value="notifs">
        <?php foreach (NotifEmail::TYPES as $type => $t): ?>
            <label class="case">
                <input type="checkbox" name="notifs[]" value="<?= $type ?>" <?= !empty($u[$t['colonne']]) ? 'checked' : '' ?>>
                <span><strong><?= e($t['libelle']) ?></strong><br><span class="petit"><?= e(ucfirst($t['detail'])) ?></span></span>
            </label>
        <?php endforeach; ?>
        <button class="bouton bouton-petit">Enregistrer mes choix</button>
    </form>
</section>

<section class="carte">
    <h2>🗂️ Mes données</h2>
    <p class="petit">Tes droits RGPD : récupérer toutes tes données, ou supprimer ton compte. <a href="<?= url('page/confidentialite') ?>">En savoir plus</a></p>
    <div class="actions">
        <a href="<?= url('utilisateur/exporter') ?>" class="bouton bouton-secondaire">⬇️ Télécharger mes données</a>
    </div>
    <details class="zone-danger">
        <summary>Supprimer mon compte</summary>
        <p>Tes informations personnelles seront effacées et tes annonces retirées. <strong>C'est définitif.</strong></p>
        <form method="post" action="<?= url('utilisateur/supprimerCompte') ?>" data-confirmer="Supprimer définitivement ton compte ?">
            <?= csrf_champ() ?>
            <label>Tape <strong>SUPPRIMER</strong> pour confirmer <input type="text" name="confirmation" required autocomplete="off"></label>
            <label>Ton mot de passe <input type="password" name="mot_de_passe" required></label>
            <button type="submit" class="bouton bouton-danger">Supprimer définitivement mon compte</button>
        </form>
    </details>
</section>

<p class="petit">Compte : <?= e($u['pseudo']) ?> · <?= e($u['email']) ?> · inscrit le <?= date('d/m/Y', strtotime($u['date_inscription'])) ?></p>
