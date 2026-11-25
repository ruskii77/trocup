<?php
// Un petit formulaire d'action de la page (chaque bouton envoie une « action » à AdminController::categories)
$debutForm = fn(string $action, string $confirmer = ''): string =>
    '<form method="post" action="' . url('admin/categories') . '"' . ($confirmer ? ' data-confirmer="' . e($confirmer) . '"' : '') . '>'
    . csrf_champ() . '<input type="hidden" name="action" value="' . $action . '">';

// Cases à cocher de toutes les catégories (pour dire à quelles catégories un filtre s'applique)
$casesCategories = function (array $cochees) use ($arbre): string {
    $html = '<div class="cases-categories">';
    foreach ($arbre as $p) {
        $html .= '<div><label class="case-principale"><input type="checkbox" name="categories[]" value="' . $p['id'] . '"'
            . (in_array((int) $p['id'], $cochees, true) ? ' checked' : '') . '> ' . e($p['nom']) . ' <span class="petit">(et toutes ses sous-catégories)</span></label>';
        foreach ($p['enfants'] as $c) {
            $html .= '<label><input type="checkbox" name="categories[]" value="' . $c['id'] . '"'
                . (in_array((int) $c['id'], $cochees, true) ? ' checked' : '') . '> ' . e($c['nom']) . '</label>';
        }
        $html .= '</div>';
    }
    return $html . '</div>';
};
?>
<p class="petit"><a href="<?= url('admin/index') ?>">← Administration</a></p>
<h1>🗂️ Catégories et filtres</h1>
<p class="petit">Les sous-catégories rangent les annonces (Vêtements › T-shirts). Les filtres (taille, pointure…) sont demandés à la publication
   et proposés dans la recherche, seulement pour les catégories où ils s'appliquent.</p>

<h2>Catégories</h2>
<div class="admin-categories">
    <?php foreach ($arbre as $p): ?>
        <section class="carte">
            <h3><?= e($p['nom']) ?></h3>
            <?php foreach ($p['enfants'] as $c): ?>
                <div class="ligne-admin">
                    <?= $debutForm('renommer') ?>
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <input type="text" name="nom" value="<?= e($c['nom']) ?>" maxlength="50" required aria-label="Nom">
                        <button class="bouton bouton-secondaire bouton-petit">Renommer</button>
                    </form>
                    <?= $debutForm('supprimer_sous', 'Supprimer « ' . $c['nom'] . ' » ? Ses annonces passeront dans « ' . $p['nom'] . ' ».') ?>
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="bouton bouton-danger bouton-petit" title="Supprimer">✕</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?= $debutForm('ajouter_sous') ?>
                <input type="hidden" name="id_parent" value="<?= $p['id'] ?>">
                <div class="ligne-admin">
                    <input type="text" name="nom" maxlength="50" required placeholder="Nouvelle sous-catégorie" aria-label="Nouvelle sous-catégorie">
                    <button class="bouton bouton-petit">+ Ajouter</button>
                </div>
            </form>
        </section>
    <?php endforeach; ?>
</div>

<h2>Filtres</h2>
<?php foreach ($caracteristiques as $id => $c): ?>
    <details class="carte admin-filtre">
        <summary>
            <strong><?= e($c['nom']) ?></strong>
            <span class="petit"><?= $c['type'] === 'liste' ? count($c['options']) . ' choix' : 'texte libre' ?>
                · <?= $c['obligatoire'] ? 'obligatoire' : 'facultatif' ?>
                · <?= count($c['categories']) ?> catégorie(s)</span>
        </summary>

        <?php if ($c['type'] === 'liste'): ?>
            <p class="label-groupe">Choix possibles</p>
            <div class="puces-options">
                <?php foreach ($c['options'] as $idOption => $valeur): ?>
                    <?= $debutForm('supprimer_option', 'Supprimer « ' . $valeur . ' » ? Les annonces qui avaient ce choix le perdront.') ?>
                        <input type="hidden" name="id_option" value="<?= $idOption ?>">
                        <span class="puce"><?= e($valeur) ?> <button class="lien-bouton" title="Supprimer ce choix">✕</button></span>
                    </form>
                <?php endforeach; ?>
            </div>
            <?= $debutForm('ajouter_option') ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <div class="ligne-admin">
                    <input type="text" name="valeur" maxlength="50" required placeholder="Nouveau choix" aria-label="Nouveau choix">
                    <button class="bouton bouton-petit">+ Ajouter</button>
                </div>
            </form>
        <?php endif; ?>

        <?= $debutForm('modifier_carac') ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <p class="label-groupe">S'applique à</p>
            <?= $casesCategories($c['rattachees']) ?>
            <label class="case"><input type="checkbox" name="obligatoire" value="1" <?= $c['obligatoire'] ? 'checked' : '' ?>> Obligatoire à la publication</label>
            <button class="bouton bouton-secondaire bouton-petit">Enregistrer</button>
        </form>

        <?= $debutForm('supprimer_carac', 'Supprimer le filtre « ' . $c['nom'] . ' » ? Les valeurs déjà indiquées dans les annonces seront effacées.') ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <button class="bouton bouton-danger bouton-petit">Supprimer ce filtre</button>
        </form>
    </details>
<?php endforeach; ?>

<section class="carte formulaire">
    <h2>+ Nouveau filtre</h2>
    <?= $debutForm('ajouter_carac') ?>
        <div class="ligne">
            <label>Nom <input type="text" name="nom" maxlength="50" required placeholder="ex : Matière"></label>
            <label>Type
                <select name="type">
                    <option value="liste">Liste de choix</option>
                    <option value="texte">Texte libre</option>
                </select>
            </label>
        </div>
        <label>Choix possibles (pour une liste) — un par ligne ou séparés par des virgules
            <textarea name="options" rows="3" placeholder="Coton, Laine, Cuir, Synthétique"></textarea>
        </label>
        <p class="label-groupe">S'applique à</p>
        <?= $casesCategories([]) ?>
        <label class="case"><input type="checkbox" name="obligatoire" value="1"> Obligatoire à la publication</label>
        <button class="bouton">Créer le filtre</button>
    </form>
</section>
