<?php
class PropositionController extends Controller
{
    // Formulaire « proposer un échange » pour un objet donné
    public function nouvelle(): void
    {
        $u = $this->exigerConnexion();
        $objetDemande = Objet::trouverParId((int) ($_GET['id_objet'] ?? $_POST['id_objet_demande'] ?? 0));
        $proprio = $objetDemande ? Utilisateur::trouverParId((int) $objetDemande['id_utilisateur']) : null;

        if (!$objetDemande || $objetDemande['statut'] !== 'disponible' || $proprio['est_banni']) {
            $this->pageIntrouvable();
        }
        if ((int) $objetDemande['id_utilisateur'] === (int) $u['id']) {
            flash('erreur', 'Tu ne peux pas échanger avec toi-même 😉');
            $this->rediriger('objet/voir', ['id' => $objetDemande['id']]);
        }
        if ($objetDemande['est_reserve']) {
            flash('erreur', 'Cet objet est réservé : son propriétaire est déjà en discussion pour un échange.');
            $this->rediriger('objet/voir', ['id' => $objetDemande['id']]);
        }
        if (Blocage::entre($u['id'], (int) $objetDemande['id_utilisateur'])) {
            flash('erreur', 'Tu ne peux pas proposer d\'échange à ce membre.');
            $this->rediriger('objet/voir', ['id' => $objetDemande['id']]);
        }

        $mesObjets = Objet::disponiblesDe($u['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            $objetPropose = Objet::trouverParId((int) ($_POST['id_objet_propose'] ?? 0));
            $message = trim($_POST['message'] ?? '');

            if (!$objetPropose || $objetPropose['statut'] !== 'disponible' || (int) $objetPropose['id_utilisateur'] !== (int) $u['id']) {
                flash('erreur', 'Choisis un de tes objets disponibles.');
            } elseif (Proposition::existeDejaEnAttente($objetPropose['id'], $objetDemande['id'])) {
                flash('erreur', 'Tu as déjà fait cette proposition, attends la réponse.');
            } elseif (!Limiteur::autoriser('proposition', (string) $u['id'])) {
                flash('erreur', 'Tu as fait beaucoup de propositions cette dernière heure. Réessaie un peu plus tard.');
            } else {
                $id = Proposition::creer($objetPropose['id'], $objetDemande['id'], $u['id'], (int) $objetDemande['id_utilisateur']);
                Notification::marquerLu($id, $u['id']); // pas de notification pour soi-même
                if ($message !== '') {
                    Message::envoyer($id, $u['id'], mb_substr($message, 0, 2000));
                }
                NotifEmail::prevenir(Proposition::trouverParId($id), (int) $objetDemande['id_utilisateur'], 'Nouvelle proposition d\'échange');
                flash('succes', 'Proposition envoyée à ' . $objetDemande['pseudo'] . ' !');
                $this->rediriger('proposition/voir', ['id' => $id]);
            }
        }

        $this->afficher('propositions/nouvelle', [
            'objetDemande' => $objetDemande, 'mesObjets' => $mesObjets,
            'preselection' => (int) ($_GET['propose'] ?? 0),
        ], 'Proposer un échange');
    }

    public function mes(): void
    {
        $u = $this->exigerConnexion();
        $this->afficher('propositions/mes', [
            'recues'   => Proposition::recues($u['id']),
            'envoyees' => Proposition::envoyees($u['id']),
            'notifs'   => Notification::parProposition($u['id']),
        ], 'Mes échanges');
    }

    // Détail d'une proposition : les deux objets, la discussion, et l'avis
    public function voir(): void
    {
        [$u, $p] = $this->chargerPourParticipant((int) ($_GET['id'] ?? 0));

        // On note la date de la visite précédente (pour surligner les nouveaux messages), puis tout devient lu
        $derniereLecture = Notification::derniereLecture($p['id'], $u['id']);
        Notification::marquerLu($p['id'], $u['id']);

        $this->afficher('propositions/voir', [
            'p'              => $p,
            'derniereLecture'=> $derniereLecture,
            'messages'       => Message::deProposition($p['id']),
            'estDestinataire'=> (int) $p['id_destinataire'] === (int) $u['id'],
            'peutDonnerAvis' => $p['statut'] === 'acceptee' && !Avis::dejaDonne($p['id'], $u['id']),
        ], 'Échange');
    }

    public function accepter(): void
    {
        $this->exigerPost();
        [$u, $p] = $this->chargerPourParticipant((int) ($_POST['id'] ?? 0));

        if ((int) $p['id_destinataire'] !== (int) $u['id'] || $p['statut'] !== 'en_attente') {
            flash('erreur', 'Cette proposition ne peut plus être acceptée.');
        } else {
            // On revérifie que les deux objets sont toujours disponibles chez les bonnes personnes
            $o1 = Objet::trouverParId($p['id_objet_propose']);
            $o2 = Objet::trouverParId($p['id_objet_demande']);
            if ($o1['statut'] !== 'disponible' || $o2['statut'] !== 'disponible'
                || (int) $o1['id_utilisateur'] !== (int) $p['id_proposeur']
                || (int) $o2['id_utilisateur'] !== (int) $p['id_destinataire']) {
                Proposition::changerStatut($p['id'], 'annulee');
                flash('erreur', 'Un des objets n\'est plus disponible, la proposition est annulée.');
            } else {
                Proposition::accepter($p);
                NotifEmail::prevenir($p, (int) $p['id_proposeur'], 'Ta proposition a été acceptée 🎉');
                flash('succes', 'Échange accepté ! Organisez la rencontre par message, dans un lieu public.');
            }
        }
        $this->rediriger('proposition/voir', ['id' => $p['id']]);
    }

    public function refuser(): void
    {
        $this->changerSiEnAttente('id_destinataire', 'refusee', 'Proposition refusée.');
    }

    public function annuler(): void
    {
        $this->changerSiEnAttente('id_proposeur', 'annulee', 'Proposition annulée.');
    }

    public function message(): void
    {
        $this->exigerPost();
        [$u, $p] = $this->chargerPourParticipant((int) ($_POST['id'] ?? 0));
        $contenu = trim($_POST['contenu'] ?? '');

        if ($contenu === '' || mb_strlen($contenu) > 2000) {
            flash('erreur', 'Le message doit faire entre 1 et 2000 caractères.');
        } else {
            if (Blocage::entre((int) $p['id_proposeur'], (int) $p['id_destinataire'])) {
                flash('erreur', 'Vous ne pouvez plus vous écrire (un des deux a bloqué l\'autre).');
            } elseif (!Limiteur::autoriser('message', (string) $u['id'])) {
                flash('erreur', 'Doucement ! Trop de messages en peu de temps, attends quelques minutes.');
            } else {
                Message::envoyer($p['id'], $u['id'], $contenu);
                $autre = (int) $p['id_proposeur'] === (int) $u['id'] ? (int) $p['id_destinataire'] : (int) $p['id_proposeur'];
                NotifEmail::prevenir($p, $autre, 'Nouveau message de ' . $u['pseudo']);
            }
        }
        $this->rediriger('proposition/voir', ['id' => $p['id']]);
    }

    public function avis(): void
    {
        $this->exigerPost();
        [$u, $p] = $this->chargerPourParticipant((int) ($_POST['id'] ?? 0));
        $note = (int) ($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');

        if ($p['statut'] !== 'acceptee') {
            flash('erreur', 'Tu pourras laisser un avis une fois l\'échange accepté.');
        } elseif (Avis::dejaDonne($p['id'], $u['id'])) {
            flash('erreur', 'Tu as déjà laissé un avis pour cet échange.');
        } elseif ($note < 1 || $note > 5) {
            flash('erreur', 'Choisis une note entre 1 et 5.');
        } else {
            // L'avis va à l'autre personne de l'échange
            $autre = (int) $p['id_proposeur'] === (int) $u['id'] ? $p['id_destinataire'] : $p['id_proposeur'];
            Avis::creer($p['id'], $u['id'], (int) $autre, $note, mb_substr($commentaire, 0, 1000));
            flash('succes', 'Merci pour ton avis !');
        }
        $this->rediriger('proposition/voir', ['id' => $p['id']]);
    }

    // ---------- Outils internes ----------

    // Charge une proposition et vérifie que l'utilisateur connecté en fait partie
    private function chargerPourParticipant(int $id): array
    {
        $u = $this->exigerConnexion();
        $p = Proposition::trouverParId($id);
        if (!$p || !in_array((int) $u['id'], [(int) $p['id_proposeur'], (int) $p['id_destinataire']], true)) {
            $this->pageIntrouvable();
        }
        return [$u, $p];
    }

    private function changerSiEnAttente(string $role, string $statut, string $message): void
    {
        $this->exigerPost();
        [$u, $p] = $this->chargerPourParticipant((int) ($_POST['id'] ?? 0));
        if ((int) $p[$role] === (int) $u['id'] && $p['statut'] === 'en_attente') {
            Proposition::changerStatut($p['id'], $statut);
            flash('succes', $message);
        } else {
            flash('erreur', 'Action impossible sur cette proposition.');
        }
        $this->rediriger('proposition/voir', ['id' => $p['id']]);
    }
}
