// Fabrique une belle image de la chaîne d'échanges (format story 1080×1350) pour la partager
// sur Instagram, TikTok, WhatsApp… Tout se fait dans le navigateur avec un <canvas>.
(function () {
    const bouton = document.getElementById('partager-chaine');
    if (!bouton) return;

    const L = 1080, H = 1350;

    function chargerImage(src) {
        return new Promise(resolve => {
            if (!src) return resolve(null);
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = 'uploads/' + src;
        });
    }

    function rectArrondi(ctx, x, y, l, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + l, y, x + l, y + h, r);
        ctx.arcTo(x + l, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + l, y, r);
        ctx.closePath();
    }

    function prix(v) {
        return Math.round(v).toLocaleString('fr-FR') + ' €';
    }

    function couper(ctx, texte, largeurMax) {
        if (ctx.measureText(texte).width <= largeurMax) return texte;
        while (texte.length > 1 && ctx.measureText(texte + '…').width > largeurMax) texte = texte.slice(0, -1);
        return texte + '…';
    }

    async function dessiner() {
        let etapes = JSON.parse(bouton.dataset.etapes);
        const depart = etapes[0].valeur, arrivee = etapes[etapes.length - 1].valeur;
        const nbEchanges = etapes.length - 1;
        // Trop d'étapes pour l'image : on garde le début et la fin
        if (etapes.length > 6) etapes = [etapes[0], etapes[1], { titre: '…', valeur: null }, ...etapes.slice(-3)];

        const canvas = document.createElement('canvas');
        canvas.width = L; canvas.height = H;
        const ctx = canvas.getContext('2d');
        const police = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';

        // Fond dégradé vert → orange
        const fond = ctx.createLinearGradient(0, 0, L, H);
        fond.addColorStop(0, '#0f5e3e'); fond.addColorStop(.6, '#1a9e6c'); fond.addColorStop(1, '#ff9a4d');
        ctx.fillStyle = fond; ctx.fillRect(0, 0, L, H);

        // En-tête
        ctx.fillStyle = '#fff';
        ctx.font = `900 64px ${police}`;
        // Le nom du site, avec la fin en orange (comme le logo)
        const nom = bouton.dataset.site, accent = bouton.dataset.accent || '';
        const debut = accent && nom.endsWith(accent) ? nom.slice(0, -accent.length) : nom;
        ctx.fillText(debut, 80, 130);
        if (debut !== nom) {
            const l = ctx.measureText(debut).width;
            ctx.fillStyle = '#ffb27a'; ctx.fillText(accent, 80 + l, 130);
        }
        ctx.fillStyle = 'rgba(255,255,255,.9)';
        ctx.font = `600 40px ${police}`;
        ctx.fillText('La chaîne d’échanges de @' + bouton.dataset.pseudo, 80, 200);

        // Les étapes
        let y = 260;
        const hauteurEtape = 118;
        const images = await Promise.all(etapes.map(e => chargerImage(e.photo)));
        etapes.forEach((e, i) => {
            const derniere = i === etapes.length - 1;
            ctx.fillStyle = derniere ? '#ffffff' : 'rgba(255,255,255,.16)';
            rectArrondi(ctx, 80, y, L - 160, hauteurEtape - 18, 26); ctx.fill();
            if (images[i]) {
                ctx.save(); rectArrondi(ctx, 98, y + 14, 72, 72, 16); ctx.clip();
                const im = images[i], cote = Math.min(im.width, im.height);
                ctx.drawImage(im, (im.width - cote) / 2, (im.height - cote) / 2, cote, cote, 98, y + 14, 72, 72);
                ctx.restore();
            }
            ctx.fillStyle = derniere ? '#13784f' : '#fff';
            ctx.font = `700 40px ${police}`;
            ctx.fillText(couper(ctx, e.titre, 560), 196, y + 64);
            if (e.valeur !== null) {
                ctx.font = `800 40px ${police}`;
                ctx.fillStyle = derniere ? '#ff8a3d' : '#ffd2ad';
                const t = prix(e.valeur);
                ctx.fillText(t, L - 110 - ctx.measureText(t).width, y + 64);
            }
            y += hauteurEtape;
        });

        // Le résultat
        y += 40;
        ctx.fillStyle = '#fff';
        ctx.font = `900 120px ${police}`;
        const mult = depart > 0 ? '×' + (arrivee / depart).toLocaleString('fr-FR', { maximumFractionDigits: arrivee / depart < 10 ? 1 : 0 }) : '';
        ctx.fillText(mult, 80, y + 100);
        ctx.font = `600 38px ${police}`;
        ctx.fillText(prix(depart) + ' → ' + prix(arrivee) + ' en ' + nbEchanges + ' échange' + (nbEchanges > 1 ? 's' : ''), 80, y + 160);

        ctx.font = `600 34px ${police}`;
        ctx.fillStyle = 'rgba(255,255,255,.92)';
        ctx.fillText(bouton.dataset.slogan + ' — ' + bouton.dataset.site, 80, H - 70);
        return canvas;
    }

    const apercu = document.getElementById('apercu-partage');
    let fichier = null;

    bouton.addEventListener('click', async () => {
        bouton.disabled = true;
        const canvas = await dessiner();
        const url = canvas.toDataURL('image/png');
        document.getElementById('apercu-image').src = url;
        document.getElementById('apercu-telecharger').href = url;
        fichier = await new Promise(r => canvas.toBlob(b => r(new File([b], bouton.dataset.fichier, { type: 'image/png' }))));
        // Le bouton « Partager » n'apparaît que si le téléphone/navigateur sait partager des fichiers
        document.getElementById('apercu-partager').hidden = !(navigator.canShare && navigator.canShare({ files: [fichier] }));
        apercu.hidden = false;
        bouton.disabled = false;
    });

    document.getElementById('apercu-partager').addEventListener('click', () => {
        navigator.share({ files: [fichier], title: 'Ma chaîne ' + bouton.dataset.site, text: 'Regarde jusqu’où je suis monté sur ' + bouton.dataset.site + ' !' }).catch(() => {});
    });
    document.getElementById('apercu-fermer').addEventListener('click', () => { apercu.hidden = true; });
    apercu.addEventListener('click', e => { if (e.target === apercu) apercu.hidden = true; });
})();
