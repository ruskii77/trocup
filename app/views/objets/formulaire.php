<section class="carte formulaire">
    <h1><?= $modification ? 'Modifier mon objet' : 'Publier un objet' ?></h1>

    <?php foreach ($erreurs as $err): ?>
        <div class="alerte alerte-erreur"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data"
          action="<?= $modification ? url('objet/modifier', ['id' => $valeurs['id']]) : url('objet/creer') ?>">
        <?= csrf_champ() ?>
        <?php
        $maxPhotos = 1 + Objet::MAX_PHOTOS_SUP;
        $photoActuelle = $modification && !empty($valeurs['photo']) ? $valeurs['photo'] : null;
        $place = Objet::MAX_PHOTOS_SUP - count($photosSup);
        ?>
        <!-- 📷 Photos : version confortable (vignettes, ✕, ★). Cachée sans JavaScript : ce sont alors les champs classiques plus bas qui servent. -->
        <fieldset class="photos-annonce" data-photos-annonce data-max="<?= $maxPhotos ?>" hidden>
            <legend>📷 Photos <span class="petit">— jusqu'à <?= $maxPhotos ?>. Une annonce avec de belles photos reçoit beaucoup plus de propositions !</span></legend>
            <div class="grille-photos" data-grille-photos>
                <?php if ($photoActuelle): ?>
                    <div class="tuile-photo est-principale" data-existante="principale">
                        <img src="<?= photo_objet($photoActuelle) ?>" alt="">
                        <button type="button" class="tuile-etoile">★ Principale</button>
                    </div>
                <?php endif; ?>
                <?php foreach ($photosSup as $ph): ?>
                    <div class="tuile-photo" data-existante="sup" data-id="<?= $ph['id'] ?>">
                        <img src="uploads/<?= e($ph['fichier']) ?>" alt="">
                        <button type="button" class="tuile-retirer" aria-label="Retirer cette photo">✕</button>
                    </div>
                <?php endforeach; ?>
                <label class="ajout-photo" data-ajout-photo tabindex="0" role="button">
                    <span class="ajout-photo-icone" aria-hidden="true">📷</span>
                    <strong>Ajouter des photos</strong>
                    <span class="petit">Galerie ou appareil photo<br>JPG, PNG, WEBP ou HEIC (iPhone)</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" multiple data-choix-photos class="cache-visuel">
                </label>
            </div>
            <p class="petit compteur-photos" data-compteur-photos></p>
        </fieldset>


        <label>Titre
            <input type="text" name="titre" maxlength="100" required value="<?= e($valeurs['titre']) ?>" placeholder="Ex : Trombone rouge géant">
        </label>

        <label>Description
            <textarea name="description" rows="5" required placeholder="Décris ton objet, son histoire, ce que tu aimerais en échange…"><?= e($valeurs['description']) ?></textarea>
        </label>

        <div class="ligne">
            <label>Catégorie
                <select name="id_categorie" required data-pilote-caracs>
                    <option value="">Choisir…</option>
                    <?php foreach ($arbre as $p): ?>
                        <?php if ($p['enfants']): ?>
                            <optgroup label="<?= e($p['nom']) ?>">
                                <?php foreach ($p['enfants'] as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= (int) $valeurs['id_categorie'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                                <?php endforeach; ?>
                                <option value="<?= $p['id'] ?>" <?= (int) $valeurs['id_categorie'] === (int) $p['id'] ? 'selected' : '' ?>>Autre (<?= e(mb_strtolower($p['nom'])) ?>)</option>
                            </optgroup>
                        <?php else: ?>
                            <option value="<?= $p['id'] ?>" <?= (int) $valeurs['id_categorie'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nom']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>État
                <select name="etat">
                    <?php foreach (['neuf', 'tres_bon', 'bon', 'use'] as $etat): ?>
                        <option value="<?= $etat ?>" <?= $valeurs['etat'] === $etat ? 'selected' : '' ?>><?= libelle_etat($etat) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Valeur estimée (€)
                <input type="number" name="valeur_estimee" min="0" step="1" value="<?= e($valeurs['valeur_estimee']) ?>">
                <a href="<?= url('page/concept') ?>#valeur" class="petit" target="_blank">Comment l'estimer ?</a>
            </label>
        </div>

        <?php
        // Précisions selon la catégorie (taille, pointure, plateforme…).
        // Toutes sont dans la page ; js/site.js n'affiche que celles de la catégorie choisie.
        $categorieChoisie = (int) $valeurs['id_categorie'];
        $nbVisibles = count(array_filter($caracteristiques, fn($c) => in_array($categorieChoisie, $c['categories'], true)));
        ?>
        <fieldset class="caracteristiques" id="caracteristiques" <?= $nbVisibles ? '' : 'hidden' ?>>
            <legend>Précisions <span class="petit">— elles aident les autres à trouver ton annonce avec les filtres</span></legend>
            <div class="grille-caracs">
                <?php foreach ($caracteristiques as $id => $c):
                    $visible = in_array($categorieChoisie, $c['categories'], true);
                    $val = $valeurs['c'][$id] ?? ''; ?>
                    <label data-categories="<?= implode(',', $c['categories']) ?>" <?= $visible ? '' : 'hidden' ?>>
                        <?= e($c['nom']) ?><?= $c['obligatoire'] ? '' : ' <span class="petit">(facultatif)</span>' ?>
                        <?php if ($c['type'] === 'liste'): ?>
                            <select name="c[<?= $id ?>]" <?= $c['obligatoire'] ? 'required' : '' ?> <?= $visible ? '' : 'disabled' ?>>
                                <option value="">Choisir…</option>
                                <?php foreach ($c['options'] as $idOption => $libelle): ?>
                                    <option value="<?= $idOption ?>" <?= $val === $idOption ? 'selected' : '' ?>><?= e($libelle) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="c[<?= $id ?>]" maxlength="<?= Caracteristique::LONGUEUR_TEXTE ?>" value="<?= e(is_string($val) ? $val : '') ?>"
                                   <?= $c['obligatoire'] ? 'required' : '' ?> <?= $visible ? '' : 'disabled' ?>>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- Champs classiques (utilisés tels quels si JavaScript est désactivé, remplis par js/site.js sinon) -->
        <div class="champs-photos-classiques" data-champs-classiques>
            <label>Photo principale (JPG, PNG, WEBP ou HEIC d'iPhone, <?= Image::tailleMaxMo() ?> Mo max.)
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif">
            </label>
            <?php if ($photoActuelle): ?>
                <p class="petit">Photo actuelle : <img src="<?= photo_objet($photoActuelle) ?>" alt="" class="miniature"> (laisse vide pour la garder)</p>
            <?php endif; ?>
            <?php if ($photosSup): ?>
                <p class="label-groupe">Photos supplémentaires</p>
                <div class="photos-existantes">
                    <?php foreach ($photosSup as $ph): ?>
                        <label class="photo-existante">
                            <img src="uploads/<?= e($ph['fichier']) ?>" alt="">
                            <span><input type="checkbox" name="supprimer_photos[]" value="<?= $ph['id'] ?>"> supprimer</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <label>Photos supplémentaires (jusqu'à <?= max(0, $place) ?> de plus : autres angles, détails, défauts…)
                <input type="file" name="photos_sup[]" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif" multiple>
            </label>
        </div>

        <button type="submit" class="bouton bouton-grand"><?= $modification ? 'Enregistrer' : 'Mettre en ligne' ?></button>
    </form>
</section>
