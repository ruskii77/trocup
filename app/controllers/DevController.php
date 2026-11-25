<?php
// Outils réservés au développement local (désactivés quand MODE_DEV = false)
class DevController extends Controller
{
    // « Boîte mail de test » : affiche les emails enregistrés en mode 'fichier'
    public function emails(): void
    {
        if (!MODE_DEV) {
            $this->pageIntrouvable();
        }
        $this->afficher('dev/emails', ['emails' => Mailer::emailsDeTest()], 'Boîte mail de test');
    }
}
