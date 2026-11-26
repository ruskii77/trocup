<article class="carte page-texte">
    <?php require __DIR__ . '/_avertissement.php'; ?>
    <h1>Politique de confidentialité</h1>
    <p class="petit">Dernière mise à jour : <?= date('d/m/Y', filemtime(__FILE__)) ?></p>
    <p>Cette page explique quelles données <?= e(NOM_SITE) ?> collecte, pourquoi, combien de temps, et quels sont tes droits (RGPD).</p>

    <h2>Responsable du traitement</h2>
    <p><?= e(EDITEUR_NOM) ?> — <?= e(EDITEUR_EMAIL) ?>. Le site est hébergé en France par <?= e(HEBERGEUR_NOM) ?>.</p>

    <h2>Données collectées, utilisation et durée de conservation</h2>
    <table class="tableau">
        <thead><tr><th>Donnée</th><th>Pourquoi</th><th>Conservée</th></tr></thead>
        <tbody>
            <tr><td>Pseudo, email, mot de passe (chiffré, illisible même pour nous)</td><td>Créer et sécuriser ton compte, t'envoyer les emails du service</td><td>Tant que ton compte existe</td></tr>
            <tr><td>Code postal, ville (et le point GPS du centre de ta commune, jamais ton adresse)</td><td>Montrer tes annonces aux membres proches, recherche par département ou dans un rayon en km</td><td>Tant que ton compte existe</td></tr>
            <tr><td>Annonces, photos, messages, avis</td><td>Faire fonctionner les échanges entre membres</td><td>Tant que ton compte existe</td></tr>
            <tr><td>Photo de profil, bio, réseaux sociaux (facultatifs)</td><td>Présenter ton profil aux autres membres</td><td>Jusqu'à ce que tu les retires</td></tr>
            <tr><td>Date de dernière connexion</td><td>Supprimer les comptes abandonnés</td><td>Tant que ton compte existe</td></tr>
            <tr><td>Adresse IP lors des connexions ratées et des inscriptions</td><td>Protéger le site contre les robots et le piratage</td><td><?= Maintenance::DUREES['tentatives_connexion'] ?></td></tr>
            <tr><td>Compte jamais confirmé par email</td><td>—</td><td>Supprimé après <?= Maintenance::DUREES['compte_non_confirme'] ?></td></tr>
            <tr><td>Compte sans connexion pendant <?= Maintenance::DUREES['compte_inactif'] ?></td><td>—</td><td>Anonymisé automatiquement</td></tr>
        </tbody>
    </table>
    <p>Base légale : l'exécution du service que tu as demandé en t'inscrivant (et l'acceptation des conditions d'utilisation),
       l'intérêt légitime pour la sécurité, et ton consentement pour les cookies non nécessaires.
       <strong>Tes données ne sont ni vendues, ni louées, ni utilisées pour de la publicité ciblée.</strong>
       Les photos envoyées sont réenregistrées par le site, ce qui efface leurs métadonnées (dont la position GPS que les téléphones ajoutent parfois).</p>

    <h2>Qui voit quoi ?</h2>
    <p>Le bouton « 📍 Ma position » de la recherche demande ta position à ton navigateur (tu peux refuser). Elle sert seulement
       à trouver la ville la plus proche pour cette recherche : elle n'est <strong>jamais enregistrée</strong>. Les distances
       affichées (« à 12 km ») sont calculées entre les centres des communes, pas entre des adresses.</p>
    <p>Ton email et ton code postal ne sont jamais affichés. Ton pseudo, ta ville, ton département, tes annonces, ta chaîne
       d'échanges, tes avis et les infos facultatives de ton profil sont publics. Tes messages ne sont visibles que par
       les deux personnes de l'échange. Seuls l'éditeur et l'hébergeur (<?= e(HEBERGEUR_NOM) ?>, en France) ont accès aux données techniques.</p>

    <h2 id="cookies">Cookies</h2>
    <p>Les cookies « nécessaires » sont déposés sans consentement car le site ne peut pas fonctionner sans eux.
       Les autres catégories ne sont activées <strong>que si tu les acceptes</strong> ; ne rien choisir équivaut à refuser.
       Ton choix est conservé 6 mois, puis on te le redemande. Tu peux le modifier à tout moment :
       <a href="#cookies" data-ouvrir-cookies>Gérer mes cookies</a>.</p>
    <table class="tableau">
        <thead><tr><th>Catégorie</th><th>Cookie / service</th><th>Utilité</th><th>Durée</th></tr></thead>
        <tbody>
        <?php foreach (Cookies::categories() as $cat): ?>
            <?php if (!$cat['services']): ?>
                <tr><td><?= e($cat['nom']) ?></td><td colspan="3"><em>Aucun service de ce type n'est utilisé actuellement.</em></td></tr>
            <?php endif; ?>
            <?php foreach ($cat['services'] as [$nom, $utilite, $duree]): ?>
                <tr><td><?= e($cat['nom']) ?></td><td><code><?= e($nom) ?></code></td><td><?= e($utilite) ?></td><td><?= e($duree) ?></td></tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="petit">Pour prouver ton choix si besoin, nous gardons une trace anonyme (un identifiant au hasard, les catégories acceptées, la date)
       pendant <?= Maintenance::DUREES['preuve_cookies'] ?>, sans adresse IP.</p>

    <h2>Sécurité</h2>
    <p>Connexion chiffrée (HTTPS), mots de passe chiffrés, blocage après plusieurs tentatives de connexion ratées,
       protections contre les attaques courantes des sites web. En cas de fuite de données présentant un risque pour toi,
       nous préviendrons la CNIL sous 72 heures et, si nécessaire, les membres concernés.</p>

    <h2>Tes droits</h2>
    <p>Tu peux à tout moment, depuis <a href="<?= url('utilisateur/compte') ?>">Mon compte</a> :</p>
    <ul>
        <li><strong>consulter et modifier</strong> tes informations ;</li>
        <li><strong>télécharger toutes tes données</strong> (droit d'accès et de portabilité) ;</li>
        <li><strong>supprimer ton compte</strong> (droit à l'effacement) ;</li>
        <li><strong>retirer ton consentement aux cookies</strong> (lien « Gérer mes cookies »).</li>
    </ul>
    <p>Pour toute autre demande (opposition, limitation, question) : <?= e(EDITEUR_EMAIL) ?>. Réponse sous un mois maximum.
       Si tu estimes que tes droits ne sont pas respectés, tu peux adresser une réclamation à la CNIL (cnil.fr).</p>
</article>
