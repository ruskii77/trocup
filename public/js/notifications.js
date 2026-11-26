// Met à jour le badge de notifications sans recharger la page.
// Toutes les 30 secondes, on demande au serveur le nombre de notifications non lues.
(function () {
    const badge = document.getElementById('badge-notifs');
    if (!badge) return; // pas connecté : rien à faire
    // Le même chiffre est affiché à deux endroits : dans le menu et sur le bouton « Menu » (téléphone)
    const badges = document.querySelectorAll('.badge-notifs');

    const titreOrigine = document.title.replace(/^\(\d+\) /, '');

    function afficher(total) {
        badges.forEach(function (b) {
            b.textContent = total > 99 ? '99+' : total;
            b.hidden = total === 0;
        });
        // Le chiffre apparaît aussi dans l'onglet du navigateur : « (2) Visota »
        document.title = total > 0 ? '(' + total + ') ' + titreOrigine : titreOrigine;
    }

    async function verifier() {
        try {
            const reponse = await fetch('index.php?route=notification/compte', { credentials: 'same-origin' });
            if (!reponse.ok) return;
            const donnees = await reponse.json();
            afficher(donnees.total);
        } catch (e) {
            // pas de connexion internet ou serveur arrêté : on réessaiera au prochain tour
        }
    }

    afficher(parseInt(badge.dataset.total, 10) || 0);
    setInterval(verifier, 30000);
    // Quand on revient sur l'onglet, on vérifie tout de suite
    document.addEventListener('visibilitychange', () => { if (!document.hidden) verifier(); });
})();
