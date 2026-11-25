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
                <select name="id_categorie" required>
                    <option value="">Choisir…</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int) $valeurs['id_categorie'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
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
            </label>
        </div>

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
