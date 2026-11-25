// =====================================================
//  Gestionnaire de consentement aux cookies
//
//  Pour ajouter un service soumis à consentement (ex. statistiques), écris dans une vue :
//    <script type="text/plain" data-consentement="mesure" data-src="https://exemple.com/stats.js"></script>
//  Ce script ne sera chargé QUE si le visiteur a accepté la catégorie « mesure ».
//  (Pense aussi à l'autoriser dans la Content-Security-Policy de app/core/Securite.php.)
// =====================================================
(function () {
    const bandeau = document.getElementById('bandeau-cookies');
    if (!bandeau) return;

    const NOM = bandeau.dataset.nom;
    const DUREE = parseInt(bandeau.dataset.duree, 10);
    const VERSION = parseInt(bandeau.dataset.version, 10);
    const cases = bandeau.querySelectorAll('input[data-categorie]:not([disabled])');
    const details = document.getElementById('cookies-details');
    const boutonEnregistrer = bandeau.querySelector('[data-cookies="enregistrer"]');
    const boutonPersonnaliser = bandeau.querySelector('[data-cookies="personnaliser"]');

    function lire() {
        const ligne = document.cookie.split('; ').find(c => c.startsWith(NOM + '='));
        if (!ligne) return null;
        try {
            const choix = JSON.parse(decodeURIComponent(ligne.substring(NOM.length + 1)));
            return choix.v === VERSION ? choix : null;
        } catch (e) { return null; }
    }

    function enregistrer(choix) {
        choix.v = VERSION;
        choix.d = new Date().toISOString(); // date du choix (preuve du consentement)
        choix.id = (lire() && lire().id) || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random());
        const securise = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = NOM + '=' + encodeURIComponent(JSON.stringify(choix)) +
            '; Max-Age=' + DUREE + '; Path=/; SameSite=Lax' + securise;

        // Trace anonyme du choix côté serveur, pour pouvoir prouver le consentement si besoin
        fetch(bandeau.dataset.journal, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(choix)
        }).catch(function () {});

        fermer();
        activerScripts(choix);
        // Si un service était déjà chargé et qu'on le refuse maintenant, il faut recharger la page pour l'arrêter
        if (window.__cookiesActifs && Object.keys(window.__cookiesActifs).some(c => window.__cookiesActifs[c] && !choix[c])) {
            location.reload();
        }
    }

    function choixDepuisCases(valeurParDefaut) {
        const choix = {};
        cases.forEach(function (c) { choix[c.dataset.categorie] = valeurParDefaut === undefined ? c.checked : valeurParDefaut; });
        return choix;
    }

    // Charge les scripts en attente dont la catégorie a été acceptée
    function activerScripts(choix) {
        window.__cookiesActifs = window.__cookiesActifs || {};
        document.querySelectorAll('script[type="text/plain"][data-consentement]').forEach(function (ancien) {
            const cat = ancien.dataset.consentement;
            if (!choix[cat] || ancien.dataset.active) return;
            const nouveau = document.createElement('script');
            if (ancien.dataset.src) nouveau.src = ancien.dataset.src;
            ancien.dataset.active = '1';
            ancien.after(nouveau);
            window.__cookiesActifs[cat] = true;
        });
    }

    function ouvrir(avecDetails) {
        const choix = lire();
        cases.forEach(function (c) { c.checked = !!(choix && choix[c.dataset.categorie]); });
        details.hidden = !avecDetails;
        boutonEnregistrer.hidden = !avecDetails;
        boutonPersonnaliser.hidden = !!avecDetails;
        bandeau.hidden = false;
    }

    function fermer() { bandeau.hidden = true; }

    bandeau.addEventListener('click', function (e) {
        const action = e.target.dataset.cookies;
        if (action === 'accepter') enregistrer(choixDepuisCases(true));
        if (action === 'refuser') enregistrer(choixDepuisCases(false));
        if (action === 'personnaliser') ouvrir(true);
        if (action === 'enregistrer') enregistrer(choixDepuisCases());
    });

    // Lien « Gérer mes cookies » du pied de page
    document.querySelectorAll('[data-ouvrir-cookies]').forEach(function (lien) {
        lien.addEventListener('click', function (e) { e.preventDefault(); ouvrir(true); });
    });

    // Au chargement : pas encore de choix → on affiche le bandeau ; sinon on applique le choix
    const choix = lire();
    if (choix) activerScripts(choix); else ouvrir(false);
})();
