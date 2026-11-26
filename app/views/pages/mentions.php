<article class="carte page-texte">
    <?php require __DIR__ . '/_avertissement.php'; ?>
    <h1>Mentions légales</h1>

    <h2>Éditeur du site</h2>
    <p><?= e(NOM_SITE) ?> est un site édité à titre personnel par <strong><?= e(EDITEUR_NOM) ?></strong>, <?= e(EDITEUR_VILLE) ?>.<br>
       Contact : <?= e(EDITEUR_EMAIL) ?><br>
       Directeur de la publication : <?= e(EDITEUR_NOM) ?>.</p>

    <h2>Hébergement</h2>
    <p><?= e(HEBERGEUR_NOM) ?><br><?= e(HEBERGEUR_ADRESSE) ?></p>

    <h2>Contenus publiés par les membres</h2>
    <p>Les annonces, photos, messages et avis sont publiés sous la responsabilité de leurs auteurs.
       <?= e(NOM_SITE) ?> agit en tant qu'hébergeur de ces contenus : tout contenu manifestement illicite peut être signalé
       grâce au bouton « Signaler » ou à l'adresse <?= e(EDITEUR_EMAIL) ?>, et sera retiré dans les meilleurs délais.</p>

    <h2>Points de contact (règlement européen sur les services numériques — DSA)</h2>
    <p>Pour les membres, les autorités et toute personne souhaitant signaler un contenu illicite :
       <strong><?= e(EDITEUR_EMAIL) ?></strong> (réponse en français ou en anglais).
       Les annonces et les membres peuvent aussi être signalés directement grâce aux boutons « Signaler » du site.</p>

    <h2>Propriété intellectuelle</h2>
    <p>Le nom <?= e(NOM_SITE) ?>, le logo et la présentation du site sont la propriété de l'éditeur.
       Les membres restent propriétaires des photos et textes qu'ils publient.</p>

    <h2>Données et logiciels utilisés</h2>
    <p class="petit">Liste des communes : INSEE / Etalab (Licence Ouverte). Coordonnées GPS des communes :
       <a href="https://www.geonames.org" rel="noopener">GeoNames</a> (licence CC BY 4.0).
       Décodage des photos HEIC : libheif-js (licence LGPL-3.0).</p>

    <p class="petit">Voir aussi : <a href="<?= url('page/cgu') ?>">Conditions d'utilisation</a> ·
       <a href="<?= url('page/confidentialite') ?>">Politique de confidentialité</a></p>
</article>
