<article class="carte page-texte">
    <?php require __DIR__ . '/_avertissement.php'; ?>
    <h1>Politique de confidentialité</h1>
    <p>Cette page explique quelles données <?= e(NOM_SITE) ?> collecte, pourquoi, et quels sont tes droits (RGPD).</p>

    <h2>Responsable du traitement</h2>
    <p><?= e(EDITEUR_NOM) ?> — <?= e(EDITEUR_EMAIL) ?></p>

    <h2>Données collectées et utilisation</h2>
    <table class="tableau">
        <thead><tr><th>Donnée</th><th>Pourquoi</th></tr></thead>
        <tbody>
            <tr><td>Pseudo, email, mot de passe (chiffré)</td><td>Créer et sécuriser ton compte, t'envoyer les emails du service</td></tr>
            <tr><td>Code postal, ville</td><td>Montrer tes annonces aux membres proches et permettre la recherche par département</td></tr>
            <tr><td>Annonces, photos, messages, avis</td><td>Faire fonctionner les échanges entre membres</td></tr>
            <tr><td>Photo de profil, bio, réseaux sociaux (facultatifs)</td><td>Présenter ton profil aux autres membres</td></tr>
            <tr><td>Adresse IP lors des connexions ratées et des inscriptions</td><td>Protéger le site contre les robots et les tentatives de piratage (conservée 2 jours maximum)</td></tr>
        </tbody>
    </table>
    <p>Base légale : l'exécution du service que tu as demandé en t'inscrivant, et l'intérêt légitime pour la sécurité.
       <strong>Tes données ne sont ni vendues, ni louées, ni utilisées pour de la publicité ciblée.</strong></p>

    <h2>Qui voit quoi ?</h2>
    <p>Ton email et ton code postal ne sont jamais affichés. Ton pseudo, ta ville, ton département, tes annonces, ta chaîne
       d'échanges, tes avis et les infos facultatives de ton profil sont publics. Tes messages ne sont visibles que par
       les deux personnes de l'échange (et par l'administrateur en cas de signalement).</p>

    <h2>Cookies</h2>
    <p>Le site utilise un seul cookie, indispensable à la connexion (cookie de session). Aucun cookie publicitaire ni de mesure d'audience.</p>

    <h2>Durée de conservation</h2>
    <p>Tes données sont conservées tant que ton compte existe. Quand tu supprimes ton compte, tes informations personnelles
       sont effacées immédiatement ; tes annonces sont retirées et ton pseudo est remplacé par « membre supprimé » dans
       les échanges passés des autres membres.</p>

    <h2>Tes droits</h2>
    <p>Tu peux à tout moment, depuis <a href="<?= url('utilisateur/compte') ?>">Mon compte</a> :</p>
    <ul>
        <li><strong>consulter et modifier</strong> tes informations ;</li>
        <li><strong>télécharger toutes tes données</strong> (droit d'accès et de portabilité) ;</li>
        <li><strong>supprimer ton compte</strong> (droit à l'effacement).</li>
    </ul>
    <p>Pour toute autre demande : <?= e(EDITEUR_EMAIL) ?>. Tu peux aussi saisir la CNIL (cnil.fr).</p>
</article>
