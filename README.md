# Cre8Connect

## Présentation du projet

**Cre8Connect** est une plateforme web de collaboration entre **marques** et **créateurs de contenu**.

La plateforme facilite la mise en relation professionnelle entre les marques qui souhaitent promouvoir leurs produits, services ou campagnes, et les créateurs capables de produire du contenu adapté à leurs besoins.

Cre8Connect regroupe plusieurs espaces fonctionnels : gestion des utilisateurs, publications, commentaires, campagnes, produits, contrats, événements, forums, offres, candidatures, réclamations, statistiques, assistance intelligente, sécurité et supervision administrative avancée.

Le projet s’inscrit dans la thématique :

**Économie digitale, entrepreneuriat et futur du travail**

---

## État actuel du projet

Le projet est dans une version avancée destinée à la présentation finale.

Les travaux réalisés ne se limitent plus aux fonctionnalités CRUD classiques. La plateforme inclut maintenant une logique complète de collaboration, de supervision, de sécurité et d’assistance intelligente :

- FrontOffice pour les créateurs et les marques.
- BackOffice pour les administrateurs.
- Gestion des rôles : créateur, marque, administrateur, super administrateur et hyper administrateur.
- Cycle complet offre → candidature → négociation → acceptation/refus → contrat.
- Assistant intelligent **Cre8Pilot**.
- Système de sécurité **Cre8Shield**.
- Recommandation intelligente de créateurs.
- Base de données finale réaliste et cohérente.
- Déploiement possible sur Raspberry Pi 5 avec Apache, MariaDB et tunnel HTTPS.
- Supervision serveur et sécurité prévue dans l’espace Hyper Admin.

---

## Objectifs du projet

- Permettre aux utilisateurs de s’authentifier et d’accéder à la plateforme selon leur rôle : **créateur**, **marque** ou **administrateur**.
- Offrir aux créateurs un espace pour publier du contenu, interagir avec la communauté, suivre leurs performances et postuler à des offres.
- Permettre aux marques de publier des offres, gérer des campagnes, associer des produits, consulter les candidatures et collaborer avec les créateurs.
- Gérer les contrats, la signature électronique et le suivi des collaborations.
- Offrir un espace d’événements et de forums pour encourager la communication, la formation et l’échange d’expériences.
- Fournir un BackOffice administratif pour gérer les utilisateurs, les réclamations, les événements, les forums, les campagnes, les statistiques et les alertes de sécurité.
- Intégrer des fonctionnalités avancées basées sur l’intelligence artificielle pour assister les utilisateurs, analyser les données, générer du contenu et renforcer la sécurité.
- Améliorer l’expérience utilisateur grâce aux dashboards, filtres, recherches avancées, modes sombre/clair, statistiques dynamiques et interfaces interactives.
- Ajouter une couche de supervision Hyper Admin : logs, actions administratives, restauration de comptes, avertissements et surveillance serveur.

---

## Rôles principaux

### Créateur de contenu

Le créateur peut :

- consulter les offres disponibles ;
- envoyer des candidatures ;
- négocier avec les marques ;
- publier des posts ;
- commenter et réagir aux publications ;
- participer aux événements ;
- accéder aux forums ;
- suivre ses statistiques de performance ;
- utiliser Cre8Pilot pour améliorer ses candidatures, ses messages et ses contenus.

### Marque

La marque peut :

- créer et gérer des offres ;
- consulter les candidatures reçues ;
- accepter, refuser ou négocier avec les créateurs ;
- créer des campagnes ;
- gérer des produits ;
- générer et signer des contrats ;
- suivre les performances des collaborations ;
- utiliser l’IA pour proposer des campagnes, améliorer les textes, préparer les réponses et recommander des créateurs.

### Administrateur

L’administrateur peut :

- gérer les utilisateurs ;
- traiter les réclamations ;
- surveiller les comptes suspects ;
- consulter les statistiques globales ;
- gérer les événements et forums ;
- suivre les campagnes ;
- consulter les alertes de sécurité ;
- superviser l’activité de la plateforme depuis le BackOffice.

### Super Admin

Le Super Admin supervise un grand module fonctionnel :

- utilisateurs et réclamations ;
- publications et commentaires ;
- campagnes, produits et contrats ;
- événements et forums.

Il peut valider certaines actions administratives, contrôler les actions des administrateurs réguliers et transmettre les cas sensibles à l’Hyper Admin.

### Hyper Admin

L’Hyper Admin est le niveau de supervision le plus élevé.

Il peut :

- consulter les logs administratifs ;
- annuler certaines actions d’un administrateur ;
- restaurer un compte suspendu ou supprimé logiquement ;
- envoyer des avertissements aux administrateurs ;
- superviser Cre8Shield ;
- consulter les informations serveur ;
- suivre les sauvegardes et les événements de sécurité.

---

## Architecture du projet

Le projet suit une architecture **MVC** :

- **Modèle** : contient les classes métier et les entités manipulées par la plateforme.
- **Vue** : contient les pages FrontOffice et BackOffice affichées aux utilisateurs.
- **Contrôleur** : assure le lien entre les vues, les modèles et la base de données.

Le projet est organisé en deux grands espaces.

### FrontOffice

Espace destiné aux créateurs et aux marques. Il permet l’utilisation normale de la plateforme : consultation des offres, candidatures, campagnes, posts, commentaires, événements, forums, etc.

### BackOffice

Espace destiné aux administrateurs. Il permet la supervision, la gestion, les statistiques, le traitement des réclamations, la modération, la sécurité et la supervision avancée.

---

## Modules du projet

## 1. Module Utilisateur / Réclamation

Ce module assure la gestion des comptes, des rôles, de l’authentification et des réclamations.

### Fonctionnalités principales

- Gestion des comptes utilisateurs.
- Authentification selon le rôle : créateur, marque, administrateur, super administrateur ou hyper administrateur.
- Gestion des réclamations.
- Traitement administratif des demandes.
- Tableau de bord statistique pour les utilisateurs et les réclamations.
- Recherche, filtrage et tri des utilisateurs ou réclamations.
- Détection et suspension des comptes suspects.
- Restauration de compte après vérification.
- Notification ou email lorsqu’un compte est restauré.
- Notification par email lorsqu’un administrateur répond à une réclamation.
- Récupération de mot de passe par email.
- Connexion sécurisée par reconnaissance faciale.

### Valeur ajoutée

Ce module représente la base de sécurité de Cre8Connect. Il garantit un accès contrôlé à la plateforme, protège les comptes utilisateurs et permet une gestion claire des réclamations.

---

## 2. Module Post / Commentaire / Réaction

Ce module permet aux créateurs de publier du contenu et d’interagir avec la communauté.

### Fonctionnalités principales

- Création, modification, suppression et consultation des posts.
- Gestion des commentaires.
- Réactions aux posts et aux commentaires.
- Recherche avancée par ID créateur, ID post, ID commentaire, sujet ou nom du créateur.
- Statistiques dynamiques sur les vues, likes, dislikes et performances.
- Dashboard de performance pour les créateurs.
- Pagination avancée pour améliorer la navigation.
- Système de classement des contenus tendance.
- Support des emojis et stickers dans les commentaires.
- Mode sombre et mode clair dans le FrontOffice et le BackOffice.
- Chatbot IA déplaçable par drag-and-drop.
- Dictée vocale multilingue.
- Génération de posts par intelligence artificielle.
- Analyse et description automatique des images uploadées.
- Modération par administrateur avec possibilité de révision.

### Valeur ajoutée

Ce module transforme l’espace communautaire en un environnement interactif et intelligent. Les créateurs peuvent suivre leurs performances, créer du contenu plus rapidement et améliorer leur engagement avec la communauté.

---

## 3. Module Campagne / Produit / Contrat

Ce module organise les collaborations commerciales entre les marques et les créateurs à travers les campagnes, les produits et les contrats.

### Fonctionnalités principales

- Création et gestion des campagnes.
- Définition des objectifs et budgets.
- Suivi des performances des campagnes.
- Ajout et gestion des produits.
- Description des caractéristiques des produits.
- Association des produits aux campagnes.
- Génération de contrats.
- Signature électronique.
- Suivi des collaborations.
- Gestion des états : actif, en attente, signé, résilié ou expiré selon le contexte.

### Fonctionnalités IA

- Suggestion de campagnes adaptées aux créateurs.
- Recommandation de créateurs compatibles.
- Recommandation de produits pertinents.
- Score de compatibilité entre créateur, marque, produit et campagne.
- Prédiction ou estimation de performance.
- Génération automatique de clauses et propositions de contrat.
- Génération d’idées de campagnes pour les marques.
- Proposition de slogans, types de contenu, budgets et créateurs adaptés.
- Analyse des campagnes dans le dashboard administrateur.
- Détection d’anomalies, fraude ou faux engagement.

### Valeur ajoutée

Ce module permet de professionnaliser les collaborations entre marques et créateurs. Il aide les marques à mieux préparer leurs campagnes et permet aux administrateurs de suivre la performance globale de la plateforme.

---

## 4. Module Événement / Forum

Ce module permet d’organiser des événements et de créer des espaces de discussion liés aux événements.

### Événements — BackOffice

L’administrateur dispose d’un dashboard contenant :

- total des événements ;
- total des inscriptions ;
- taux de remplissage ;
- événements à venir ;
- graphiques de participation ;
- répartition des événements par type ;
- Top 5 des événements les plus suivis.

L’administrateur peut aussi :

- créer un événement ;
- modifier un événement ;
- supprimer un événement ;
- rechercher et trier les événements ;
- gérer les images, types, statuts, dates, lieux, capacités et adresses.

### Événements — FrontOffice

Les créateurs et marques peuvent :

- consulter les événements disponibles ;
- rechercher un événement ;
- filtrer par type : formation, webinaire, meetup, atelier ;
- filtrer par lieu : en ligne ou présentiel ;
- consulter les détails d’un événement ;
- s’inscrire ou rejoindre une liste d’attente ;
- voir la localisation via Google Maps ;
- accéder au forum lié à l’événement.

### Forums — BackOffice

L’administrateur peut :

- consulter les forums ;
- voir les statistiques des messages ;
- suivre les participants actifs ;
- supprimer des forums ou messages signalés ;
- consulter les messages reportés ;
- voir les forums les plus actifs ;
- générer automatiquement les forums des événements du jour.

### Forums — FrontOffice

Les utilisateurs peuvent :

- accéder aux forums depuis les événements ;
- lire les messages ;
- publier de nouveaux messages ;
- signaler des messages ;
- participer aux discussions liées aux événements.

### Valeur ajoutée

Ce module renforce l’aspect communautaire de Cre8Connect. Il permet aux utilisateurs de participer à des événements, d’échanger autour de sujets professionnels et de créer une dynamique de collaboration.

---

## 5. Module Offre / Candidature

Ce module gère le cycle complet de collaboration entre les marques et les créateurs, depuis la publication d’une offre jusqu’à la décision finale.

### Fonctionnalités principales

Pour les marques :

- créer une offre ;
- définir un budget ;
- préciser les objectifs ;
- indiquer les attentes envers le créateur ;
- consulter les candidatures reçues ;
- analyser les profils ;
- négocier ;
- accepter ou refuser une candidature ;
- suivre l’état des offres.

Pour les créateurs :

- consulter les offres disponibles ;
- rechercher et filtrer les offres ;
- lire les détails d’une offre ;
- envoyer une candidature ;
- proposer un message personnalisé ;
- proposer un budget ou un délai ;
- suivre l’état des candidatures.

Pour l’administrateur :

- consulter les offres ;
- consulter les candidatures ;
- surveiller les risques ;
- traiter les alertes de sécurité.

### Entités principales

- **Offre** : représente une opportunité de collaboration publiée par une marque.
- **Candidature** : représente la demande envoyée par un créateur.
- **Négociation** : représente l’échange entre marque et créateur autour du budget, du délai ou des livrables.
- **Cre8Shield Catch** : représente les alertes de sécurité détectées par le système.

### Système de brouillons acceptation / refus / négociation

Le module inclut un système de brouillons pour les trois fenêtres principales :

- acceptation ;
- refus ;
- négociation.

Cre8Pilot peut préparer un texte adapté au contexte, ouvrir la fenêtre correspondante et remplir le champ de note ou de message. L’utilisateur reste toujours responsable de la validation finale.

### Valeur ajoutée

Ce module ne se limite pas à afficher des offres. Il accompagne les marques et les créateurs pendant tout le processus de collaboration : création d’offre, candidature, analyse, négociation, décision finale et sécurité.

---

## Cre8Pilot — Assistant intelligent

**Cre8Pilot** est l’assistant IA intégré à Cre8Connect. Il est conçu pour être :

- conscient de la page actuelle ;
- conscient du rôle de l’utilisateur ;
- capable d’utiliser les données visibles ;
- limité par des règles de sécurité ;
- utile sans exécuter de décisions finales à la place de l’utilisateur.

### Capacités principales

Cre8Pilot peut :

- préparer une offre ;
- améliorer une description ;
- recommander des créateurs ;
- préparer une candidature ;
- résumer une offre ;
- comparer les offres visibles ;
- préparer une réponse de négociation ;
- préparer une note d’acceptation ;
- préparer une note de refus ;
- utiliser un CV ou portfolio uploadé par l’utilisateur ;
- ouvrir une fenêtre d’action sans valider l’action finale ;
- aider l’utilisateur à naviguer vers une offre, une candidature ou une fenêtre précise.

### Interface et expérience utilisateur

Les améliorations prévues ou intégrées autour de Cre8Pilot concernent :

- meilleure fenêtre IA ;
- avatar avec états visuels : idle, thinking, filling, warning, success ;
- upload direct de PDF/TXT/CV depuis la fenêtre ;
- support d’images lorsque le contexte le permet ;
- meilleure traduction de l’interface Cre8Pilot ;
- mode vocal amélioré ;
- mise en évidence des champs modifiés ;
- navigation assistée sans action finale automatique.

### Actions IA sécurisées

Actions autorisées :

- remplir un formulaire ;
- ouvrir une fenêtre de négociation ;
- ouvrir une fenêtre d’acceptation ou de refus ;
- appliquer un filtre ;
- faire une recherche ;
- trier les résultats ;
- mettre le focus sur un champ ;
- surligner les champs modifiés ;
- préparer un brouillon.

Actions interdites :

- publier une offre automatiquement ;
- supprimer une offre automatiquement ;
- envoyer une candidature automatiquement ;
- accepter une candidature automatiquement ;
- refuser une candidature automatiquement ;
- envoyer une réponse finale automatiquement ;
- supprimer des données ;
- usurper l’identité d’un utilisateur ;
- exposer des données cachées ou sensibles.

---

## Cre8Shield — Sécurité et analyse de risque

**Cre8Shield** est la couche de sécurité de Cre8Connect. Il analyse les messages, formulaires, liens, documents et comportements suspects.

### Risques détectés

- phishing ;
- lien suspect ;
- paiement hors plateforme ;
- demande de mot de passe ou code de connexion ;
- faux support ;
- QR invoice suspect ;
- fichier ZIP suspect ;
- injection SQL ;
- XSS ;
- prompt injection ;
- usurpation d’identité ;
- faux engagement ;
- demande de suppression d’historique ;
- tentative d’accès à des fichiers sensibles du serveur.

### Cre8Shield Input Watcher

Cre8Shield peut être utilisé comme vérificateur avant soumission pour :

- offres ;
- candidatures ;
- messages de négociation ;
- posts ;
- commentaires ;
- messages de forum ;
- réclamations ;
- descriptions de campagnes ;
- descriptions de produits ;
- sections “à propos” des profils.

Selon le score de risque, le système peut :

- autoriser la soumission ;
- afficher un avertissement ;
- demander confirmation ;
- bloquer l’entrée ;
- créer une alerte pour le BackOffice.

### Analyse de risque avancée

Cre8Shield peut combiner :

- règles déterministes ;
- extraction d’entités suspectes : URL, domaine, email, téléphone, mot de paiement, mot de passe, script, expression SQL ;
- score de risque ;
- catégories de risque ;
- explication lisible pour l’administrateur.

L’idée d’utiliser un modèle NER cybersécurité a été étudiée. Le modèle externe initialement testé n’a pas été retenu car il n’était plus supporté par le fournisseur. Le projet privilégie donc une approche plus stable : règles, extraction d’entités, classification simple et explication assistée.

---

## Recommandation intelligente de créateurs

Cre8Connect inclut une logique de recommandation pour aider les marques à choisir les créateurs les plus adaptés à une offre ou une campagne.

La recommandation peut exploiter :

- le profil du créateur ;
- la section “à propos” ;
- les spécialités ;
- les posts publiés ;
- les candidatures envoyées ;
- les candidatures acceptées ;
- l’historique de collaboration ;
- la compatibilité de budget ;
- le type de contenu demandé ;
- les signaux de confiance ou de risque.

Le résultat attendu est un classement avec un score et des raisons explicables. La recommandation reste une aide à la décision : la marque choisit toujours manuellement.

---

## BackOffice avancé et Hyper Admin

Le BackOffice ne sert pas uniquement à gérer les tableaux classiques. Il inclut une logique de supervision avancée.

### Server Center

Le Server Center est prévu pour l’Hyper Admin. Il permet de suivre l’état du serveur :

- statut Apache ;
- statut MariaDB ;
- statut ngrok ;
- CPU, RAM, disque ;
- uptime ;
- dernier backup ;
- dernier commit Git ;
- erreurs récentes ;
- événements de sécurité serveur.

### Logs administratifs et annulation

Les actions importantes des administrateurs peuvent être enregistrées :

- suspension d’un utilisateur ;
- restauration d’un compte ;
- changement de rôle ;
- suppression ou restauration d’un commentaire ;
- fermeture d’un forum ;
- archivage d’un produit ;
- traitement d’une réclamation ;
- avertissement envoyé à un administrateur.

Certaines actions peuvent être annulées si les anciennes données sont conservées.

### Avertissements administrateurs

L’Hyper Admin ou un Super Admin peut avertir un administrateur avant une suspension. L’objectif est de corriger les comportements risqués ou les erreurs de modération sans bloquer directement l’accès.

### Restauration de compte

Lorsqu’un compte suspendu est restauré après vérification, un email ou une notification peut informer l’utilisateur que son compte est de nouveau accessible.

---

## Base de données finale

Une base de données finale a été préparée pour rendre la plateforme réaliste et active.

Elle contient notamment :

- 32 utilisateurs ;
- des profils complets, partiels et incomplets ;
- 9 campagnes ;
- 9 offres ;
- 28 candidatures ;
- des négociations avec budgets et délais réalistes ;
- 24 produits ;
- 11 contrats ;
- 20 posts ;
- une cinquantaine de commentaires ;
- 12 réclamations ;
- des réponses administratives ;
- des alertes Cre8Shield ;
- 6 événements ;
- 6 forums ;
- des messages de forum ;
- des notifications ;
- des demandes administratives ;
- des logs administratifs ;
- des traces de supervision serveur.

Les mots de passe sont stockés sous forme hashée. Les identifiants de présentation ne doivent pas être publiés dans ce fichier.

---

## Données médias

Les médias utilisés par la base finale doivent être placés dans les dossiers suivants :

```text
Vue/public/produits/
Vue/public/uploads/
Vue/public/uploads/evenements/
Vue/public/uploads/profile/
```

Les images de profils sont enregistrées dans la base sous forme de nom de fichier. Les produits utilisent aussi un nom de fichier. Les posts et certains événements utilisent des chemins relatifs selon la structure existante du projet.

---

## Validation et stress tests

Les fonctionnalités avancées de Cre8Pilot et Cre8Shield ont été validées par plusieurs séries de tests.

### Omega Validation Test

Objectif : vérifier les scénarios critiques, les actions sûres, les réponses JSON, les risques de sécurité et la préservation des nombres dans les négociations.

Résultat atteint :

- 25 / 25 scénarios passés ;
- 0 action finale interdite ;
- 0 erreur JSON ;
- 0 erreur endpoint ;
- alertes de sécurité détectées.

### Novel Final Matrix Stress Test

Objectif : vérifier les prompts réalistes, les documents, la recommandation de créateurs, les contraintes de format, le multilingue et la résistance aux injections.

Résultat atteint :

- 61 / 61 scénarios passés ;
- modèle de recommandation utilisé ;
- pas d’action finale automatique.

### Chaos Final Matrix Stress Test

Objectif : tester les requêtes ambiguës, contradictoires, risquées, mal écrites ou culturellement sensibles.

Résultat atteint :

- 75 / 75 scénarios passés ;
- pas d’action interdite ;
- pas d’erreur JSON ;
- comportement sûr et stable.

---

## Sécurité

La sécurité est intégrée dans plusieurs parties du projet :

- authentification par rôle ;
- contrôle d’accès entre FrontOffice et BackOffice ;
- récupération de mot de passe par email ;
- reconnaissance faciale ;
- suspension des comptes suspects ;
- restauration de comptes après contrôle ;
- détection des comportements frauduleux ;
- alertes de sécurité dans le BackOffice ;
- protection contre les liens suspects, phishing, XSS et injections SQL ;
- limitation des actions automatiques de l’IA ;
- validation manuelle obligatoire pour les actions sensibles ;
- blocage des fichiers sensibles côté serveur ;
- stockage des secrets dans `.env`.

### Fichiers à ne jamais publier

Ne pas envoyer sur GitHub :

```text
.env
vendor/
storage/uploads/
Vue/public/uploads/
backups/
*.sql contenant des données sensibles
ngrok.yml
```

Un fichier `.env.example` peut être utilisé pour montrer la structure sans exposer les vraies clés.

---

## Déploiement Raspberry Pi

Le projet peut être hébergé sur un Raspberry Pi 5 avec :

- Apache ;
- PHP ;
- MariaDB ;
- Composer ;
- Git ;
- ngrok pour l’accès HTTPS lorsque le réseau est derrière CGNAT.

### Points importants

- Les secrets doivent rester dans `.env`.
- Les dossiers d’uploads doivent être accessibles en écriture par Apache.
- Les fichiers `.env`, `.git`, `storage` et les dossiers sensibles doivent être bloqués depuis le navigateur.
- Une sauvegarde SQL doit être faite avant chaque mise à jour importante.
- Le tunnel ngrok permet de contourner le blocage CGNAT lorsque le port forwarding classique ne fonctionne pas.

---

## Expérience utilisateur

Le projet intègre plusieurs améliorations d’interface :

- mode sombre et mode clair ;
- dashboards statistiques ;
- cartes KPI ;
- graphiques dynamiques ;
- tableaux avec recherche, tri et pagination ;
- filtres avancés ;
- modals de création, modification et détail ;
- notifications toast ;
- interfaces FrontOffice et BackOffice modernisées ;
- chatbot draggable ;
- support des médias, images et interactions communautaires ;
- fenêtres d’action avec brouillons IA ;
- assistant visuel avec avatar et états.

---

## Technologies utilisées

- **PHP**
- **MySQL / MariaDB**
- **HTML5**
- **CSS3**
- **JavaScript**
- **Bootstrap**
- **Chart.js**
- **AJAX / Fetch API**
- **XAMPP**
- **Composer**
- **PHPMailer / SMTP**
- **APIs d’intelligence artificielle**
- **Reconnaissance faciale**
- **Speech-to-Text**
- **Raspberry Pi 5**
- **Apache**
- **ngrok**

---

## Installation locale

### 1. Cloner le repository

```bash
git clone https://github.com/Fel-med/Esprit-PW-2A22-2526-Devcore.git
```

### 2. Placer le projet dans XAMPP

Copier le dossier du projet dans :

```text
C:/xampp/htdocs/php/
```

Exemple :

```text
C:/xampp/htdocs/php/cre8connect/
```

### 3. Installer les dépendances PHP

Si le projet utilise Composer :

```bash
composer install
```

### 4. Lancer XAMPP

Démarrer :

- Apache ;
- MySQL.

### 5. Importer la base de données

Ouvrir phpMyAdmin :

```text
http://localhost/phpmyadmin
```

Créer ou sélectionner la base de données du projet, puis importer le fichier SQL fourni avec le projet.

Pour restaurer complètement une base finale exportée, il est recommandé d’importer le fichier dans une base vide.

### 6. Configurer la connexion à la base

Vérifier le fichier de configuration du projet, par exemple :

```text
config.php
```

ou le fichier :

```text
.env
```

Adapter les paramètres selon l’environnement local :

```env
DB_HOST=localhost
DB_NAME=cre8connect
DB_USER=root
DB_PASS=
```

### 7. Accéder au projet

Ouvrir le projet dans le navigateur :

```text
http://localhost/php/cre8connect/
```

---

## Organisation générale des dossiers

```text
Cre8Connect/
│
├── Controleur/
│   ├── utilisateurC.php
│   ├── reclamationC.php
│   ├── postC.php
│   ├── commentaireC.php
│   ├── campagneC.php
│   ├── produitC.php
│   ├── contratC.php
│   ├── evenementC.php
│   ├── forumC.php
│   ├── offreC.php
│   └── condidatureC.php
│
├── Modele/
│   ├── utilisateur.php
│   ├── reclamation.php
│   ├── post.php
│   ├── commentaire.php
│   ├── campagne.php
│   ├── produit.php
│   ├── contrat.php
│   ├── evenement.php
│   ├── forum.php
│   ├── offre.php
│   └── condidature.php
│
├── Vue/
│   ├── FrontOffice/
│   ├── BackOffice/
│   └── public/
│       ├── produits/
│       └── uploads/
│           ├── evenements/
│           └── profile/
│
├── storage/
├── vendor/
├── assets/
├── config.php
├── .env.example
└── README.md
```

---

## ODD visés

Le projet prend en compte plusieurs Objectifs de Développement Durable :

- **ODD 8** : Travail décent et croissance économique.
- **ODD 9** : Industrie, innovation et infrastructure.
- **ODD 12** : Consommation et production responsables.

Cre8Connect encourage l’économie digitale, facilite l’entrepreneuriat, améliore les opportunités de collaboration et propose une utilisation responsable des outils numériques.

---

## Membres du groupe

- **Mhamdi Neila** : Module Utilisateur / Réclamation
- **Mansouri Amal** : Module Post / Commentaire / Réaction
- **Ghadhab Nour** : Module Campagne / Produit / Contrat
- **Mouaddeb Rabeb** : Module Événement / Forum
- **Felhi Mohamed** : Module Offre / Candidature, Cre8Pilot, Cre8Shield et supervision avancée

---

## Repository

Lien du repository GitHub :

**[Esprit-PW-2A22-2526-Devcore](https://github.com/Fel-med/Esprit-PW-2A22-2526-Devcore)**

---

## Conclusion

Cre8Connect est une plateforme complète qui combine collaboration professionnelle, contenu communautaire, gestion administrative, événements, forums, campagnes, contrats, offres et candidatures.

Grâce à l’intégration de l’intelligence artificielle, des tableaux de bord statistiques, de la sécurité avancée, de Cre8Pilot, de Cre8Shield, de la supervision Hyper Admin et d’une architecture MVC claire, le projet offre une solution moderne pour connecter les marques et les créateurs de contenu dans un environnement digital sécurisé, interactif et évolutif.
