<article class="carte page-texte">
    <?php require __DIR__ . '/_avertissement.php'; ?>
    <h1>Conditions générales d'utilisation</h1>

    <h2>1. Le service</h2>
    <p><?= e(NOM_SITE) ?> est une plateforme gratuite qui met en relation des particuliers souhaitant échanger des objets.
       Aucun paiement ne transite par le site et aucun argent ne doit être échangé entre membres via <?= e(NOM_SITE) ?>.</p>

    <h2>2. Inscription</h2>
    <p>L'inscription est réservée aux personnes majeures. Tu t'engages à fournir des informations exactes et à garder ton mot de passe secret.
       Un seul compte par personne.</p>

    <h2>3. Ce qui est interdit</h2>
    <ul>
        <li>Proposer des objets illégaux, dangereux ou réglementés : armes, médicaments, drogues, alcool, tabac, contrefaçons, objets volés, animaux.</li>
        <li>Publier des contenus choquants, haineux, à caractère sexuel ou portant atteinte aux droits d'autrui.</li>
        <li>Utiliser le site pour vendre, faire de la publicité ou envoyer des messages non sollicités.</li>
        <li>Se faire passer pour quelqu'un d'autre, ou harceler d'autres membres.</li>
    </ul>

    <h2>4. Les échanges</h2>
    <p>Les échanges se font directement entre membres, sous leur seule responsabilité. <?= e(NOM_SITE) ?> ne vérifie pas
       les objets et ne garantit ni leur état, ni leur valeur, ni le bon déroulement de l'échange.
       Conseils : rencontrez-vous dans un lieu public, vérifiez l'objet avant d'échanger, ne communiquez jamais vos coordonnées bancaires.</p>

    <h2>5. Signalements et modération</h2>
    <p>Tout membre peut signaler une annonce ou un membre avec les boutons « Signaler » ; toute personne peut aussi
       écrire à <?= e(EDITEUR_EMAIL) ?>. Les signalements sont examinés par l'administrateur du site (un humain, pas un algorithme).</p>
    <p>L'administrateur peut retirer une annonce ou suspendre un compte qui ne respecte pas ces conditions.
       Dans ce cas, le membre concerné est <strong>prévenu par email</strong> avec le motif de la décision,
       et peut la <strong>contester</strong> en répondant à <?= e(EDITEUR_EMAIL) ?> ; sa demande sera réexaminée.</p>
    <p>Les annonces sont affichées par date de publication (ou selon le tri choisi par le visiteur : valeur, département, catégorie).
       Aucune annonce n'est mise en avant contre rémunération.</p>

    <h2>6. Responsabilité</h2>
    <p>Le site est fourni « en l'état », gratuitement. L'éditeur fait de son mieux pour qu'il fonctionne correctement,
       mais ne peut être tenu responsable des litiges entre membres ni d'une interruption du service.</p>

    <h2>7. Données personnelles</h2>
    <p>Voir la <a href="<?= url('page/confidentialite') ?>">politique de confidentialité</a>.</p>

    <h2>8. Modification des conditions</h2>
    <p>Ces conditions peuvent évoluer. La version en vigueur est toujours celle affichée sur cette page.
       Elles sont soumises au droit français.</p>
</article>
