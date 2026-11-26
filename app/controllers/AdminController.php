<?php
// Espace réservé à l'administrateur (role = 'admin' dans la table utilisateur)
class AdminController extends Controller
{
    // Motifs proposés pour motiver une décision de modération (obligation DSA : expliquer pourquoi)
    public const MOTIFS = [
        'Objet interdit (armes, médicaments, drogues, contrefaçon, objet volé…)',
        'Contenu choquant, haineux ou inapproprié',
        'Arnaque ou tentative de vente déguisée',
        'Annonce trompeuse ou en double',
        'Comportement abusif envers d\'autres membres',
        'Non-respect des conditions d\'utilisation',
    ];

    public function index(): void
    {
        $this->exigerAdmin();
        $this->afficher('admin/index', [
            'signalements' => Signalement::enAttente(),
            'signalementsMembres' => SignalementMembre::enAttente(),
            'utilisateurs' => Utilisateur::tous(),
            'derniereMaintenance' => Maintenance::derniereExecution(),
        ], 'Administration');
    }

    // Retire l'annonce signalée, clôt ses signalements et prévient le propriétaire avec le motif
    public function retirerObjet(): void
    {
        $this->exigerPost();
        $this->exigerAdmin();
        $objet = Objet::trouverParId((int) ($_POST['id_objet'] ?? 0));
        if ($objet) {
            Objet::retirer($objet['id']);
            Signalement::traiterPourObjet($objet['id']);
            $this->prevenir((int) $objet['id_utilisateur'], 'Ton annonce a été retirée',
                "Ton annonce « {$objet['titre']} » a été retirée par la modération de " . NOM_SITE . '.');
            flash('succes', 'Annonce retirée et propriétaire prévenu par email.');
        }
        $this->rediriger('admin/index');
    }

    // Le signalement n'était pas justifié : on le classe sans suite
    public function ignorer(): void
    {
        $this->exigerPost();
        $this->exigerAdmin();
        Signalement::traiter((int) ($_POST['id'] ?? 0));
        flash('succes', 'Signalement classé sans suite.');
        $this->rediriger('admin/index');
    }

    public function ignorerMembre(): void
    {
        $this->exigerPost();
        $this->exigerAdmin();
        SignalementMembre::traiter((int) ($_POST['id'] ?? 0));
        flash('succes', 'Signalement classé sans suite.');
        $this->rediriger('admin/index');
    }

    public function bannir(): void
    {
        $this->changerBannissement(true, 'Compte banni : ses annonces ne sont plus visibles. Le membre a été prévenu par email.');
    }

    public function debannir(): void
    {
        $this->changerBannissement(false, 'Compte réactivé.');
    }

    // Lance tout de suite le nettoyage RGPD (normalement automatique une fois par jour)
    public function nettoyer(): void
    {
        $this->exigerPost();
        $this->exigerAdmin();
        $bilan = Maintenance::nettoyer();
        $resume = implode(', ', array_map(fn($k, $v) => "$v $k", array_keys($bilan), $bilan));
        flash('succes', 'Nettoyage effectué : ' . $resume . '.');
        $this->rediriger('admin/index');
    }

    // 🗂️ Catégories et filtres : la page affiche tout, les formulaires envoient une « action »
    public function categories(): void
    {
        $this->exigerAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            try {
                flash('succes', $this->actionCategories($_POST['action'] ?? ''));
            } catch (InvalidArgumentException $ex) {
                flash('erreur', $ex->getMessage());
            }
            $this->rediriger('admin/categories');
        }
        $this->afficher('admin/categories', [
            'arbre' => Categorie::arbre(),
            'caracteristiques' => Caracteristique::toutes(),
        ], 'Catégories et filtres');
    }

    // Exécute une action du formulaire. Renvoie le message de succès, ou lance une InvalidArgumentException.
    private function actionCategories(string $action): string
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $nomValide = function (string $nom): string {
            if ($nom === '' || mb_strlen($nom) > 50) {
                throw new InvalidArgumentException('Le nom est obligatoire (50 caractères maximum).');
            }
            return $nom;
        };
        // Les catégories cochées pour un filtre : seulement des catégories qui existent
        $categoriesCochees = function (): array {
            $ids = array_map('intval', (array) ($_POST['categories'] ?? []));
            return array_values(array_filter($ids, fn($i) => Categorie::existe($i)));
        };

        switch ($action) {
            case 'ajouter_sous':
                $parent = Categorie::trouver((int) ($_POST['id_parent'] ?? 0));
                if (!$parent || $parent['id_parent'] !== null) {
                    throw new InvalidArgumentException('Catégorie principale introuvable.');
                }
                try {
                    Categorie::creerSousCategorie($nomValide($nom), $parent['id']);
                } catch (PDOException $ex) {
                    throw new InvalidArgumentException('Une catégorie porte déjà ce nom.');
                }
                return "Sous-catégorie « $nom » ajoutée dans {$parent['nom']}.";

            case 'renommer':
                $c = Categorie::trouver($id);
                if (!$c || $c['id_parent'] === null) {
                    throw new InvalidArgumentException('Seules les sous-catégories se renomment ici.');
                }
                try {
                    Categorie::renommer($id, $nomValide($nom));
                } catch (PDOException $ex) {
                    throw new InvalidArgumentException('Une catégorie porte déjà ce nom.');
                }
                return 'Catégorie renommée.';

            case 'description':
                $c = Categorie::trouver($id);
                $texte = trim($_POST['description'] ?? '');
                if (!$c || mb_strlen($texte) > 300) {
                    throw new InvalidArgumentException('Présentation trop longue (300 caractères maximum).');
                }
                Categorie::changerDescription($id, $texte === '' ? null : $texte);
                return "Présentation de « {$c['nom']} » enregistrée.";

            case 'supprimer_sous':
                $c = Categorie::trouver($id);
                if (!$c || $c['id_parent'] === null) {
                    throw new InvalidArgumentException('Seules les sous-catégories peuvent être supprimées.');
                }
                $nb = Categorie::nombreObjets($id);
                Categorie::supprimerSousCategorie($id);
                return "« {$c['nom']} » supprimée" . ($nb ? " ($nb annonce(s) déplacée(s) dans la catégorie principale)." : '.');

            case 'ajouter_carac':
                $type = ($_POST['type'] ?? '') === 'texte' ? 'texte' : 'liste';
                $nomValide($nom);
                if (Caracteristique::nomExiste($nom)) {
                    throw new InvalidArgumentException('Un filtre porte déjà ce nom.');
                }
                // Une option par ligne, ou séparées par des virgules
                $options = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,\n]/', $_POST['options'] ?? '')), fn($o) => $o !== '' && mb_strlen($o) <= 50)));
                if ($type === 'liste' && !$options) {
                    throw new InvalidArgumentException('Un filtre « liste » a besoin d\'au moins un choix (ex : S, M, L).');
                }
                $categories = $categoriesCochees();
                if (!$categories) {
                    throw new InvalidArgumentException('Coche au moins une catégorie.');
                }
                Caracteristique::creer($nom, $type, !empty($_POST['obligatoire']), $type === 'liste' ? $options : [], $categories);
                return "Filtre « $nom » créé.";

            case 'modifier_carac':
                $categories = $categoriesCochees();
                if (!$categories) {
                    throw new InvalidArgumentException('Coche au moins une catégorie (ou supprime le filtre).');
                }
                Caracteristique::changerCategories($id, $categories);
                Caracteristique::changerObligatoire($id, !empty($_POST['obligatoire']));
                return 'Filtre mis à jour.';

            case 'ajouter_option':
                $valeur = trim($_POST['valeur'] ?? '');
                if ($valeur === '' || mb_strlen($valeur) > 50) {
                    throw new InvalidArgumentException('Le choix est obligatoire (50 caractères maximum).');
                }
                Caracteristique::ajouterOption($id, $valeur);
                return "Choix « $valeur » ajouté.";

            case 'supprimer_option':
                Caracteristique::supprimerOption((int) ($_POST['id_option'] ?? 0));
                return 'Choix supprimé.';

            case 'supprimer_carac':
                Caracteristique::supprimer($id);
                return 'Filtre supprimé.';
        }
        throw new InvalidArgumentException('Action inconnue.');
    }

    private function changerBannissement(bool $banni, string $message): void
    {
        $this->exigerPost();
        $admin = $this->exigerAdmin();
        $id = (int) ($_POST['id_utilisateur'] ?? 0);
        if ($id === (int) $admin['id']) {
            flash('erreur', 'Tu ne peux pas te bannir toi-même.');
        } else {
            Utilisateur::definirBannissement($id, $banni);
            if ($banni) {
                SignalementMembre::traiterPourMembre($id);
                $this->prevenir($id, 'Ton compte a été suspendu', 'Ton compte ' . NOM_SITE . ' a été suspendu par la modération.');
            }
            flash('succes', $message);
        }
        $this->rediriger('admin/index');
    }

    // Email au membre concerné : la décision, le motif, et comment la contester (« exposé des motifs » du DSA)
    private function prevenir(int $idUtilisateur, string $sujet, string $decision): void
    {
        $u = Utilisateur::trouverParId($idUtilisateur);
        if (!$u || $u['supprime']) {
            return;
        }
        $motif = trim($_POST['motif'] ?? '') ?: 'Non-respect des conditions d\'utilisation';
        Mailer::envoyer(
            $u['email'], $sujet . ' — ' . NOM_SITE, $sujet,
            "Bonjour {$u['pseudo']},\n$decision\n\nMotif : " . mb_substr($motif, 0, 300)
            . "\n\nCette décision a été prise par un humain (l'administrateur du site), suite à un signalement ou à une vérification."
            . "\nSi tu penses qu'il s'agit d'une erreur, tu peux la contester en répondant à " . EDITEUR_EMAIL . '.'
        );
    }
}
