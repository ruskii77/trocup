<?php
class ObjetController extends Controller
{
    // Page d'accueil : une vitrine organisée, pas la liste de toutes les annonces
    public function accueil(): void
    {
        $u = utilisateur_connecte();
        // « Les plus belles chaînes » : les membres qui ont fait le plus d'échanges
        $meilleurs = [];
        foreach (Utilisateur::meilleursEchangeurs(3) as $m) {
            $chaine = Proposition::chaine((int) $m['id']);
            $meilleurs[] = $m + [
                'depart'  => $chaine[0]['donne_titre'],  'valeur_depart'  => $chaine[0]['donne_valeur'],
                'arrivee' => end($chaine)['recu_titre'], 'valeur_arrivee' => end($chaine)['recu_valeur'],
                'photo'   => end($chaine)['recu_photo'],
            ];
        }

        $this->afficher('accueil', [
            'categories'   => Categorie::avecNombre(),
            'recentes'     => Objet::recentes(8),
            'pres'         => $u ? Objet::recentes(4, $u['departement'], $u['id']) : [],
            'departement'  => $u['departement'] ?? null,
            'total'        => Objet::nombreDisponibles(),
            'nbMembres'    => Utilisateur::compter(),
            'nbEchanges'   => Proposition::nombreAcceptees(),
            'meilleurs'    => $meilleurs,
        ]);
    }

    // Page « Chercher » : tous les filtres + tri + pagination
    public function recherche(): void
    {
        $filtres = [
            'q'           => trim($_GET['q'] ?? ''),
            'categorie'   => (int) ($_GET['categorie'] ?? 0) ?: null,
            'departement' => isset(departements()[$_GET['departement'] ?? '']) ? $_GET['departement'] : '',
            'etat'        => in_array($_GET['etat'] ?? '', ['neuf', 'tres_bon', 'bon', 'use'], true) ? $_GET['etat'] : '',
            'valeur_min'  => is_numeric($_GET['valeur_min'] ?? '') ? (float) $_GET['valeur_min'] : null,
            'valeur_max'  => is_numeric($_GET['valeur_max'] ?? '') ? (float) $_GET['valeur_max'] : null,
            'tri'         => isset(Objet::TRIS[$_GET['tri'] ?? '']) ? $_GET['tri'] : 'recent',
        ];
        $categorie = $filtres['categorie'] ? Categorie::trouver($filtres['categorie']) : null;
        if (!$categorie) {
            $filtres['categorie'] = null;
        }
        // Filtres propres à la catégorie choisie (Taille, Pointure…)
        $filtres['caracs'] = Caracteristique::filtresRecherche($filtres['categorie'], $_GET['c'] ?? []);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $resultat = Objet::rechercher($filtres, $page);

        // Numéro de page trop grand (ex : lien ancien) → on affiche la dernière page
        $nbPages = max(1, (int) ceil($resultat['total'] / ANNONCES_PAR_PAGE));
        if ($page > $nbPages) {
            $page = $nbPages;
            $resultat = Objet::rechercher($filtres, $page);
        }

        $this->afficher('objets/recherche', [
            'filtres'    => $filtres,
            'objets'     => $resultat['objets'],
            'total'      => $resultat['total'],
            'page'       => $page,
            'nbPages'    => $nbPages,
            'arbre'      => Categorie::arbre(),
            'categorie'  => $categorie,
            // Raccourcis vers les sous-catégories quand une catégorie principale est choisie
            'sousCategories' => ($categorie && $categorie['id_parent'] === null) ? Categorie::enfantsAvecNombre($categorie['id']) : [],
            'caracteristiques' => Caracteristique::pourCategorie($filtres['categorie']),
        ], $categorie ? $categorie['nom'] . ' à échanger' : 'Chercher un objet');
    }

    public function voir(): void
    {
        $objet = Objet::trouverParId((int) ($_GET['id'] ?? 0));
        $proprietaire = $objet ? Utilisateur::trouverParId((int) $objet['id_utilisateur']) : null;

        // Un objet retiré ou appartenant à un compte banni n'est visible que par l'admin
        if (!$objet || (($objet['statut'] !== 'disponible' || $proprietaire['est_banni']) && !est_admin())) {
            $this->pageIntrouvable();
        }

        $u = utilisateur_connecte();
        $estProprio = $u && (int) $u['id'] === (int) $objet['id_utilisateur'];
        $this->afficher('objets/voir', [
            'objet'        => $objet,
            'photosSup'    => Objet::photosSup($objet['id']),
            'caracteristiques' => Caracteristique::affichageObjet($objet['id']),
            'estFavori'    => $u ? Favori::estFavori($u['id'], $objet['id']) : false,
            'nbFavoris'    => Favori::nombrePour($objet['id']),
            // Suggestions : mes objets dont la valeur est proche de celui-ci
            'suggestions'  => ($u && !$estProprio) ? Objet::suggestionsPour($u['id'], (float) $objet['valeur_estimee']) : [],
            'bloque'       => $u && !$estProprio && Blocage::entre($u['id'], (int) $objet['id_utilisateur']),
            'estProprio'   => $estProprio,
            'note'         => Utilisateur::noteMoyenne((int) $objet['id_utilisateur']),
            'meta'         => [
                'description' => 'À échanger sur ' . NOM_SITE . ' : ' . mb_substr($objet['description'], 0, 150),
                'image'       => $objet['photo'] ? URL_SITE . '/uploads/' . $objet['photo'] : URL_SITE . '/img/partage.jpg',
            ],
        ], $objet['titre']);
    }

    public function creer(): void
    {
        $u = $this->exigerConnexion();
        $erreurs = [];
        $valeurs = ['titre' => '', 'description' => '', 'etat' => 'bon', 'valeur_estimee' => '', 'id_categorie' => '', 'c' => []];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            [$valeurs, $erreurs] = $this->validerFormulaire();
            if (!$erreurs && !Limiteur::autoriser('objet', (string) $u['id'])) {
                $erreurs[] = 'Tu as publié beaucoup d\'annonces aujourd\'hui. Réessaie demain.';
            }

            $photo = null;
            if (!$erreurs && !empty($_FILES['photo']['name'])) {
                try {
                    $photo = Objet::enregistrerPhoto($_FILES['photo']);
                } catch (RuntimeException $ex) {
                    $erreurs[] = $ex->getMessage();
                }
            }

            if (!$erreurs) {
                $id = Objet::creer($valeurs + ['photo' => $photo, 'id_utilisateur' => $u['id']]);
                Caracteristique::enregistrer($id, $valeurs['c']);
                $this->enregistrerPhotosSup($id, 0);
                flash('succes', 'Ton objet est en ligne !');
                $this->rediriger('objet/voir', ['id' => $id]);
            }
        }

        $this->afficher('objets/formulaire', [
            'valeurs' => $valeurs, 'erreurs' => $erreurs,
            'arbre' => Categorie::arbre(), 'caracteristiques' => Caracteristique::toutes(),
            'modification' => false, 'photosSup' => [],
        ], 'Publier un objet');
    }

    public function modifier(): void
    {
        $u = $this->exigerConnexion();
        $objet = Objet::trouverParId((int) ($_GET['id'] ?? 0));
        if (!$objet || $objet['statut'] !== 'disponible' || (int) $objet['id_utilisateur'] !== (int) $u['id']) {
            $this->pageIntrouvable();
        }

        $erreurs = [];
        $valeurs = $objet + ['c' => Caracteristique::valeursObjet($objet['id'])];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            [$valeurs, $erreurs] = $this->validerFormulaire();

            $photo = $objet['photo'];
            if (!$erreurs && !empty($_FILES['photo']['name'])) {
                try {
                    $photo = Objet::enregistrerPhoto($_FILES['photo']);
                } catch (RuntimeException $ex) {
                    $erreurs[] = $ex->getMessage();
                }
            }

            if (!$erreurs) {
                if ($photo !== $objet['photo']) {
                    Image::supprimer($objet['photo']); // l'ancienne photo ne sert plus
                }
                Objet::modifier($objet['id'], $valeurs + ['photo' => $photo]);
                Caracteristique::enregistrer($objet['id'], $valeurs['c']);
                foreach ((array) ($_POST['supprimer_photos'] ?? []) as $idPhoto) {
                    Objet::supprimerPhotoSup((int) $idPhoto, $objet['id']);
                }
                $this->enregistrerPhotosSup($objet['id'], count(Objet::photosSup($objet['id'])));
                flash('succes', 'Objet modifié.');
                $this->rediriger('objet/voir', ['id' => $objet['id']]);
            }
            $valeurs['id'] = $objet['id'];
        }

        $this->afficher('objets/formulaire', [
            'valeurs' => $valeurs, 'erreurs' => $erreurs,
            'arbre' => Categorie::arbre(), 'caracteristiques' => Caracteristique::toutes(),
            'modification' => true, 'photosSup' => Objet::photosSup($objet['id']),
        ], 'Modifier un objet');
    }

    public function retirer(): void
    {
        $this->exigerPost();
        $u = $this->exigerConnexion();
        $objet = Objet::trouverParId((int) ($_POST['id'] ?? 0));
        if (!$objet || (int) $objet['id_utilisateur'] !== (int) $u['id']) {
            $this->pageIntrouvable();
        }
        Objet::retirer($objet['id']);
        flash('succes', 'Ton annonce a été retirée.');
        $this->rediriger('utilisateur/profil', ['id' => $u['id']]);
    }

    public function signaler(): void
    {
        $this->exigerPost();
        $u = $this->exigerConnexion();
        $objet = Objet::trouverParId((int) ($_POST['id'] ?? 0));
        $motif = trim($_POST['motif'] ?? '');

        if (!$objet) {
            $this->pageIntrouvable();
        }
        if ($motif === '' || mb_strlen($motif) > 255) {
            flash('erreur', 'Indique un motif (255 caractères maximum).');
        } elseif (!Limiteur::autoriser('signalement', (string) $u['id'])) {
            flash('erreur', 'Tu as déjà fait beaucoup de signalements aujourd\'hui.');
        } else {
            Signalement::creer($objet['id'], $u['id'], $motif);
            flash('succes', 'Merci, l\'annonce a été signalée à l\'administrateur.');
        }
        $this->rediriger('objet/voir', ['id' => $objet['id']]);
    }

    // ❤️ Ajouter / retirer des favoris
    public function favori(): void
    {
        $this->exigerPost();
        $u = $this->exigerConnexion();
        $objet = Objet::trouverParId((int) ($_POST['id'] ?? 0));
        if (!$objet) {
            $this->pageIntrouvable();
        }
        $ajoute = Favori::basculer($u['id'], $objet['id']);
        flash('succes', $ajoute ? 'Ajouté à tes favoris ❤️' : 'Retiré de tes favoris.');
        if (($_POST['retour'] ?? '') === 'favoris') {
            $this->rediriger('objet/favoris');
        }
        $this->rediriger('objet/voir', ['id' => $objet['id']]);
    }

    public function favoris(): void
    {
        $u = $this->exigerConnexion();
        $this->afficher('objets/favoris', ['objets' => Favori::objetsDe($u['id'])], 'Mes favoris');
    }

    // Le propriétaire marque son annonce « réservée » (en discussion) ou à nouveau disponible
    public function reserver(): void
    {
        $this->exigerPost();
        $u = $this->exigerConnexion();
        $objet = Objet::trouverParId((int) ($_POST['id'] ?? 0));
        if (!$objet || (int) $objet['id_utilisateur'] !== (int) $u['id']) {
            $this->pageIntrouvable();
        }
        Objet::changerReserve($objet['id'], !$objet['est_reserve']);
        flash('succes', $objet['est_reserve'] ? 'Ton annonce est à nouveau disponible.' : 'Annonce marquée comme réservée : plus personne ne peut faire de nouvelle proposition.');
        $this->rediriger('objet/voir', ['id' => $objet['id']]);
    }

    // Enregistre les photos supplémentaires envoyées (champ photos_sup[]), dans la limite autorisée
    private function enregistrerPhotosSup(int $idObjet, int $dejaPresentes): void
    {
        $f = $_FILES['photos_sup'] ?? null;
        if (!$f || !is_array($f['name'])) {
            return;
        }
        $place = Objet::MAX_PHOTOS_SUP - $dejaPresentes;
        foreach ($f['name'] as $i => $nom) {
            if ($nom === '' || $place <= 0) {
                continue;
            }
            $fichier = ['name' => $nom, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
            try {
                Objet::ajouterPhotoSup($idObjet, Image::enregistrer($fichier, 'objet'));
                $place--;
            } catch (RuntimeException $ex) {
                flash('erreur', 'Photo « ' . $nom . ' » ignorée : ' . $ex->getMessage());
            }
        }
    }

    // Vérifie les champs du formulaire objet. Renvoie [valeurs propres, liste d'erreurs]
    private function validerFormulaire(): array
    {
        $v = [
            'titre'          => trim($_POST['titre'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'etat'           => $_POST['etat'] ?? '',
            'valeur_estimee' => str_replace(',', '.', trim($_POST['valeur_estimee'] ?? '0')),
            'id_categorie'   => (int) ($_POST['id_categorie'] ?? 0),
        ];
        $erreurs = [];

        if ($v['titre'] === '' || mb_strlen($v['titre']) > 100) $erreurs[] = 'Le titre est obligatoire (100 caractères maximum).';
        if ($v['description'] === '')                            $erreurs[] = 'La description est obligatoire.';
        if (!in_array($v['etat'], ['neuf', 'tres_bon', 'bon', 'use'], true)) $erreurs[] = 'État invalide.';
        if (!is_numeric($v['valeur_estimee']) || $v['valeur_estimee'] < 0 || $v['valeur_estimee'] > 1000000) {
            $erreurs[] = 'La valeur estimée doit être un nombre positif.';
        }
        if (!Categorie::existe($v['id_categorie'])) {
            $erreurs[] = 'Choisis une catégorie.';
            $v['c'] = [];
        } else {
            // Taille, pointure, plateforme… selon la catégorie choisie
            [$v['c'], $erreursCaracs] = Caracteristique::valider($v['id_categorie'], $_POST['c'] ?? []);
            $erreurs = array_merge($erreurs, $erreursCaracs);
        }

        $v['valeur_estimee'] = (float) $v['valeur_estimee'];
        return [$v, $erreurs];
    }
}
