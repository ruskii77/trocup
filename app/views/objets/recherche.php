<?php
// Construit l'adresse d'une page de résultats en gardant les filtres actuels
$lienPage = function (int $p) use ($filtres): string {
    $params = array_filter([
        'q' => $filtres['q'], 'categorie' => $filtres['categorie'], 'departement' => $filtres['departement'],
        'etat' => $filtres['etat'], 'valeur_min' => $filtres['valeur_min'], 'valeur_max' => $filtres['valeur_max'],
        'tri' => $filtres['tri'] !== 'recent' ? $filtres['tri'] : null,
    ], fn($v) => $v !== null && $v !== '');
    // Les filtres propres à la catégorie : c[3]=12&c[8]=nike
    $caracs = array_map(fn($f) => $f['valeur'], $filtres['caracs']);
    return url('objet/recherche', $params + ($caracs ? ['c' => $caracs] : []) + ['page' => $p]);
};
$moi = utilisateur_connecte();
?>
<?php $seulDepartement = !$categorie && $filtres['departement'] !== '' && $filtres['q'] === ''; ?>
<?php if ($categorie): ?>
    <h1><?= e($categorie['nom']) ?> à échanger</h1>
<?php elseif ($seulDepartement): ?>
    <!-- Page « troc près de chez vous » d'un département (adresse troc/34-herault) -->
    <h1>Troc <?= e(nom_departement($filtres['departement'])) ?> (<?= e($filtres['departement']) ?>)</h1>
    <?php if ($page === 1): ?>
        <p class="intro-categorie">Échange d'objets entre particuliers près de chez vous<?= $villes ? ', à ' . e(implode(', ', $villes)) . '…' : '' ?>
            Repère une annonce qui te plaît et propose un échange : gratuit, sans argent, et en main propre.</p>
    <?php endif; ?>
<?php else: ?>
    <h1>Chercher un objet à échanger</h1>
<?php endif; ?>
<?php if ($categorie && !empty($categorie['description']) && $page === 1): ?>
    <!-- Texte de présentation : aide les visiteurs… et Google à comprendre la page -->
    <p class="intro-categorie"><?= e($categorie['description']) ?></p>
<?php endif; ?>

<?php if ($sousCategories): ?>
    <!-- Raccourcis : on affine d'un clic -->
    <nav class="puces-categories" aria-label="Sous-catégories">
        <?php foreach ($sousCategories as $sc): ?>
            <a href="<?= url('objet/recherche', array_filter(['categorie' => $sc['id'], 'departement' => $filtres['departement'], 'q' => $filtres['q']])) ?>" class="puce">
                <?= e($sc['nom']) ?> <span><?= (int) $sc['nombre'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<?php elseif ($categorie && $categorie['id_parent'] !== null): ?>
    <p class="petit"><a href="<?= url('objet/recherche', array_filter(['categorie' => $categorie['id_parent'], 'departement' => $filtres['departement'], 'q' => $filtres['q']])) ?>">← Toute la catégorie</a></p>
<?php endif; ?>

<div class="page-recherche">
    <?php
    // Nombre de filtres utilisés (affiché sur le bouton « Filtres » en version téléphone)
    $nbFiltres = count(array_filter([$filtres['q'], $filtres['departement'], $filtres['etat'], $filtres['valeur_min'], $filtres['valeur_max']], fn($v) => $v !== null && $v !== ''))
        + count($filtres['caracs']) + ($filtres['tri'] !== 'recent' ? 1 : 0);
    ?>
    <!-- Sur téléphone, js/site.js replie ce bloc pour montrer d'abord les annonces -->
    <details class="filtres-repliables" open <?= $nbFiltres ? 'data-actifs="1"' : '' ?>>
    <summary class="bouton bouton-secondaire resume-filtres">⚙️ Filtres<?= $nbFiltres ? ' (' . $nbFiltres . ' actif' . ($nbFiltres > 1 ? 's' : '') . ')' : '' ?></summary>
    <form method="get" action="<?= urls_propres() ? url('objet/recherche') : 'index.php' ?>" class="carte panneau-filtres">
        <?php if (!urls_propres()): ?><input type="hidden" name="route" value="objet/recherche"><?php endif; ?>

        <label>Mot-clé
            <input type="search" name="q" value="<?= e($filtres['q']) ?>" placeholder="console, vélo…">
        </label>

        <label>Catégorie
            <!-- data-envoi-auto : changer de catégorie recharge la page pour afficher ses filtres (taille, pointure…) -->
            <select name="categorie" data-envoi-auto>
                <option value="">Toutes</option>
                <?php foreach ($arbre as $p): ?>
                    <?php if ($p['enfants']): ?>
                        <optgroup label="<?= e($p['nom']) ?>">
                            <option value="<?= $p['id'] ?>" <?= $filtres['categorie'] === (int) $p['id'] ? 'selected' : '' ?>>Tout « <?= e($p['nom']) ?> »</option>
                            <?php foreach ($p['enfants'] as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $filtres['categorie'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php else: ?>
                        <option value="<?= $p['id'] ?>" <?= $filtres['categorie'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nom']) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if ($caracteristiques): ?>
            <fieldset class="filtres-categorie">
                <legend>Filtres <?= e($categorie['nom']) ?></legend>
                <?php foreach ($caracteristiques as $id => $c):
                    $choix = $filtres['caracs'][$id]['valeur'] ?? ''; ?>
                    <label><?= e($c['nom']) ?>
                        <?php if ($c['type'] === 'liste'): ?>
                            <select name="c[<?= $id ?>]">
                                <option value="">Tous</option>
                                <?php foreach ($c['options'] as $idOption => $libelle): ?>
                                    <option value="<?= $idOption ?>" <?= $choix === $idOption ? 'selected' : '' ?>><?= e($libelle) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="search" name="c[<?= $id ?>]" maxlength="<?= Caracteristique::LONGUEUR_TEXTE ?>" value="<?= e((string) $choix) ?>" placeholder="ex : Nike">
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>

        <label>Département
            <select name="departement">
                <option value="">Toute la France</option>
                <?php if ($moi): ?>
                    <option value="<?= e($moi['departement']) ?>">📍 Mon département (<?= e($moi['departement']) ?>)</option>
                <?php endif; ?>
                <?php foreach (departements() as $code => $nom): ?>
                    <option value="<?= e($code) ?>" <?= $filtres['departement'] === (string) $code ? 'selected' : '' ?>><?= e($code . ' – ' . $nom) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>État
            <select name="etat">
                <option value="">Tous</option>
                <?php foreach (['neuf', 'tres_bon', 'bon', 'use'] as $etat): ?>
                    <option value="<?= $etat ?>" <?= $filtres['etat'] === $etat ? 'selected' : '' ?>><?= libelle_etat($etat) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <fieldset class="fourchette">
            <legend>Valeur estimée (€)</legend>
            <input type="number" name="valeur_min" min="0" placeholder="Min" value="<?= e($filtres['valeur_min']) ?>" aria-label="Valeur minimum">
            <span>à</span>
            <input type="number" name="valeur_max" min="0" placeholder="Max" value="<?= e($filtres['valeur_max']) ?>" aria-label="Valeur maximum">
        </fieldset>

        <label>Trier par
            <select name="tri">
                <?php foreach (Objet::TRIS as $cle => [$libelle]): ?>
                    <option value="<?= $cle ?>" <?= $filtres['tri'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <button type="submit" class="bouton bouton-grand">🔍 Chercher</button>
        <a href="<?= url('objet/recherche') ?>" class="petit centre lien-reinit">Effacer les filtres</a>
    </form>
    </details>

    <section class="resultats">
        <p class="nombre-resultats">
            <strong><?= $total ?></strong> résultat<?= $total > 1 ? 's' : '' ?>
            <?php if ($filtres['departement'] !== ''): ?> · 📍 <?= e(nom_departement($filtres['departement'])) ?> (<?= e($filtres['departement']) ?>)<?php endif; ?>
        </p>

        <?php if (!$objets): ?>
            <p class="vide">Aucun objet ne correspond. Essaie d'élargir ta recherche (autre département, moins de filtres).
                <?php if (!empty($filtres['caracs'])): ?><br><span class="petit">Astuce : certaines annonces n'indiquent pas toutes les précisions (marque, couleur…). Retire un filtre pour les voir.</span><?php endif; ?></p>
        <?php else: ?>
            <div class="grille"><?php foreach ($objets as $o) require __DIR__ . '/_carte.php'; ?></div>
        <?php endif; ?>

        <?php if ($nbPages > 1): ?>
            <nav class="pagination" aria-label="Pages de résultats">
                <?php if ($page > 1): ?><a href="<?= $lienPage($page - 1) ?>">← Précédent</a><?php endif; ?>
                <?php for ($p = max(1, $page - 2); $p <= min($nbPages, $page + 2); $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="page-actuelle"><?= $p ?></span>
                    <?php else: ?>
                        <a href="<?= $lienPage($p) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $nbPages): ?><a href="<?= $lienPage($page + 1) ?>">Suivant →</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</div>
