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
        champ.addEventListener('change', function () {
            // Photo iPhone (HEIC) : on attend la fin de la conversion (voir plus bas), qui enverra elle-même le formulaire
            if (champ.files && Array.from(champ.files).some(function (f) { return /\.(heic|heif)$/i.test(f.name) || /image\/hei[cf]/i.test(f.type); })) return;
            champ.form.submit();
        });
    });

    // Compteur de caractères : <textarea data-compteur="300"> suivi d'un élément qui affiche « x / 300 »
    document.querySelectorAll('[data-compteur]').forEach(function (champ) {
        const afficheur = champ.nextElementSibling;
        const maj = function () { afficheur.textContent = champ.value.length + ' / ' + champ.dataset.compteur; };
        champ.addEventListener('input', maj);
    });

    // Menu téléphone : se ferme quand on touche ailleurs ou qu'on appuie sur Échap ; Entrée/Espace l'ouvre au clavier
    const bascule = document.getElementById('menu-bascule');
    if (bascule) {
        const boutonMenu = document.querySelector('.bouton-menu');
        const majAria = function () { boutonMenu.setAttribute('aria-expanded', bascule.checked ? 'true' : 'false'); };
        bascule.addEventListener('change', majAria);
        majAria();
        boutonMenu.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bascule.checked = !bascule.checked; majAria(); }
        });
        document.addEventListener('click', function (e) {
            if (bascule.checked && !e.target.closest('.entete')) { bascule.checked = false; majAria(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && bascule.checked) { bascule.checked = false; majAria(); boutonMenu.focus(); }
        });
    }

    // Sur téléphone, la page Chercher replie ses filtres pour montrer tout de suite les annonces
    const filtresMobile = document.querySelector('details.filtres-repliables');
    if (filtresMobile && window.matchMedia('(max-width: 720px)').matches && !filtresMobile.dataset.actifs) {
        filtresMobile.open = false;
    }

    // ===== Photos : préparées dans le navigateur AVANT l'envoi =====
    //  - remises droites (le navigateur applique l'orientation EXIF des photos prises téléphone en main)
    //  - photos iPhone HEIC converties en JPEG (décodeur js/vendor/libheif, ~1,5 Mo, chargé seulement si besoin)
    //  - réduites à 2400 px : envoi plus rapide en 4G, le serveur les réduit encore ensuite
    let chargementLibheif = null;
    const chargerLibheif = function () {
        if (!chargementLibheif) {
            chargementLibheif = new Promise(function (ok, erreur) {
                const script = document.createElement('script');
                script.src = 'js/vendor/libheif/libheif.js';
                script.onload = function () {
                    // On télécharge nous-mêmes le .wasm puis on le donne à libheif (sa méthode de chargement ne marche pas dans un navigateur)
                    fetch('js/vendor/libheif/libheif.wasm')
                        .then(function (r) { if (!r.ok) throw new Error('wasm introuvable'); return r.arrayBuffer(); })
                        .then(function (wasm) { return window.libheif({ wasmBinary: wasm }); })
                        .then(ok, erreur);
                };
                script.onerror = erreur;
                document.head.appendChild(script);
            }).catch(function (e) { chargementLibheif = null; throw e; });
        }
        return chargementLibheif;
    };
    const estHeic = function (f) {
        return /image\/hei[cf]/i.test(f.type) || /\.(heic|heif)$/i.test(f.name);
    };
    // HEIC → toile (canvas) avec les pixels de la photo
    const decoderHeic = async function (fichier) {
        const lib = await chargerLibheif();
        const images = new lib.HeifDecoder().decode(new Uint8Array(await fichier.arrayBuffer()));
        if (!images || !images.length) throw new Error('photo illisible');
        const image = images[0];
        const l = image.get_width(), h = image.get_height();
        const pixels = new ImageData(l, h);
        await new Promise(function (ok, erreur) {
            image.display(pixels, function (resultat) { resultat ? ok() : erreur(new Error('décodage impossible')); });
        });
        const toile = document.createElement('canvas');
        toile.width = l; toile.height = h;
        toile.getContext('2d').putImageData(pixels, 0, 0);
        return toile;
    };
    // JPG/PNG/WEBP → image décodée par le navigateur (qui la remet droite grâce à l'EXIF)
    const chargerImage = function (fichier) {
        return new Promise(function (ok, erreur) {
            const img = new Image();
            img.onload = function () { ok(img); };
            img.onerror = function () { erreur(new Error('image illisible')); };
            img.src = URL.createObjectURL(fichier);
        });
    };
    // Renvoie un fichier JPEG droit et allégé (ou le fichier d'origine si on ne sait pas le lire : le serveur décidera)
    const preparerPhoto = async function (fichier) {
        let source;
        if (estHeic(fichier)) {
            source = await decoderHeic(fichier); // en cas d'échec : erreur, gérée par l'appelant
        } else if (/^image\/(jpeg|png|webp)$/i.test(fichier.type)) {
            try { source = await chargerImage(fichier); } catch (e) { return fichier; }
        } else {
            return fichier;
        }
        const l = source.naturalWidth || source.width, h = source.naturalHeight || source.height;
        const echelle = Math.min(1, 2400 / Math.max(l, h));
        const toile = document.createElement('canvas');
        toile.width = Math.round(l * echelle); toile.height = Math.round(h * echelle);
        const ctx = toile.getContext('2d');
        ctx.fillStyle = '#fff'; // fond blanc pour les PNG transparents
        ctx.fillRect(0, 0, toile.width, toile.height);
        ctx.drawImage(source, 0, 0, toile.width, toile.height);
        if (source.src) URL.revokeObjectURL(source.src);
        const blob = await new Promise(function (ok) { toile.toBlob(ok, 'image/jpeg', 0.9); });
        if (!blob) return fichier;
        return new File([blob], fichier.name.replace(/\.[a-z0-9]+$/i, '') + '.jpg', { type: 'image/jpeg' });
    };

    // Champ photo simple (photo de profil) : <input type="file" data-heic>
    document.querySelectorAll('input[type=file][data-heic]').forEach(function (champ) {
        champ.addEventListener('change', async function () {
            const fichiers = Array.from(champ.files);
            if (!fichiers.length) return;
            const formulaire = champ.form;
            const bouton = formulaire.querySelector('button[type=submit], button:not([type])');
            let info = champ.parentNode.querySelector('.info-heic');
            if (!info) {
                info = document.createElement('span');
                info.className = 'info-heic petit';
                champ.insertAdjacentElement('afterend', info);
            }
            info.textContent = fichiers.some(estHeic) ? '⏳ Conversion de la photo iPhone (HEIC) en JPEG…' : '⏳ Préparation de la photo…';
            if (bouton) bouton.disabled = true;
            try {
                const prets = [];
                for (const f of fichiers) prets.push(await preparerPhoto(f));
                const transfert = new DataTransfer();
                prets.forEach(function (f) { transfert.items.add(f); });
                champ.files = transfert.files;
                info.textContent = '✅ Photo prête.';
                if (champ.hasAttribute('data-envoi-auto')) formulaire.submit();
            } catch (e) {
                champ.value = '';
                info.textContent = '❌ Impossible de lire cette photo. Essaie avec un autre navigateur, ou exporte-la en JPG.';
            }
            if (bouton) bouton.disabled = false;
        });
    });

    // ===== Photos d'une annonce : gros bouton « Ajouter des photos », vignettes, ✕ pour retirer, ★ pour la photo principale =====
    // Les vraies cases (photo, photos_sup[], supprimer_photos[]) restent dans le formulaire : on les remplit juste avant l'envoi.
    const blocPhotos = document.querySelector('[data-photos-annonce]');
    if (blocPhotos) {
        const formulaire = blocPhotos.closest('form');
        const grille = blocPhotos.querySelector('[data-grille-photos]');
        const boutonAjout = blocPhotos.querySelector('[data-ajout-photo]');
        const choix = blocPhotos.querySelector('[data-choix-photos]');
        const compteur = blocPhotos.querySelector('[data-compteur-photos]');
        const classiques = formulaire.querySelector('[data-champs-classiques]');
        const champPrincipal = formulaire.querySelector('input[name=photo]');
        const champSup = formulaire.querySelector('input[name="photos_sup[]"]');
        const boutonEnvoi = formulaire.querySelector('button[type=submit]');
        const max = parseInt(blocPhotos.dataset.max, 10) || 5;
        const nouvelles = [];   // [{fichier, url, pret}]
        let principale = blocPhotos.querySelector('[data-existante=principale]') ? 'existante' : 0; // 'existante' ou index dans nouvelles
        let enCours = 0;

        blocPhotos.hidden = false;
        if (classiques) classiques.hidden = true;

        const existantesGardees = function () {
            return Array.from(blocPhotos.querySelectorAll('[data-existante]')).filter(function (t) { return !t.classList.contains('retiree'); }).length;
        };
        const total = function () { return existantesGardees() + nouvelles.length; };
        const majCompteur = function () {
            compteur.textContent = total() + ' / ' + max + ' photos' + (enCours ? ' · ⏳ préparation…' : '') + (total() ? ' · ★ = photo principale (la première vue)' : '');
            boutonAjout.hidden = total() >= max;
            if (boutonEnvoi) boutonEnvoi.disabled = enCours > 0;
        };
        const dessiner = function () {
            grille.querySelectorAll('[data-nouvelle]').forEach(function (t) { t.remove(); });
            // étiquette ★ sur la photo existante principale (si elle reste principale)
            const tuileExistante = grille.querySelector('[data-existante=principale]');
            if (tuileExistante) {
                tuileExistante.classList.toggle('est-principale', principale === 'existante');
                tuileExistante.querySelector('.tuile-etoile').textContent = principale === 'existante' ? '★ Principale' : '☆ Garder en principale';
            }
            nouvelles.forEach(function (n, i) {
                const tuile = document.createElement('div');
                tuile.className = 'tuile-photo' + (principale === i ? ' est-principale' : '');
                tuile.dataset.nouvelle = i;
                tuile.innerHTML = n.pret ? '<img alt="">' : '<span class="tuile-attente">⏳</span>';
                if (n.pret) tuile.querySelector('img').src = n.url;
                const etoile = document.createElement('button');
                etoile.type = 'button';
                etoile.className = 'tuile-etoile';
                etoile.textContent = principale === i ? '★ Principale' : '☆ Mettre en principale';
                etoile.addEventListener('click', function () { principale = i; dessiner(); });
                const retirer = document.createElement('button');
                retirer.type = 'button';
                retirer.className = 'tuile-retirer';
                retirer.setAttribute('aria-label', 'Retirer cette photo');
                retirer.textContent = '✕';
                retirer.addEventListener('click', function () {
                    URL.revokeObjectURL(n.url);
                    nouvelles.splice(i, 1);
                    if (principale === i) principale = tuileExistante && !tuileExistante.classList.contains('retiree') ? 'existante' : 0;
                    else if (typeof principale === 'number' && principale > i) principale--;
                    dessiner();
                });
                tuile.appendChild(etoile);
                tuile.appendChild(retirer);
                grille.insertBefore(tuile, boutonAjout);
            });
            majCompteur();
        };
        const ajouter = async function (fichiers) {
            const place = max - total();
            const pris = Array.from(fichiers).filter(function (f) { return /^image\//.test(f.type) || estHeic(f); }).slice(0, place);
            if (fichiers.length > pris.length) compteur.dataset.alerte = '1';
            for (const f of pris) {
                const n = { fichier: f, url: '', pret: false };
                nouvelles.push(n);
                enCours++;
                dessiner();
                try {
                    n.fichier = await preparerPhoto(f);
                    n.url = URL.createObjectURL(n.fichier);
                    n.pret = true;
                } catch (e) {
                    nouvelles.splice(nouvelles.indexOf(n), 1);
                    alerteTexte('❌ « ' + f.name + ' » est illisible (essaie de l\'exporter en JPG).');
                }
                enCours--;
                dessiner();
            }
            if (fichiers.length > pris.length) alerteTexte('Maximum ' + max + ' photos par annonce : les photos en trop ont été ignorées.');
        };
        const alerteTexte = function (texte) {
            let a = blocPhotos.querySelector('.alerte-photos');
            if (!a) { a = document.createElement('p'); a.className = 'alerte-photos petit'; compteur.after(a); }
            a.textContent = texte;
        };

        choix.addEventListener('change', function () { ajouter(choix.files); choix.value = ''; });
        // Glisser-déposer des photos sur le bloc (ordinateur)
        ['dragenter', 'dragover'].forEach(function (ev) {
            blocPhotos.addEventListener(ev, function (e) { e.preventDefault(); blocPhotos.classList.add('survol'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            blocPhotos.addEventListener(ev, function (e) { e.preventDefault(); blocPhotos.classList.remove('survol'); });
        });
        blocPhotos.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files) ajouter(e.dataTransfer.files); });

        // Photos déjà en ligne (modification) : ✕ coche la case « supprimer » cachée, ★ garde la photo principale actuelle
        blocPhotos.querySelectorAll('[data-existante]').forEach(function (tuile) {
            const boutonRetirer = tuile.querySelector('.tuile-retirer');
            if (boutonRetirer) {
                boutonRetirer.addEventListener('click', function () {
                    const caseSupprimer = formulaire.querySelector('input[name="supprimer_photos[]"][value="' + tuile.dataset.id + '"]');
                    const retiree = !tuile.classList.contains('retiree');
                    if (retiree && total() <= 0) return;
                    tuile.classList.toggle('retiree', retiree);
                    if (caseSupprimer) caseSupprimer.checked = retiree;
                    boutonRetirer.textContent = retiree ? '↺' : '✕';
                    boutonRetirer.setAttribute('aria-label', retiree ? 'Garder cette photo' : 'Retirer cette photo');
                    majCompteur();
                });
            }
            const etoile = tuile.querySelector('.tuile-etoile');
            if (etoile) etoile.addEventListener('click', function () { principale = 'existante'; dessiner(); });
        });

        // Juste avant l'envoi : la photo ★ va dans « photo », les autres dans « photos_sup[] »
        formulaire.addEventListener('submit', function (e) {
            if (enCours) { e.preventDefault(); return; }
            const prets = nouvelles.filter(function (n) { return n.pret; });
            const tPrincipal = new DataTransfer();
            const tSup = new DataTransfer();
            prets.forEach(function (n) {
                if (principale !== 'existante' && nouvelles.indexOf(n) === principale) tPrincipal.items.add(n.fichier);
                else tSup.items.add(n.fichier);
            });
            // création sans ★ choisie : la première photo devient la principale
            if (!tPrincipal.files.length && principale !== 'existante' && tSup.files.length) {
                const premiere = tSup.files[0];
                const reste = new DataTransfer();
                Array.from(tSup.files).slice(1).forEach(function (f) { reste.items.add(f); });
                tPrincipal.items.add(premiere);
                if (champSup) champSup.files = reste.files;
            } else if (champSup) {
                champSup.files = tSup.files;
            }
            if (champPrincipal) champPrincipal.files = tPrincipal.files;
        });

        boutonAjout.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); choix.click(); }
        });
        dessiner();
    }

    // Code postal → liste des villes (inscription, Mon compte).
    // <input data-code-postal="id-du-champ-ville"> : dès 5 chiffres, on propose les villes ; s'il n'y en a qu'une, on la remplit.
    document.querySelectorAll('[data-code-postal]').forEach(function (champCp) {
        const champVille = document.getElementById(champCp.dataset.codePostal);
        const liste = document.getElementById(champVille.getAttribute('list'));
        let dernierCp = '';
        const majVilles = function () {
            const cp = champCp.value.trim();
            if (!/^\d{5}$/.test(cp) || cp === dernierCp) return;
            dernierCp = cp;
            fetch('index.php?route=geo/communes&cp=' + cp)
                .then(function (r) { return r.json(); })
                .then(function (villes) {
                    liste.innerHTML = '';
                    villes.forEach(function (v) {
                        const option = document.createElement('option');
                        option.value = v.nom;
                        liste.appendChild(option);
                    });
                    const noms = villes.map(function (v) { return v.nom; });
                    if (noms.length === 1) {
                        champVille.value = noms[0];
                    } else if (noms.indexOf(champVille.value) === -1) {
                        champVille.value = '';
                    }
                    champVille.placeholder = noms.length ? 'Choisis ta ville (' + noms.length + ' possible' + (noms.length > 1 ? 's' : '') + ')' : 'Code postal inconnu';
                    if (noms.length > 1) champVille.focus();
                })
                .catch(function () { /* hors ligne : on tape la ville à la main */ });
        };
        champCp.addEventListener('input', majVilles);
        majVilles();
    });

    // Recherche « Autour de » : suggestions de villes, bouton « 📍 Ma position », rayon.
    // Les champs cachés lat/lon reçoivent le point GPS de la ville choisie.
    const autour = document.querySelector('[data-autour]');
    if (autour) {
        const formAutour = autour.form;
        const champLat = formAutour.querySelector('[name=lat]');
        const champLon = formAutour.querySelector('[name=lon]');
        const listeAutour = document.getElementById(autour.getAttribute('list'));
        const rayon = formAutour.querySelector('[name=rayon]');
        const points = {};
        let minuteur = null;
        autour.addEventListener('input', function () {
            if (points[autour.value]) {
                // une suggestion vient d'être choisie
                champLat.value = points[autour.value][0];
                champLon.value = points[autour.value][1];
                if (rayon && rayon.value === '') rayon.value = '30';
                return;
            }
            champLat.value = ''; champLon.value = '';
            clearTimeout(minuteur);
            const texte = autour.value.trim();
            if (texte.length < 2) return;
            minuteur = setTimeout(function () {
                fetch('index.php?route=geo/recherche&q=' + encodeURIComponent(texte))
                    .then(function (r) { return r.json(); })
                    .then(function (villes) {
                        listeAutour.innerHTML = '';
                        villes.forEach(function (v) {
                            points[v.libelle] = [v.lat, v.lon];
                            const option = document.createElement('option');
                            option.value = v.libelle;
                            listeAutour.appendChild(option);
                        });
                    })
                    .catch(function () {});
            }, 200);
        });
        // Rayon choisi sans ville : on part de chez soi (membre connecté) ou on invite à taper une ville
        if (rayon) {
            rayon.addEventListener('change', function () {
                if (rayon.value === '' || champLat.value) return;
                if (autour.dataset.chezMoi && autour.value.trim() === '') {
                    autour.value = autour.dataset.chezMoi;
                    champLat.value = autour.dataset.chezMoiLat;
                    champLon.value = autour.dataset.chezMoiLon;
                } else if (autour.value.trim() === '') {
                    autour.focus();
                    autour.placeholder = 'Indique une ville pour le rayon';
                }
            });
        }
        // Champ « Autour de » vidé : on efface aussi le point GPS (sinon l'ancien lieu resterait actif)
        autour.addEventListener('search', function () { if (autour.value === '') { champLat.value = ''; champLon.value = ''; } });

        const boutonPosition = formAutour.querySelector('[data-ma-position]');
        if (boutonPosition && navigator.geolocation) {
            boutonPosition.hidden = false;
            boutonPosition.addEventListener('click', function () {
                boutonPosition.disabled = true;
                boutonPosition.textContent = '⏳ Localisation…';
                navigator.geolocation.getCurrentPosition(function (pos) {
                    if (rayon && rayon.value === '') rayon.value = '30';
                    // Vie privée : la position exacte part une seule fois au serveur (en POST, jamais enregistrée),
                    // qui répond avec la ville la plus proche. On cherche ensuite autour du CENTRE de cette ville,
                    // donc la position exacte n'apparaît ni dans l'adresse de la page, ni dans l'historique.
                    const donnees = new FormData();
                    donnees.append('lat', pos.coords.latitude.toFixed(5));
                    donnees.append('lon', pos.coords.longitude.toFixed(5));
                    fetch('index.php?route=geo/autour', { method: 'POST', body: donnees })
                        .then(function (r) { return r.json(); })
                        .then(function (v) {
                            if (!v.libelle) throw new Error('aucune ville');
                            autour.value = v.libelle;
                            champLat.value = v.lat;
                            champLon.value = v.lon;
                        })
                        .catch(function () {
                            // secours : position arrondie à ~1 km
                            autour.value = 'Ma position';
                            champLat.value = pos.coords.latitude.toFixed(2);
                            champLon.value = pos.coords.longitude.toFixed(2);
                        })
                        .finally(function () { formAutour.submit(); });
                }, function () {
                    // refusée ou introuvable : on le dit sur le bouton, la personne peut taper sa ville
                    boutonPosition.disabled = false;
                    boutonPosition.textContent = '📍 Position refusée : tape ta ville';
                }, { timeout: 10000, maximumAge: 600000 });
            });
        }
    }

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
