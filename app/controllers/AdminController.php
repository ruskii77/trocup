<?php
// Espace réservé à l'administrateur (role = 'admin' dans la table utilisateur)
class AdminController extends Controller
{
    public function index(): void
    {
        $this->exigerAdmin();
        $this->afficher('admin/index', [
            'signalements' => Signalement::enAttente(),
            'signalementsMembres' => SignalementMembre::enAttente(),
            'utilisateurs' => Utilisateur::tous(),
        ], 'Administration');
    }

    // Retire l'annonce signalée et clôt ses signalements
    public function retirerObjet(): void
    {
        $this->exigerPost();
        $this->exigerAdmin();
        $idObjet = (int) ($_POST['id_objet'] ?? 0);
        if (Objet::trouverParId($idObjet)) {
            Objet::retirer($idObjet);
            Signalement::traiterPourObjet($idObjet);
            flash('succes', 'Annonce retirée.');
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
        $this->changerBannissement(true, 'Compte banni : ses annonces ne sont plus visibles.');
    }

    public function debannir(): void
    {
        $this->changerBannissement(false, 'Compte réactivé.');
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
            }
            flash('succes', $message);
        }
        $this->rediriger('admin/index');
    }
}
