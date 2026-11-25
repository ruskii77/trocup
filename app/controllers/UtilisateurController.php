<?php
class UtilisateurController extends Controller
{
    // ==================== Inscription + confirmation de l'email ====================

    public function inscription(): void
    {
        if (utilisateur_connecte()) $this->rediriger('accueil');

        $erreurs = [];
        $valeurs = ['pseudo' => '', 'email' => '', 'ville' => '', 'code_postal' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            $valeurs = [
                'pseudo'      => trim($_POST['pseudo'] ?? ''),
                'email'       => mb_strtolower(trim($_POST['email'] ?? '')),
                'ville'       => trim($_POST['ville'] ?? ''),
                'code_postal' => trim($_POST['code_postal'] ?? ''),
            ];
            $departement = departement_depuis_cp($valeurs['code_postal']);

            // Anti-robot n°1 : champ « piège » invisible pour un humain. Un robot le remplit → on fait semblant que tout va bien
            if (!empty($_POST['site_web'])) {
                $this->rediriger('utilisateur/verificationEnvoyee');
            }
            // Anti-robot n°2 : 5 inscriptions max par heure depuis la même adresse IP
            if (Limiteur::atteinte('inscription', $_SERVER['REMOTE_ADDR'] ?? '')) {
                $erreurs[] = 'Trop d\'inscriptions depuis ta connexion. Réessaie dans une heure.';
            }
            if (empty($_POST['cgu'])) {
                $erreurs[] = 'Tu dois accepter les conditions d\'utilisation.';
            }

            if (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $valeurs['pseudo'])) $erreurs[] = 'Pseudo : 3 à 50 caractères (lettres, chiffres, - et _).';
            elseif (Utilisateur::pseudoExiste($valeurs['pseudo']))          $erreurs[] = 'Ce pseudo est déjà pris.';
            if (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL))       $erreurs[] = 'Adresse email invalide.';
            elseif (Utilisateur::trouverParEmail($valeurs['email']))         $erreurs[] = 'Un compte existe déjà avec cet email.';
            if ($valeurs['ville'] === '' || mb_strlen($valeurs['ville']) > 100) $erreurs[] = 'Indique ta ville.';
            if (!$departement)                                               $erreurs[] = 'Code postal français invalide (5 chiffres).';
            if ($err = Utilisateur::erreurMotDePasse($_POST['mot_de_passe'] ?? '', $_POST['mot_de_passe_confirmation'] ?? '')) $erreurs[] = $err;

            if (!$erreurs) {
                $id = Utilisateur::creer($valeurs['pseudo'], $valeurs['email'], $_POST['mot_de_passe'], $valeurs['ville'], $valeurs['code_postal'], $departement);
                Limiteur::compter('inscription', $_SERVER['REMOTE_ADDR'] ?? ''); // seules les inscriptions réussies comptent
                $this->envoyerEmailVerification(Utilisateur::trouverParId($id));
                $_SESSION['email_en_attente'] = $valeurs['email'];
                $this->rediriger('utilisateur/verificationEnvoyee');
            }
        }

        $this->afficher('utilisateurs/inscription', ['valeurs' => $valeurs, 'erreurs' => $erreurs], 'Inscription');
    }

    // Page « regarde tes emails »
    public function verificationEnvoyee(): void
    {
        $this->afficher('utilisateurs/verification_envoyee', [
            'email' => $_SESSION['email_en_attente'] ?? null,
        ], 'Confirme ton email');
    }

    // Lien cliqué depuis l'email de confirmation
    public function verifier(): void
    {
        $jeton = Jeton::trouverValide($_GET['jeton'] ?? '', 'verification');
        if (!$jeton) {
            flash('erreur', 'Ce lien de confirmation est invalide ou a expiré. Connecte-toi pour en recevoir un nouveau.');
            $this->rediriger('utilisateur/connexion');
        }
        Jeton::marquerUtilise($jeton['id']);
        Utilisateur::marquerEmailVerifie($jeton['id_utilisateur']);
        unset($_SESSION['email_en_attente']);

        $u = Utilisateur::trouverParId($jeton['id_utilisateur']);
        $this->ouvrirSession($u);
        flash('succes', 'Email confirmé, bienvenue ' . $u['pseudo'] . ' ! Publie ton premier objet pour commencer ta chaîne.');
        $this->rediriger('accueil');
    }

    public function renvoyerVerification(): void
    {
        $this->exigerPost();
        $u = Utilisateur::trouverParEmail(mb_strtolower(trim($_POST['email'] ?? '')));
        if ($u && !$u['email_verifie'] && !$u['est_banni']) {
            $this->envoyerEmailVerification($u);
        }
        // Même réponse dans tous les cas : on ne révèle pas quels emails sont inscrits
        $_SESSION['email_en_attente'] = $_POST['email'] ?? null;
        flash('succes', 'Si ce compte existe et n\'est pas encore confirmé, un nouvel email vient de partir.');
        $this->rediriger('utilisateur/verificationEnvoyee');
    }

    // ==================== Connexion / déconnexion ====================

    public function connexion(): void
    {
        if (utilisateur_connecte()) $this->rediriger('accueil');

        $erreur = null;
        $nonVerifie = false;
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            $email = mb_strtolower(trim($_POST['email'] ?? ''));
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            if (TentativeConnexion::estBloque($email, $ip)) {
                $erreur = 'Trop de tentatives ratées. Réessaie dans ' . DUREE_BLOCAGE_MINUTES . ' minutes, ou réinitialise ton mot de passe.';
            } else {
                $u = Utilisateur::trouverParEmail($email);
                // Même message si l'email ou le mot de passe est faux : on ne dit pas lequel (sécurité)
                if (!$u || !password_verify($_POST['mot_de_passe'] ?? '', $u['mot_de_passe_hash'])) {
                    TentativeConnexion::enregistrerEchec($email, $ip);
                    $erreur = 'Email ou mot de passe incorrect.';
                } elseif ($u['est_banni']) {
                    $erreur = 'Ce compte a été suspendu par l\'administrateur.';
                } elseif (!$u['email_verifie']) {
                    $erreur = 'Confirme d\'abord ton adresse email : clique sur le lien reçu à l\'inscription.';
                    $nonVerifie = true;
                } else {
                    TentativeConnexion::effacer($email);
                    $this->ouvrirSession($u);
                    flash('succes', 'Content de te revoir, ' . $u['pseudo'] . ' !');
                    $this->rediriger('accueil');
                }
            }
        }

        $this->afficher('utilisateurs/connexion', ['erreur' => $erreur, 'email' => $email, 'nonVerifie' => $nonVerifie], 'Connexion');
    }

    public function deconnexion(): void
    {
        $this->exigerPost();
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('succes', 'Tu es déconnecté. À bientôt !');
        $this->rediriger('accueil');
    }

    // ==================== Mot de passe oublié ====================

    public function motDePasseOublie(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            $u = Utilisateur::trouverParEmail(mb_strtolower(trim($_POST['email'] ?? '')));
            if ($u && !$u['est_banni'] && Limiteur::autoriser('mdp_oublie', $_SERVER['REMOTE_ADDR'] ?? '')) {
                $jeton = Jeton::creer($u['id'], 'reinitialisation', 60);
                Mailer::envoyer(
                    $u['email'], 'Réinitialise ton mot de passe', 'Mot de passe oublié ?',
                    "Bonjour {$u['pseudo']},\nTu as demandé à changer ton mot de passe. Ce lien est valable 1 heure.",
                    URL_SITE . '/' . url('utilisateur/reinitialiser', ['jeton' => $jeton]), 'Choisir un nouveau mot de passe'
                );
            }
            // Message identique que l'email existe ou non (on ne révèle pas qui est inscrit)
            flash('succes', 'Si un compte existe avec cet email, tu vas recevoir un lien pour choisir un nouveau mot de passe.');
            $this->rediriger('utilisateur/connexion');
        }
        $this->afficher('utilisateurs/mot_de_passe_oublie', [], 'Mot de passe oublié');
    }

    public function reinitialiser(): void
    {
        $valeurJeton = $_GET['jeton'] ?? $_POST['jeton'] ?? '';
        $jeton = Jeton::trouverValide($valeurJeton, 'reinitialisation');
        if (!$jeton) {
            flash('erreur', 'Ce lien est invalide ou a expiré. Refais une demande.');
            $this->rediriger('utilisateur/motDePasseOublie');
        }

        $erreur = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();
            $erreur = Utilisateur::erreurMotDePasse($_POST['mot_de_passe'] ?? '', $_POST['mot_de_passe_confirmation'] ?? '');
            if (!$erreur) {
                Utilisateur::changerMotDePasse($jeton['id_utilisateur'], $_POST['mot_de_passe']);
                Jeton::marquerUtilise($jeton['id']);
                // Avoir reçu le lien prouve aussi que l'email est valide
                Utilisateur::marquerEmailVerifie($jeton['id_utilisateur']);
                $u = Utilisateur::trouverParId($jeton['id_utilisateur']);
                TentativeConnexion::effacer($u['email']);
                flash('succes', 'Mot de passe changé ! Tu peux te connecter.');
                $this->rediriger('utilisateur/connexion');
            }
        }
        $this->afficher('utilisateurs/reinitialiser', ['jeton' => $valeurJeton, 'erreur' => $erreur], 'Nouveau mot de passe');
    }

    // ==================== Mon compte ====================

    public function compte(): void
    {
        $session = $this->exigerConnexion();
        $u = Utilisateur::trouverParId($session['id']);
        $erreurs = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verifier();

            if (($_POST['action'] ?? '') === 'localisation') {
                $ville = trim($_POST['ville'] ?? '');
                $cp = trim($_POST['code_postal'] ?? '');
                $dep = departement_depuis_cp($cp);
                if ($ville === '' || mb_strlen($ville) > 100) $erreurs[] = 'Indique ta ville.';
                if (!$dep) $erreurs[] = 'Code postal français invalide (5 chiffres).';
                if (!$erreurs) {
                    Utilisateur::modifierLocalisation($u['id'], $ville, $cp, $dep);
                    flash('succes', 'Localisation mise à jour.');
                    $this->rediriger('utilisateur/compte');
                }
            }

            if (($_POST['action'] ?? '') === 'profil') {
                $bio = trim($_POST['bio'] ?? '');
                if (mb_strlen($bio) > 300) $erreurs[] = 'La bio fait 300 caractères maximum.';
                $reseaux = [];
                foreach (Utilisateur::RESEAUX as $cle => [$nom, $regle]) {
                    $valeur = Utilisateur::nettoyerReseau($_POST[$cle] ?? '');
                    if ($valeur !== '' && !preg_match($regle, $valeur)) {
                        $erreurs[] = "Nom d'utilisateur $nom invalide.";
                    }
                    $reseaux[$cle] = $valeur !== '' ? $valeur : null;
                }
                if (!$erreurs) {
                    Utilisateur::modifierProfilPublic($u['id'], $bio !== '' ? $bio : null, $reseaux);
                    flash('succes', 'Profil mis à jour.');
                    $this->rediriger('utilisateur/compte');
                }
                $u = array_merge($u, $reseaux, ['bio' => $bio]); // on réaffiche ce qui a été tapé
            }

            if (($_POST['action'] ?? '') === 'avatar') {
                if (!empty($_POST['supprimer'])) {
                    Image::supprimer($u['avatar']);
                    Utilisateur::changerAvatar($u['id'], null);
                    flash('succes', 'Photo de profil supprimée.');
                    $this->rediriger('utilisateur/compte');
                }
                if (empty($_FILES['avatar']['name'])) {
                    $erreurs[] = 'Choisis une photo.';
                } else {
                    try {
                        $nouveau = Image::enregistrer($_FILES['avatar'], 'avatar');
                        Image::supprimer($u['avatar']); // l'ancienne photo ne sert plus
                        Utilisateur::changerAvatar($u['id'], $nouveau);
                        flash('succes', 'Nouvelle photo de profil enregistrée !');
                        $this->rediriger('utilisateur/compte');
                    } catch (RuntimeException $ex) {
                        $erreurs[] = $ex->getMessage();
                    }
                }
            }

            if (($_POST['action'] ?? '') === 'notifs') {
                Utilisateur::changerNotifEmail($u['id'], !empty($_POST['notif_email']));
                flash('succes', !empty($_POST['notif_email']) ? 'Tu recevras un email pour les nouveaux messages.' : 'Emails de notification désactivés.');
                $this->rediriger('utilisateur/compte');
            }

            if (($_POST['action'] ?? '') === 'mot_de_passe') {
                if (!password_verify($_POST['ancien'] ?? '', $u['mot_de_passe_hash'])) {
                    $erreurs[] = 'Ton mot de passe actuel est incorrect.';
                } elseif ($err = Utilisateur::erreurMotDePasse($_POST['mot_de_passe'] ?? '', $_POST['mot_de_passe_confirmation'] ?? '')) {
                    $erreurs[] = $err;
                } else {
                    Utilisateur::changerMotDePasse($u['id'], $_POST['mot_de_passe']);
                    Mailer::envoyer($u['email'], 'Ton mot de passe a été modifié', 'Mot de passe modifié',
                        "Bonjour {$u['pseudo']},\nLe mot de passe de ton compte vient d'être changé. Si ce n'est pas toi, utilise tout de suite « Mot de passe oublié ».");
                    flash('succes', 'Mot de passe modifié.');
                    $this->rediriger('utilisateur/compte');
                }
            }
        }

        $this->afficher('utilisateurs/compte', ['u' => $u, 'erreurs' => $erreurs], 'Mon compte');
    }

    // ==================== RGPD : mes données ====================

    // Télécharge un fichier JSON avec toutes les données du membre
    public function exporter(): void
    {
        $u = $this->exigerConnexion();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="mes-donnees-' . strtolower(NOM_SITE) . '.json"');
        echo json_encode(Utilisateur::exporter($u['id']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function supprimerCompte(): void
    {
        $this->exigerPost();
        $session = $this->exigerConnexion();
        $u = Utilisateur::trouverParId($session['id']);

        if ($u['role'] === 'admin') {
            flash('erreur', 'Un compte administrateur ne peut pas être supprimé d\'ici (retire d\'abord le rôle admin dans la base).');
        } elseif (($_POST['confirmation'] ?? '') !== 'SUPPRIMER' || !password_verify($_POST['mot_de_passe'] ?? '', $u['mot_de_passe_hash'])) {
            flash('erreur', 'Pour supprimer ton compte, tape SUPPRIMER et ton mot de passe actuel.');
        } else {
            Utilisateur::anonymiser($u['id']);
            $_SESSION = [];
            session_destroy();
            session_start();
            flash('succes', 'Ton compte a été supprimé. Merci d\'avoir utilisé ' . NOM_SITE . ' !');
            $this->rediriger('accueil');
        }
        $this->rediriger('utilisateur/compte');
    }

    // ==================== Signaler / bloquer un membre ====================

    public function signaler(): void
    {
        $this->exigerPost();
        $u = $this->exigerConnexion();
        $cible = Utilisateur::trouverParId((int) ($_POST['id'] ?? 0));
        $motif = trim($_POST['motif'] ?? '');
        if (!$cible || (int) $cible['id'] === (int) $u['id']) {
            $this->pageIntrouvable();
        }
        if ($motif === '' || mb_strlen($motif) > 255) {
            flash('erreur', 'Indique un motif (255 caractères maximum).');
        } elseif (!Limiteur::autoriser('signalement', (string) $u['id'])) {
            flash('erreur', 'Tu as déjà fait beaucoup de signalements aujourd\'hui.');
        } else {
            SignalementMembre::creer($cible['id'], $u['id'], $motif);
            flash('succes', 'Merci, le membre a été signalé à l\'administrateur.');
        }
        $this->rediriger('utilisateur/profil', ['id' => $cible['id']]);
    }

    public function bloquer(): void
    {
        $this->exigerPost();
        $u = $this->exigerConnexion();
        $cible = Utilisateur::trouverParId((int) ($_POST['id'] ?? 0));
        if (!$cible || (int) $cible['id'] === (int) $u['id']) {
            $this->pageIntrouvable();
        }
        $bloque = Blocage::basculer($u['id'], $cible['id']);
        flash('succes', $bloque
            ? $cible['pseudo'] . ' est bloqué : vous ne pouvez plus vous proposer d\'échanges ni vous écrire.'
            : $cible['pseudo'] . ' est débloqué.');
        $this->rediriger('utilisateur/profil', ['id' => $cible['id']]);
    }

    // ==================== Profil public ====================

    public function profil(): void
    {
        $profil = Utilisateur::trouverParId((int) ($_GET['id'] ?? 0));
        if (!$profil || $profil['supprime'] || ($profil['est_banni'] && !est_admin())) {
            $this->pageIntrouvable();
        }
        $id = (int) $profil['id'];
        $u = utilisateur_connecte();

        $this->afficher('utilisateurs/profil', [
            'profil'      => $profil,
            'objets'      => Objet::disponiblesDe($id),
            'chaine'      => Proposition::chaine($id),
            'avis'        => Avis::recus($id),
            'note'        => Utilisateur::noteMoyenne($id),
            'stats'       => Utilisateur::statistiques($id),
            'estMoi'      => $u && (int) $u['id'] === $id,
            'jeBloque'    => $u && (int) $u['id'] !== $id && Blocage::aBloque($u['id'], $id),
            'meta'        => ['description' => 'Profil de ' . $profil['pseudo'] . ' sur ' . NOM_SITE . ' : sa chaîne d\'échanges, ses objets et ses avis.']
                             + ($profil['avatar'] ? ['image' => URL_SITE . '/uploads/' . $profil['avatar']] : []),
        ], 'Profil de ' . $profil['pseudo']);
    }

    // ==================== Outils internes ====================

    private function envoyerEmailVerification(array $u): void
    {
        $jeton = Jeton::creer($u['id'], 'verification', 60 * 24);
        Mailer::envoyer(
            $u['email'], 'Confirme ton adresse email', 'Bienvenue ' . $u['pseudo'] . ' !',
            "Il ne reste qu'une étape : confirme ton adresse email pour activer ton compte.\nCe lien est valable 24 heures.",
            URL_SITE . '/' . url('utilisateur/verifier', ['jeton' => $jeton]), 'Confirmer mon email'
        );
    }

    private function ouvrirSession(array $u): void
    {
        session_regenerate_id(true); // nouvel identifiant de session à la connexion (sécurité)
        $_SESSION['utilisateur'] = [
            'id' => (int) $u['id'], 'pseudo' => $u['pseudo'], 'role' => $u['role'],
            'departement' => $u['departement'], 'avatar' => $u['avatar'] ?? null,
        ];
    }
}
