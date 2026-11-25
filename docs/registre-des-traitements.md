# Registre des activités de traitement — Visota

> Document interne exigé par le RGPD (article 30). Il ne se publie pas : il se garde et se met à jour,
> et doit pouvoir être présenté à la CNIL si elle le demande. Modèle simplifié à compléter.

**Responsable du traitement :** [Prénom Nom], [ville] — contact@visota.fr
**Hébergeur (sous-traitant) :** o2switch, Clermont-Ferrand, France — serveurs en France
**Dernière mise à jour :** [date]

## Traitement n°1 — Gestion des comptes membres
| Rubrique | Contenu |
|---|---|
| Finalité | Créer et gérer les comptes, authentification, sécurité |
| Base légale | Exécution du contrat (CGU acceptées à l'inscription) |
| Personnes concernées | Membres du site (18 ans et plus) |
| Données | Pseudo, email, mot de passe chiffré (bcrypt), code postal, ville, département, date d'inscription, date de dernière connexion ; facultatif : photo, bio, réseaux sociaux |
| Destinataires | Éditeur ; o2switch (hébergement) |
| Transferts hors UE | Aucun |
| Durée de conservation | Durée du compte ; comptes non confirmés supprimés après 7 jours ; comptes inactifs 3 ans anonymisés |
| Sécurité | HTTPS, hachage bcrypt, limitation des tentatives de connexion, jetons email hachés et à usage unique, CSP, protection CSRF/XSS/injection SQL |

## Traitement n°2 — Annonces, échanges, messagerie et avis
| Rubrique | Contenu |
|---|---|
| Finalité | Permettre la publication d'annonces et les échanges entre membres |
| Base légale | Exécution du contrat |
| Données | Annonces, photos (métadonnées EXIF supprimées), propositions, messages, avis, favoris, blocages |
| Destinataires | Membres concernés (messages : uniquement les 2 participants) ; éditeur |
| Durée de conservation | Durée du compte ; après suppression : compte anonymisé, contenus retirés |

## Traitement n°3 — Modération et signalements
| Rubrique | Contenu |
|---|---|
| Finalité | Lutter contre les contenus illicites et les abus (obligations LCEN et DSA) |
| Base légale | Obligation légale / intérêt légitime |
| Données | Signalements (auteur, motif, date), décisions de modération et motifs |
| Durée de conservation | Durée du compte concerné |

## Traitement n°4 — Sécurité et anti-spam
| Rubrique | Contenu |
|---|---|
| Finalité | Bloquer les attaques par force brute et les robots |
| Base légale | Intérêt légitime |
| Données | Adresse IP et email lors des connexions ratées ; compteurs d'actions |
| Durée de conservation | 2 jours (purge automatique) |

## Traitement n°5 — Cookies et consentement
| Rubrique | Contenu |
|---|---|
| Finalité | Fonctionnement du site (session) ; preuve du consentement |
| Base légale | Cookies nécessaires : exemptés ; autres : consentement |
| Données | Cookie de session ; choix de consentement (identifiant aléatoire, choix, date — sans IP) |
| Durée de conservation | Session ; choix 6 mois ; preuve 13 mois |

## En cas de violation de données (fuite, piratage)
1. Contenir l'incident (changer les mots de passe de la base et de cPanel, corriger la faille).
2. Noter dans ce document : date, nature, données et personnes concernées, mesures prises.
3. Si risque pour les personnes : notifier la CNIL **sous 72 h** (notifications.cnil.fr) et prévenir les membres si le risque est élevé.
