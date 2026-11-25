<?php $moi = utilisateur_connecte(); ?>
<section class="concept-intro">
    <span class="sur-titre">Le concept</span>
    <h1>Échanger vers le haut, <span class="degrade">c'est quoi ?</span></h1>
    <p class="hero-intro">Tu échanges un objet contre un autre qui t'intéresse un peu plus. Puis tu recommences avec ce que tu as reçu.
       Échange après échange, tu « montes » : c'est ta <strong>chaîne</strong>. Sur <?= e(NOM_SITE) ?>, tout se fait
       <strong>entre particuliers, sans argent</strong>, et c'est gratuit.</p>
</section>

<div class="concept-grille">
    <section class="carte concept-bloc">
        <h2>📎 Une histoire vraie</h2>
        <p>En 2005, un Canadien, Kyle MacDonald, a mis en ligne… un trombone rouge. Il l'a échangé contre un stylo, le stylo contre une poignée de porte,
           et ainsi de suite. Environ un an et 14 échanges plus tard, il avait obtenu une maison.</p>
        <p class="petit">On ne te promet pas une maison 😉 Mais l'idée est là : un objet qui dort dans un tiroir a souvent plus de valeur pour quelqu'un d'autre que pour toi.</p>
    </section>

    <section class="carte concept-bloc">
        <h2>🔁 Pourquoi les deux y gagnent</h2>
        <p>Dans un bon échange, <strong>chacun repart avec ce dont il a besoin</strong>. Ton vieux casque ne te sert plus ; pour quelqu'un, c'est exactement
           ce qu'il cherchait. Lui a une trottinette qu'il n'utilise jamais.</p>
        <p>« Monter d'un cran », ce n'est pas forcément plus cher : c'est un objet <strong>plus utile pour toi</strong>, plus rare,
           ou plus facile à échanger ensuite.</p>
    </section>
</div>

<h2 class="centre">Un exemple de chaîne</h2>
<div class="concept-chaine" aria-label="Exemple : trombone, stylo, casque audio, trottinette">
    <div class="etape-chaine"><span class="emoji">📎</span><span>Trombone</span><b>1 €</b></div>
    <span class="fleche-chaine" aria-hidden="true">→</span>
    <div class="etape-chaine"><span class="emoji">🖊️</span><span>Stylo design</span><b>25 €</b></div>
    <span class="fleche-chaine" aria-hidden="true">→</span>
    <div class="etape-chaine"><span class="emoji">🎧</span><span>Casque audio</span><b>60 €</b></div>
    <span class="fleche-chaine" aria-hidden="true">→</span>
    <div class="etape-chaine"><span class="emoji">🛴</span><span>Trottinette</span><b>180 €</b></div>
</div>
<p class="petit centre">Chaque étape est un échange accepté par les deux membres. Ta chaîne s'affiche sur ton profil.</p>

<section class="comment-ca-marche">
    <h2>Comment ça marche, étape par étape</h2>
    <div class="etapes etapes-4">
        <div><span>1</span><strong>Publie un objet</strong>
            <p class="petit">Une photo, un titre, la catégorie et une estimation honnête de sa valeur. Même un tout petit objet suffit pour commencer.</p></div>
        <div><span>2</span><strong>Trouve mieux</strong>
            <p class="petit">Cherche par catégorie, taille, département… Repère un objet qui te plaît et clique sur « Proposer un échange » en choisissant l'un de tes objets.</p></div>
        <div><span>3</span><strong>Discutez, puis échangez</strong>
            <p class="petit">L'autre membre accepte ou refuse. S'il accepte, vous convenez d'un rendez-vous par message et échangez en main propre.</p></div>
        <div><span>4</span><strong>Recommence</strong>
            <p class="petit">Laisse un avis, publie l'objet reçu… et vise encore un cran plus haut. Ta chaîne grandit à chaque échange.</p></div>
    </div>
</section>

<div class="concept-grille">
    <section class="carte concept-bloc" id="valeur">
        <h2>💶 Bien estimer la valeur</h2>
        <ul>
            <li>Regarde à <strong>quel prix l'objet se revend d'occasion</strong>, pas son prix neuf.</li>
            <li>Tiens compte de l'<strong>état</strong> : rayures, pièces manquantes, boîte d'origine…</li>
            <li>Reste <strong>honnête</strong> : une valeur gonflée fait fuir les propositions, et les avis le rappellent aux autres.</li>
            <li>La valeur sert de repère. Au final, c'est à vous deux de décider si l'échange vous convient.</li>
        </ul>
    </section>

    <section class="carte concept-bloc">
        <h2>📏 Les règles du jeu</h2>
        <ul>
            <li><strong>Pas d'argent</strong> entre membres : on échange des objets, rien d'autre.</li>
            <li>Des <strong>photos réelles</strong> et une description fidèle, défauts compris.</li>
            <li><strong>Objets interdits</strong> : armes, médicaments, drogues, contrefaçons, animaux, objets volés.</li>
            <li>Après chaque échange, <strong>un avis</strong> : c'est ce qui crée la confiance.</li>
        </ul>
        <p class="petit">Tout le détail est dans les <a href="<?= url('page/cgu') ?>">conditions d'utilisation</a>.</p>
    </section>
</div>

<section class="carte concept-bloc concept-securite">
    <h2>🛡️ Échanger en sécurité</h2>
    <div class="avantages">
        <div><span>📍</span><strong>Lieu public</strong><p class="petit">Rencontre-toi en journée, dans un endroit fréquenté (gare, centre commercial…).</p></div>
        <div><span>🔍</span><strong>Vérifie l'objet</strong><p class="petit">Allume l'appareil, essaie le vêtement, compare avec les photos avant d'échanger.</p></div>
        <div><span>🚫</span><strong>Jamais d'argent</strong><p class="petit">Ni virement, ni code, ni « frais d'envoi » demandés : c'est le signe d'une arnaque.</p></div>
        <div><span>🚩</span><strong>Signale</strong><p class="petit">Une annonce ou un membre suspect ? Utilise le bouton « Signaler », l'administrateur vérifie.</p></div>
    </div>
</section>

<section class="concept-faq">
    <h2 class="centre">Questions fréquentes</h2>
    <details class="carte">
        <summary>C'est vraiment gratuit ?</summary>
        <p>Oui. L'inscription, la publication d'annonces et les échanges sont gratuits, et aucun paiement ne passe par le site.</p>
    </details>
    <details class="carte">
        <summary>Je dois forcément échanger contre quelque chose de plus cher ?</summary>
        <p>Non. Le but est de « monter », mais chacun reste libre : tu peux accepter un échange parce que l'objet te plaît ou t'est utile, même s'il vaut un peu moins.</p>
    </details>
    <details class="carte">
        <summary>Et si l'objet ne correspond pas à l'annonce ?</summary>
        <p>C'est pour ça qu'on vérifie avant d'échanger, en main propre. Si tu as un souci avec un membre, laisse un avis honnête et signale-le si nécessaire.</p>
    </details>
    <details class="carte">
        <summary>Peut-on échanger à distance ?</summary>
        <p>Le site est pensé pour échanger près de chez soi (recherche par département). Si vous choisissez l'envoi postal, c'est entre vous et à vos risques :
           ne paie jamais rien à un membre.</p>
    </details>
    <details class="carte">
        <summary>Où je vois ma chaîne d'échanges ?</summary>
        <p>Sur ton profil : chaque échange accepté s'ajoute à ta chaîne, du premier objet au dernier reçu. Tu peux même la partager en image.</p>
    </details>
</section>

<section class="appel-final">
    <h2>Prêt à lancer ta chaîne ?</h2>
    <p>Choisis un objet qui ne te sert plus et publie-le en 1 minute.</p>
    <?php if ($moi): ?>
        <a href="<?= url('objet/creer') ?>" class="bouton bouton-grand bouton-blanc">+ Publier un objet</a>
    <?php else: ?>
        <a href="<?= url('utilisateur/inscription') ?>" class="bouton bouton-grand bouton-blanc">Créer mon compte — gratuit</a>
    <?php endif; ?>
</section>
