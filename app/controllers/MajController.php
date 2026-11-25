<?php
// Page « Mise à jour de la base de données »
// - base vide : tout le monde peut lancer l'installation (premier lancement du site)
// - base existante : réservé à l'admin
class MajController extends Controller
{
    public function index(): void
    {
        $installation = Migrations::baseVide();
        if (!$installation) {
            $this->exigerAdmin();
        }

        $erreur = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            try {
                $faites = Migrations::appliquer();
                flash('succes', $installation
                    ? 'Installation terminée ! Crée ton compte, puis passe-le en admin (voir README).'
                    : count($faites) . ' mise(s) à jour appliquée(s). Tes données sont intactes.');
                $this->rediriger($installation ? 'utilisateur/inscription' : 'maj/index');
            } catch (RuntimeException $e) {
                $erreur = $e->getMessage();
            }
        }

        $this->afficher('maj/index', [
            'installation' => $installation,
            'enAttente'    => Migrations::enAttente(),
            'appliquees'   => Migrations::appliquees(),
            'erreur'       => $erreur,
        ], 'Mise à jour de la base');
    }
}
