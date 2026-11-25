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
