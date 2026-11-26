# Visota — V1.7

> Pars d'un trombone. Vise une voiture.

Site de troc « vers le haut » (anciennement « Échange vers le haut ») : on part d'un petit objet et on l'échange contre un peu mieux à chaque fois.
PHP 8 + MySQL, architecture MVC, sans framework.

## Première installation (MAMP, Mac)

1. **Ouvre MAMP** → **Start** (Apache et MySQL au vert).
2. **Copie le dossier** `projet-echange` dans `/Applications/MAMP/htdocs/`.
3. **Autorise l'écriture** des photos et des emails de test (Terminal) :
   ```bash
   chmod 777 /Applications/MAMP/htdocs/projet-echange/public/uploads /Applications/MAMP/htdocs/projet-echange/storage/emails
   ```
4. **Crée la base vide** : phpMyAdmin → onglet **SQL** → colle `sql/creer_base.sql` → **Exécuter**.
5. **Ouvre le site** : http://localhost:8888/projet-echange/public/ → page **Installation du site** → bouton **Installer**.
6. **Inscris-toi**, confirme ton email dans **📬 Boîte mail de test** (lien en bas de page), puis dans phpMyAdmin :
   ```sql
   UPDATE utilisateur SET role = 'admin' WHERE email = 'ton.email@exemple.fr';
   ```

Les réglages par défaut correspondent à MAMP. Si les tiens sont différents (ports, mot de passe…),
copie `config/config.local.exemple.php` en **`config/config.local.php`** et modifie-le : ce fichier n'est jamais écrasé.

## 🔄 Mettre à jour le site (sans perdre tes données)

Quand tu reçois une nouvelle version :

1. **Dézippe** la nouvelle version dans Téléchargements (sans la mettre dans MAMP).
2. **Copie le nouveau code** avec le script (Terminal) :
   ```bash
   bash /Applications/MAMP/htdocs/projet-echange/outils/mettre-a-jour.sh ~/Downloads/projet-echange
   ```
   Il remplace le code, mais ne touche **jamais** à tes photos (`public/uploads`), à tes emails de test (`storage`)
   ni à tes réglages (`config/config.local.php`).
3. **Ouvre le site connecté en admin** : s'il y a des changements de base de données, la page
   **Mise à jour de la base** s'affiche toute seule → bouton **Appliquer les mises à jour**.
   (Elle est aussi accessible depuis la page Admin.) Pendant ce temps, les visiteurs voient « Site en maintenance ».

⚠️ Ne fais plus glisser le dossier dans le Finder pour le remplacer : le Finder supprime l'ancien dossier entier, photos comprises.

### Comment ça marche : les migrations
Chaque changement de structure de la base est un fichier numéroté dans `sql/migrations/`
(`001_base.sql`, `002_notifications.sql`…). La table `migration` retient ceux déjà appliqués :
à chaque mise à jour, seuls les **nouveaux** fichiers sont exécutés. Les données restent en place.

Pour repartir de zéro (efface tout !) : exécute `sql/reinitialiser.sql`, puis ouvre le site pour réinstaller.

## Téléphone, photos et mots-clés (V1.11)

- **Menu téléphone** (bouton « Menu », marche sans JavaScript) avec tous les libellés : Mes échanges, Mes favoris, Mon profil, Mon compte…
  Annonces sur 2 colonnes et filtres repliables sur petit écran. Boutons « Ajouter aux favoris » / « Retirer des favoris ».
- **Photos jusqu'à 10 Mo** (`TAILLE_MAX_PHOTO` + limites PHP dans `public/.htaccess` pour MAMP ; sur o2switch :
  cPanel → Sélectionner une version de PHP → Options : `upload_max_filesize` 10M, `post_max_size` 64M, `memory_limit` 256M).
- **SEO** : titres et descriptions avec « troc », « échange », « près de chez vous » ; texte de présentation sur l'accueil ;
  pages **« troc près de chez vous » par département** (`troc/34-herault`), indexées et dans le sitemap.

## Notifications par email (V1.10)

- Trois types d'emails, chacun activable ou non dans **Mon compte → Notifications** :
  **intérêt** (quelqu'un ajoute une annonce en favori, sans dire qui, au plus 1 email par jour et par annonce),
  **propositions** (reçue, acceptée, refusée, annulée), **messages** (1 email par échange tant qu'il n'est pas relu).
- Chaque email a un lien **« Ne plus recevoir ces emails »** qui marche sans se connecter (lien signé avec `CLE_SECRETE`)
  et les en-têtes `List-Unsubscribe` : Gmail/Outlook affichent leur bouton « Se désabonner ».
- Migration `008` : colonnes `notif_interet`, `notif_proposition`, `notif_message` (reprennent l'ancien réglage unique).

## Référencement (V1.9)

- **Adresses propres** : `annonce/12-t-shirt-nike`, `categorie/3-vetements`, `le-concept`… (règle dans `public/.htaccess`,
  traduction dans `app/core/UrlPropre.php`). Sans le module Apache `mod_rewrite`, le site garde les adresses `index.php?route=…`.
- **Balises pour Google** sur chaque page : titre, description, `robots` (index ou noindex), adresse `canonical`, Open Graph,
  fil d'Ariane en données structurées (schema.org). Seules les pages publiques utiles sont indexées ; comptes, messages,
  formulaires et combinaisons de filtres sont en `noindex`.
- **`sitemap.xml`** (accueil, catégories, annonces) et **`robots.txt`** générés automatiquement (`SeoController`).
- **Vraies erreurs 404** pour les adresses qui n'existent pas.
- Texte de présentation de chaque catégorie (migration `007`), modifiable dans Admin → Catégories et filtres.

## Catégories et filtres (V1.8)

- **Sous-catégories** : Vêtements › T-shirts, Chaussures… Chercher dans « Vêtements » montre aussi toutes ses sous-catégories.
- **Filtres par catégorie** (Taille, Pointure, Plateforme, Couleur, Marque…) : demandés à la publication, proposés dans la recherche
  seulement pour la catégorie choisie. Un filtre rattaché à une catégorie principale vaut pour toutes ses sous-catégories.
- **Tout se gère depuis l'admin** (Admin → 🗂️ Catégories et filtres), sans toucher au code.
- Modèle de données (migration `006`) : `categorie(#id_parent)`, `caracteristique`, `caracteristique_option`,
  `categorie_caracteristique` (quelle catégorie a quel filtre), `objet_caracteristique` (la valeur choisie par une annonce).
- Page **« Le concept »** (`page/concept`) : le principe, l'histoire du trombone rouge, les règles, la sécurité, une FAQ.

## Sécurité et conformité (V1.7)

- **En-têtes de sécurité** envoyés par `app/core/Securite.php` : Content-Security-Policy (aucun script extérieur ni intégré
  dans les pages n'est exécuté), HSTS, nosniff, Permissions-Policy. Tout le JavaScript est dans `public/js/`.
- **Erreurs masquées en production** (`MODE_DEV = false`) : écrites dans le journal d'erreurs du serveur, jamais affichées.
- **Session** : cookie `visota_session` HttpOnly + SameSite, déconnexion après 2 h d'inactivité.
- **Mots de passe** conformes à la recommandation CNIL 2022 (8 caractères et 3 types, ou phrase de 16 caractères ou plus).
- **Nettoyage RGPD automatique** une fois par jour (`app/models/Maintenance.php`) + bouton dans l'admin.
- **Modération motivée (DSA)** : un membre dont l'annonce est retirée ou le compte suspendu reçoit un email avec le motif.
- **Bandeau cookies** conforme CNIL : Tout refuser / Tout accepter au même niveau, Personnaliser, choix gardé 6 mois,
  lien « Gérer mes cookies » en bas de page. Catégories dans `app/core/Cookies.php`.
- **Registre des traitements** (document RGPD interne) : `docs/registre-des-traitements.md` à compléter et garder à jour.

### Ajouter un service soumis au consentement (ex. statistiques)
1. Déclare-le dans `app/core/Cookies.php` (catégorie, nom, utilité, durée) et augmente `VERSION` (tout le monde est re-questionné).
2. Dans une vue, écris le script ainsi : `<script type="text/plain" data-consentement="mesure" data-src="https://…"></script>`.
   Il ne sera chargé que si le visiteur a accepté.
3. Autorise son domaine dans la Content-Security-Policy de `app/core/Securite.php`.

## Avant la mise en ligne : pages légales
Les pages Mentions légales, Confidentialité et CGU sont des **modèles**. Renseigne `EDITEUR_NOM`, `EDITEUR_EMAIL`,
`HEBERGEUR_NOM`… dans `config/config.local.php` (voir l'exemple) et fais-les relire. Tant que ce n'est pas fait,
un bandeau rouge « Modèle à compléter » s'affiche sur ces pages.

Les limites anti-spam se règlent dans `app/models/Limiteur.php` (tableau `REGLES`).

## Changer le nom du site
Le nom et le slogan sont dans `config/config.php` (`NOM_SITE`, `SLOGAN`) — à surcharger dans `config.local.php`.
Le logo est `public/img/logo.svg` (et `public/favicon.svg`).

## Envoyer de vrais emails (plus tard)

Par défaut `MAIL_MODE` vaut `'fichier'` : les emails sont enregistrés dans `storage/emails/` et lisibles dans la boîte mail de test.
Pour de vrais envois, dans `config/config.php` :
1. `MAIL_MODE` → `'smtp'`
2. Remplis `SMTP_UTILISATEUR` et `SMTP_MOT_DE_PASSE`. Avec Gmail : active la validation en 2 étapes sur ton compte Google,
   puis crée un **mot de passe d'application** (Compte Google → Sécurité) et mets-le dans `SMTP_MOT_DE_PASSE`.
3. Une fois en ligne : `MODE_DEV` → `false` et `URL_SITE` → l'adresse de ton site.

La bibliothèque utilisée est PHPMailer (dossier `lib/PHPMailer`, licence LGPL).

## Comment c'est organisé (MVC)

```
projet-echange/
├── public/               ← seul dossier visible depuis le navigateur
│   ├── index.php         ← le ROUTEUR : toutes les pages passent par ici (?route=controleur/action)
│   ├── css/style.css
│   ├── img/              ← image « pas de photo »
│   └── uploads/          ← photos envoyées par les membres
├── app/
│   ├── core/             ← le moteur : connexion BDD, classe Controller de base, fonctions utiles
│   ├── models/           ← MODÈLES : les requêtes SQL (une classe par table)
│   ├── controllers/      ← CONTRÔLEURS : reçoivent la demande, vérifient, appellent le modèle, choisissent la vue
│   └── views/            ← VUES : le HTML affiché
├── lib/PHPMailer/        ← bibliothèque d'envoi d'emails
├── storage/emails/       ← emails de test (mode développement)
├── config/config.php     ← paramètres par défaut (remplacé à chaque mise à jour)
├── config/config.local.php ← TES réglages (à créer si besoin, jamais écrasé)
├── outils/mettre-a-jour.sh ← copie une nouvelle version sans toucher à tes données
└── sql/
    ├── creer_base.sql    ← à exécuter une seule fois (base vide)
    ├── migrations/       ← changements de la base, numérotés et appliqués une seule fois
    └── reinitialiser.sql ← ⚠️ efface tout
```

**Exemple du trajet d'une page** — `index.php?route=objet/voir&id=3` :
1. `public/index.php` trouve la route `objet/voir` → `ObjetController::voir()`
2. le contrôleur appelle le modèle `Objet::trouverParId(3)` → requête SQL
3. il passe le résultat à la vue `app/views/objets/voir.php` qui affiche le HTML

## Ce qui marche dans la V1

- Inscription avec **confirmation par email**, connexion, déconnexion
- **Mot de passe oublié** (lien par email valable 1 h, à usage unique)
- Page **Mon compte** : changer sa localisation et son mot de passe
- Publier / modifier / retirer un objet avec photo
- **Accueil organisé** : recherche rapide, catégories, annonces près de chez toi, dernières annonces
- **Page Chercher** : mot-clé, catégorie, département, état, valeur min/max, tri, pagination (12 par page)
- Proposer un échange, accepter, refuser, annuler
- À l'acceptation : **les objets changent de propriétaire** (on peut réechanger l'objet reçu → c'est ça qui fait la chaîne)
- **Profils enrichis** (tout facultatif) : photo de profil, bio, Instagram / TikTok / Snapchat / YouTube,
  statistiques (échanges, note, multiplication de la valeur de départ)
- **Accueil vitrine** : présentation du concept, chaîne d'exemple animée, chiffres du site, « Les plus belles chaînes »
- **Photos optimisées** : redimensionnées (1200 px max, avatars 400×400) et compressées automatiquement
- **Aperçu de partage** : image et description quand un lien est partagé sur les réseaux / messageries
- **Jusqu'à 5 photos par annonce** avec galerie, **favoris** ❤️, statut **réservé**, **suggestions** d'objets à proposer
- **Emails de notification** (nouvelle proposition / nouveau message / proposition acceptée), au plus un par échange tant qu'il n'est pas lu, désactivables
- **Signaler ou bloquer un membre**, modération des membres signalés dans l'admin
- **Badges** sur le profil et **image de chaîne à partager** (format story, générée dans le navigateur)
- **RGPD** : télécharger ses données (JSON), supprimer son compte (anonymisation), pages légales (modèles à compléter)
- **Anti-spam** : limites d'actions (inscriptions, annonces, propositions, messages, signalements) + champ piège à robots
- Messagerie liée à chaque échange
- **Notifications** : badge rouge dans le menu (mis à jour toutes les 30 s sans recharger), chiffre dans l'onglet du navigateur,
  échanges avec du nouveau surlignés dans « Mes échanges », séparateur « Nouveaux messages » dans la discussion
- Avis (note sur 5) après un échange accepté
- Profil avec **chaîne d'échanges** illustrée
- Signalement d'annonce
- **Espace admin** : traiter les signalements, retirer une annonce, bannir / débannir

## Sécurité déjà en place

| Risque | Protection | Où regarder |
|---|---|---|
| Vol des mots de passe | `password_hash()` / `password_verify()` | `models/Utilisateur.php` |
| Injection SQL | Requêtes préparées PDO partout | tous les modèles |
| Faille XSS | Tout affichage passe par `e()` (htmlspecialchars) | `core/helpers.php` |
| Formulaires piégés (CSRF) | Jeton caché vérifié sur chaque POST | `core/helpers.php` |
| Fichier malveillant envoyé comme photo | Vrai type vérifié (finfo), 2 Mo max, nom aléatoire, PHP désactivé dans `uploads/` | `models/Objet.php` |
| Accès aux pages admin | Vérification du rôle à chaque page | `core/Controller.php` |
| Vol de session | `session_regenerate_id()` à la connexion, cookie `httponly` + `samesite` | `UtilisateurController.php`, `public/index.php` |
| Faux comptes | Email à confirmer avant de pouvoir se connecter | `UtilisateurController::verifier()` |
| Robots qui testent des mots de passe | Blocage 15 min après 5 échecs sur un compte (20 sur une IP) | `models/TentativeConnexion.php` |
| Liens email volés en base | Seule l'empreinte SHA-256 du jeton est stockée, usage unique, expiration | `models/Jeton.php` |
| Savoir qui est inscrit | « Mot de passe oublié » répond pareil que l'email existe ou non | `UtilisateurController::motDePasseOublie()` |

## À faire avant une vraie mise en ligne

- Passer `MODE_DEV` à `false` et configurer le SMTP
- Pages **mentions légales**, **politique de confidentialité**, **CGU**
- Possibilité pour un membre de **supprimer son compte** (RGPD)
- Mot de passe très solide sur le compte admin, HTTPS chez l'hébergeur
