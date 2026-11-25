<h1>❤️ Mes favoris</h1>
<?php if (!$objets): ?>
    <p class="vide">Aucun favori pour l'instant. Clique sur 🤍 sur une annonce pour la garder de côté.</p>
    <a href="<?= url('objet/recherche') ?>" class="bouton">Chercher des objets</a>
<?php else: ?>
    <p class="petit"><?= count($objets) ?> annonce<?= count($objets) > 1 ? 's' : '' ?> gardée<?= count($objets) > 1 ? 's' : '' ?> de côté.</p>
    <div class="grille">
        <?php foreach ($objets as $o): ?>
            <div class="favori-bloc">
                <?php require __DIR__ . '/_carte.php'; ?>
                <form method="post" action="<?= url('objet/favori') ?>">
                    <?= csrf_champ() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="retour" value="favoris">
                    <button type="submit" class="lien-bouton petit">Retirer des favoris</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
