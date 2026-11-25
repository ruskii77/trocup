<section class="carte formulaire">
    <h1><?= $modification ? 'Modifier mon objet' : 'Publier un objet' ?></h1>

    <?php foreach ($erreurs as $err): ?>
        <div class="alerte alerte-erreur"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data"
          action="<?= $modification ? url('objet/modifier', ['id' => $valeurs['id']]) : url('objet/creer') ?>">
        <?= csrf_champ() ?>

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

        <label>Photo principale (JPG, PNG ou WEBP)
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        </label>
        <?php if ($modification && !empty($valeurs['photo'])): ?>
            <p class="petit">Photo actuelle : <img src="<?= photo_objet($valeurs['photo']) ?>" alt="" class="miniature"> (laisse vide pour la garder)</p>
        <?php endif; ?>

        <?php $place = Objet::MAX_PHOTOS_SUP - count($photosSup); ?>
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
        <?php if ($place > 0): ?>
            <label>Ajouter jusqu'à <?= $place ?> photo<?= $place > 1 ? 's' : '' ?> de plus (autres angles, détails, défauts…)
                <input type="file" name="photos_sup[]" accept="image/jpeg,image/png,image/webp" multiple>
            </label>
        <?php endif; ?>

        <button type="submit" class="bouton bouton-grand"><?= $modification ? 'Enregistrer' : 'Mettre en ligne' ?></button>
    </form>
</section>
