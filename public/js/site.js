// =====================================================
//  Petits comportements du site, réunis dans UN fichier.
//  Pourquoi pas directement dans le HTML (onclick="…") ? Parce que la politique de sécurité
//  du site (Content-Security-Policy) interdit le JavaScript écrit dans les pages :
//  ça bloque une grande partie des attaques XSS.
// =====================================================
(function () {
    // Formulaires qui demandent une confirmation : <form data-confirmer="Supprimer ?">
    document.querySelectorAll('form[data-confirmer]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirmer)) e.preventDefault();
        });
    });

    // Champs qui envoient le formulaire dès qu'on les change : <input data-envoi-auto>
    document.querySelectorAll('[data-envoi-auto]').forEach(function (champ) {
        champ.addEventListener('change', function () { champ.form.submit(); });
    });

    // Compteur de caractères : <textarea data-compteur="300"> suivi d'un élément qui affiche « x / 300 »
    document.querySelectorAll('[data-compteur]').forEach(function (champ) {
        const afficheur = champ.nextElementSibling;
        const maj = function () { afficheur.textContent = champ.value.length + ' / ' + champ.dataset.compteur; };
        champ.addEventListener('input', maj);
    });

    // Formulaire d'annonce : n'affiche que les précisions (taille, pointure…) de la catégorie choisie.
    // Un champ caché est aussi désactivé : il n'est pas envoyé et ne bloque pas l'envoi s'il est obligatoire.
    const pilote = document.querySelector('[data-pilote-caracs]');
    const blocCaracs = document.getElementById('caracteristiques');
    if (pilote && blocCaracs) {
        const majCaracs = function () {
            let visibles = 0;
            blocCaracs.querySelectorAll('[data-categories]').forEach(function (champ) {
                const concerne = pilote.value !== '' && champ.dataset.categories.split(',').includes(pilote.value);
                champ.hidden = !concerne;
                champ.querySelectorAll('select, input').forEach(function (el) { el.disabled = !concerne; });
                if (concerne) visibles++;
            });
            blocCaracs.hidden = visibles === 0;
        };
        pilote.addEventListener('change', majCaracs);
        majCaracs();
    }

    // Galerie photo d'une annonce : cliquer sur une miniature l'affiche en grand
    document.querySelectorAll('.miniature-bouton').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            document.getElementById('photo-principale').src = bouton.dataset.src;
            document.querySelectorAll('.miniature-bouton').forEach(b => b.classList.remove('active'));
            bouton.classList.add('active');
        });
    });
})();
