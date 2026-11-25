<?php
// Construit l'adresse d'une page de résultats en gardant les filtres actuels
$lienPage = function (int $p) use ($filtres): string {
    $params = array_filter([
        'q' => $filtres['q'], 'categorie' => $filtres['categorie'], 'departement' => $filtres['departement'],
        'etat' => $filtres['etat'], 'valeur_min' => $filtres['valeur_min'], 'valeur_max' => $filtres['valeur_max'],
        'tri' => $filtres['tri'] !== 'recent' ? $filtres['tri'] : null,
    ], fn($v) => $v !== null && $v !== '');
    return url('objet/recherche', $params + ['page' => $p]);
};
$moi = utilisateur_connecte();
?>
<h1>Chercher un objet</h1>

<div class="page-recherche">
    <form method="get" class="carte panneau-filtres">
        <input type="hidden" name="route" value="objet/recherche">

        <label>Mot-clé
            <input type="search" name="q" value="<?= e($filtres['q']) ?>" placeholder="console, vélo…">
        </label>

        <label>Catégorie
            <select name="categorie">
                <option value="">Toutes</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filtres['categorie'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

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

    <section class="resultats">
        <p class="nombre-resultats">
            <strong><?= $total ?></strong> résultat<?= $total > 1 ? 's' : '' ?>
            <?php if ($filtres['departement'] !== ''): ?> · 📍 <?= e(nom_departement($filtres['departement'])) ?> (<?= e($filtres['departement']) ?>)<?php endif; ?>
        </p>

        <?php if (!$objets): ?>
            <p class="vide">Aucun objet ne correspond. Essaie d'élargir ta recherche (autre département, moins de filtres).</p>
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
