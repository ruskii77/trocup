<?php
// Bandeau de consentement aux cookies (affiché par public/js/cookies.js tant que le visiteur n'a pas choisi)
$categoriesCookies = Cookies::categories();
?>
<div id="bandeau-cookies" class="bandeau-cookies" role="dialog" aria-modal="false" aria-labelledby="cookies-titre"
     data-nom="<?= Cookies::NOM ?>" data-duree="<?= Cookies::DUREE ?>" data-version="<?= Cookies::VERSION ?>"
     data-journal="<?= url('cookies/journal') ?>" hidden>
    <div class="bandeau-cookies-boite">
        <h2 id="cookies-titre">🍪 Tes choix sur les cookies</h2>
        <p>Nous utilisons des cookies <strong>nécessaires</strong> au fonctionnement du site.
           Avec ton accord, nous pourrions aussi en utiliser pour <strong>mesurer l'audience</strong>
           et afficher des <strong>contenus externes</strong> (vidéos, réseaux sociaux).
           Tu peux changer d'avis à tout moment avec le lien « Gérer mes cookies » en bas de page.
           <a href="<?= url('page/confidentialite') ?>#cookies">En savoir plus</a></p>

        <!-- Les détails, cachés au départ (« Personnaliser ») -->
        <div class="cookies-details" id="cookies-details" hidden>
            <?php foreach ($categoriesCookies as $cle => $cat): ?>
                <div class="cookie-categorie">
                    <label class="interrupteur">
                        <input type="checkbox" data-categorie="<?= $cle ?>" <?= $cat['obligatoire'] ? 'checked disabled' : '' ?>>
                        <span class="interrupteur-curseur"></span>
                        <strong><?= e($cat['nom']) ?></strong>
                        <?php if ($cat['obligatoire']): ?><span class="petit">(toujours actifs)</span><?php endif; ?>
                    </label>
                    <p class="petit"><?= e($cat['description']) ?>
                        <?php if (!$cat['services'] && !$cat['obligatoire']): ?><em>Aucun service de ce type n'est utilisé pour le moment.</em><?php endif; ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Refuser et accepter : même taille, même niveau (exigence CNIL) -->
        <div class="cookies-boutons">
            <button type="button" class="bouton bouton-cookie" data-cookies="refuser">Tout refuser</button>
            <button type="button" class="bouton bouton-cookie" data-cookies="accepter">Tout accepter</button>
            <button type="button" class="lien-bouton" data-cookies="personnaliser" id="cookies-personnaliser">Personnaliser</button>
            <button type="button" class="bouton bouton-secondaire" data-cookies="enregistrer" hidden>Enregistrer mes choix</button>
        </div>
    </div>
</div>
