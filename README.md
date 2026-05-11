# Cre8Connect

## Présentation du projet

**Cre8Connect** est une plateforme web de collaboration entre **marques** et **créateurs de contenu**.

La plateforme facilite la mise en relation professionnelle entre les marques qui souhaitent promouvoir leurs produits, services ou campagnes, et les créateurs capables de produire du contenu adapté à leurs besoins.

Cre8Connect regroupe plusieurs espaces fonctionnels : gestion des utilisateurs, publications, commentaires, campagnes, produits, contrats, événements, forums, offres, candidatures, réclamations, statistiques, assistance intelligente et sécurité.

Le projet s’inscrit dans la thématique :

**Économie digitale, entrepreneuriat et futur du travail**

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
- utiliser les assistants IA pour générer du contenu ou améliorer ses candidatures.

### Marque

La marque peut :
- créer et gérer des offres ;
- consulter les candidatures reçues ;
- accepter, refuser ou négocier avec les créateurs ;
- créer des campagnes ;
- gérer des produits ;
- générer et signer des contrats ;
- suivre les performances des collaborations ;
- utiliser l’IA pour proposer des campagnes, des slogans, des contrats et des recommandations.

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

---

## Architecture du projet

Le projet suit une architecture **MVC** :

- **Modèle** : contient les classes métier et les entités manipulées par la plateforme.
- **Vue** : contient les pages FrontOffice et BackOffice affichées aux utilisateurs.
- **Contrôleur** : assure le lien entre les vues, les modèles et la base de données.

Le projet est organisé en deux grands espaces :

### FrontOffice

Espace destiné aux créateurs et aux marques. Il permet l’utilisation normale de la plateforme : consultation des offres, candidatures, campagnes, posts, commentaires, événements, forums, etc.

### BackOffice

Espace destiné aux administrateurs. Il permet la supervision, la gestion, les statistiques, le traitement des réclamations, la modération et la sécurité.

---

## Modules du projet

## 1. Module Utilisateur / Réclamation

Ce module assure la gestion des comptes, des rôles, de l’authentification et des réclamations.

### Fonctionnalités principales

- Gestion des comptes utilisateurs.
- Authentification selon le rôle : créateur, marque ou administrateur.
- Gestion des réclamations.
- Traitement administratif des demandes.
- Tableau de bord statistique pour les utilisateurs et les réclamations.
- Recherche, filtrage et tri des utilisateurs ou réclamations.
- Détection et suspension des comptes suspects.
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
- Dictée vocale multilingue : arabe, français et anglais.
- Génération de posts par intelligence artificielle.
- Analyse et description automatique des images uploadées.

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

### Fonctionnalités IA

- Suggestion de campagnes adaptées aux créateurs.
- Recommandation de marques compatibles.
- Recommandation de produits pertinents.
- Score de compatibilité entre créateur, marque, produit et campagne.
- Prédiction de performance.
- Génération automatique de contrats.
- Génération de clauses, conditions, tarifs, livrables, durée et pénalités.
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

### Cre8Pilot — Assistant intelligent

Cre8Pilot est l’assistant IA intégré au module Offre / Candidature.

Il peut :
- préparer une offre ;
- améliorer une description ;
- recommander des créateurs ;
- préparer une candidature ;
- résumer une offre ;
- comparer les offres visibles ;
- préparer une réponse de négociation ;
- préparer une note d’acceptation ;
- préparer une note de refus ;
- utiliser un CV ou portfolio uploadé par l’utilisateur.

### Actions IA sécurisées

Cre8Pilot peut aider l’utilisateur à remplir ou préparer des actions, mais il ne peut pas exécuter seul une action finale.

Actions autorisées :
- remplir un formulaire ;
- ouvrir une fenêtre de négociation ;
- appliquer un filtre ;
- faire une recherche ;
- trier les résultats ;
- mettre le focus sur un champ ;
- surligner les champs modifiés.

Actions interdites :
- publier une offre ;
- supprimer une offre ;
- envoyer une candidature ;
- accepter une candidature ;
- refuser une candidature ;
- envoyer une réponse finale ;
- supprimer des données.

### Cre8Shield — Sécurité du module

Cre8Shield analyse les messages, liens et comportements suspects.

Risques détectés :
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
- usurpation d’identité.

Les risques moyens ou élevés peuvent être envoyés au BackOffice pour traitement par l’administrateur.

### Valeur ajoutée

Ce module ne se limite pas à afficher des offres. Il accompagne les marques et les créateurs pendant tout le processus de collaboration : création d’offre, candidature, analyse, négociation, décision finale et sécurité.

---

## Intelligence artificielle dans Cre8Connect

Cre8Connect intègre plusieurs fonctionnalités basées sur l’intelligence artificielle :

- génération de posts ;
- description automatique d’images ;
- chatbot intelligent ;
- dictée vocale multilingue ;
- suggestion de campagnes ;
- recommandation de créateurs ;
- génération de contrats ;
- analyse des campagnes ;
- assistance à la rédaction d’offres ;
- assistance à la rédaction de candidatures ;
- préparation de réponses de négociation ;
- détection de risques avec Cre8Shield.

L’objectif de l’IA dans Cre8Connect est d’aider l’utilisateur, améliorer la productivité, réduire le temps de recherche, renforcer la sécurité et améliorer la qualité des collaborations.

---

## Sécurité

La sécurité est intégrée dans plusieurs parties du projet :

- authentification par rôle ;
- contrôle d’accès entre FrontOffice et BackOffice ;
- récupération de mot de passe par email ;
- reconnaissance faciale ;
- suspension des comptes suspects ;
- détection des comportements frauduleux ;
- alertes de sécurité dans le BackOffice ;
- protection contre les liens suspects, phishing, XSS et injections SQL ;
- limitation des actions automatiques de l’IA ;
- validation manuelle obligatoire pour les actions sensibles.

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
- support des médias, images et interactions communautaires.

---

## Technologies utilisées

- **PHP**
- **MySQL**
- **HTML5**
- **CSS3**
- **JavaScript**
- **Bootstrap**
- **Chart.js**
- **AJAX / Fetch API**
- **XAMPP**
- **APIs d’intelligence artificielle**
- **Reconnaissance faciale**
- **Speech-to-Text**
- **Email SMTP / PHPMailer**

---

## Installation locale

### 1. Cloner le repository

```bash
git clone https://github.com/Fel-med/Esprit-PW-2A22-2526-Devcore.git
```

### 2. Placer le projet dans XAMPP

Copier le dossier du projet dans :

```bash
C:/xampp/htdocs/php/
```

Exemple :

```bash
C:/xampp/htdocs/php/cre8connect/
```

### 3. Lancer XAMPP

Démarrer :

- Apache
- MySQL

### 4. Importer la base de données

Ouvrir phpMyAdmin :

```text
http://localhost/phpmyadmin
```

Créer ou sélectionner la base de données du projet, puis importer le fichier `.sql` fourni avec le projet.

### 5. Configurer la connexion à la base

Vérifier le fichier de configuration du projet, par exemple :

```text
config.php
```

Adapter les paramètres selon l’environnement local :

```php
host = localhost
database = nom_de_la_base
user = root
password = ""
```

### 6. Accéder au projet

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
│   └── BackOffice/
│
├── assets/
├── config.php
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
- **Felhi Mohamed** : Module Offre / Candidature

---

## Repository

Lien du repository GitHub :

**[Esprit-PW-2A22-2526-Devcore](https://github.com/Fel-med/Esprit-PW-2A22-2526-Devcore)**

---

## Conclusion

Cre8Connect est une plateforme complète qui combine collaboration professionnelle, contenu communautaire, gestion administrative, événements, forums, campagnes, contrats, offres et candidatures.

Grâce à l’intégration de l’intelligence artificielle, des tableaux de bord statistiques, de la sécurité avancée et d’une architecture MVC claire, le projet offre une solution moderne pour connecter les marques et les créateurs de contenu dans un environnement digital sécurisé, interactif et évolutif.
