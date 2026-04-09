CREATE DATABASE IF NOT EXISTS cre8connect
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cre8connect;

-- =========================================================
-- 1) UTILISATEUR / RECLAMATION
-- =========================================================

CREATE TABLE IF NOT EXISTS utilisateur (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('createur', 'marque', 'admin') NOT NULL,
    statut ENUM('actif', 'en_attente', 'suspendu', 'bloque') NOT NULL DEFAULT 'en_attente',
    tentatives_login INT UNSIGNED NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_utilisateur_role (role),
    KEY idx_utilisateur_statut (statut)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reclamation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUtilisateur INT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'en_cours', 'resolue', 'rejetee') NOT NULL DEFAULT 'en_attente',
    priorite ENUM('basse', 'moyenne', 'haute', 'urgente') NOT NULL DEFAULT 'moyenne',
    reponse_admin TEXT NULL,
    CONSTRAINT fk_reclamation_utilisateur
        FOREIGN KEY (idUtilisateur) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_reclamation_utilisateur (idUtilisateur),
    KEY idx_reclamation_statut (statut),
    KEY idx_reclamation_priorite (priorite)
) ENGINE=InnoDB;

-- =========================================================
-- 2) POST / COMMENT
-- =========================================================

CREATE TABLE IF NOT EXISTS `post` (
    id CHAR(36) PRIMARY KEY,
    idCreateur INT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    creationDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    textContent TEXT NOT NULL,
    imageContent VARCHAR(255) NULL,
    VideoContent VARCHAR(255) NULL,
    numberOfView INT UNSIGNED NOT NULL DEFAULT 0,
    numberOfLike INT UNSIGNED NOT NULL DEFAULT 0,
    numberOfDislike INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_post_utilisateur
        FOREIGN KEY (idCreateur) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_post_createur (idCreateur)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `comment` (
    id CHAR(36) PRIMARY KEY,
    idCommentedElement CHAR(36) NOT NULL,
    idUser INT UNSIGNED NOT NULL,
    commentedItem ENUM('post', 'comment') NOT NULL,
    `text` TEXT NOT NULL,
    Sticker VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    numberOfLike INT UNSIGNED NOT NULL DEFAULT 0,
    numberOfDislike INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_comment_user
        FOREIGN KEY (idUser) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_comment_user (idUser),
    KEY idx_comment_target (commentedItem, idCommentedElement)
) ENGINE=InnoDB;

-- =========================================================
-- 3) OFFRE / CANDIDATURE
-- =========================================================

CREATE TABLE IF NOT EXISTS offre (
    idOffre INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idMarque INT UNSIGNED NOT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    objectif TEXT NOT NULL,
    budgetMin DECIMAL(10,2) NOT NULL,
    budgetMax DECIMAL(10,2) NOT NULL,
    datePublication DATE NOT NULL,
    dateLimite DATE NOT NULL,
    statutOffre ENUM('brouillon', 'publiee', 'cloturee', 'expiree', 'archivee') NOT NULL DEFAULT 'brouillon',
    CONSTRAINT fk_offre_marque
        FOREIGN KEY (idMarque) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_offre_marque (idMarque),
    KEY idx_offre_statut (statutOffre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS candidature (
    idCandidature INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idOffre INT UNSIGNED NOT NULL,
    idCreateur INT UNSIGNED NOT NULL,
    dateCandidature DATE NOT NULL,
    statutCandidature ENUM('en_attente', 'en_etude', 'acceptee', 'refusee', 'retiree') NOT NULL DEFAULT 'en_attente',
    messageMotivation TEXT NOT NULL,
    budgetPropose DECIMAL(10,2) NOT NULL,
    delaiPropose INT UNSIGNED NOT NULL,
    noteDecision TEXT NULL,
    CONSTRAINT fk_candidature_offre
        FOREIGN KEY (idOffre) REFERENCES offre(idOffre)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_candidature_createur
        FOREIGN KEY (idCreateur) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_candidature_offre_createur (idOffre, idCreateur),
    KEY idx_candidature_createur (idCreateur),
    KEY idx_candidature_statut (statutCandidature)
) ENGINE=InnoDB;

-- =========================================================
-- 4) CAMPAGNE / CONTRAT / PRODUIT
-- =========================================================

CREATE TABLE IF NOT EXISTS campagne (
    idCampagne INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idMarque INT UNSIGNED NOT NULL,
    idCandidature INT UNSIGNED NOT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    dateDebut DATE NOT NULL,
    dateFin DATE NOT NULL,
    statut ENUM('planifiee', 'en_cours', 'terminee', 'suspendue', 'annulee') NOT NULL DEFAULT 'planifiee',
    CONSTRAINT fk_campagne_marque
        FOREIGN KEY (idMarque) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_campagne_candidature
        FOREIGN KEY (idCandidature) REFERENCES candidature(idCandidature)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_campagne_candidature (idCandidature),
    KEY idx_campagne_marque (idMarque),
    KEY idx_campagne_statut (statut)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contrat (
    idContrat INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idCampagne INT UNSIGNED NOT NULL,
    dateGeneration DATE NOT NULL,
    dateSignature DATE NULL,
    statut ENUM('genere', 'en_attente_signature', 'signe', 'annule', 'resilie') NOT NULL DEFAULT 'genere',
    conditions TEXT NOT NULL,
    CONSTRAINT fk_contrat_campagne
        FOREIGN KEY (idCampagne) REFERENCES campagne(idCampagne)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_contrat_campagne (idCampagne),
    KEY idx_contrat_statut (statut)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produit (
    idProduit INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idMarque INT UNSIGNED NOT NULL,
    nomProduit VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    caracteristiques TEXT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_produit_marque
        FOREIGN KEY (idMarque) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_produit_marque (idMarque)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campagne_produit (
    idCampagne INT UNSIGNED NOT NULL,
    idProduit INT UNSIGNED NOT NULL,
    PRIMARY KEY (idCampagne, idProduit),
    CONSTRAINT fk_campagne_produit_campagne
        FOREIGN KEY (idCampagne) REFERENCES campagne(idCampagne)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_campagne_produit_produit
        FOREIGN KEY (idProduit) REFERENCES produit(idProduit)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_campagne_produit_produit (idProduit)
) ENGINE=InnoDB;

-- =========================================================
-- 5) EVENEMENT / FORUM
-- =========================================================

CREATE TABLE IF NOT EXISTS evenement (
    idFormation INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    TitreFormation VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    Duree INT UNSIGNED NOT NULL,
    DateFormation DATE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS forum (
    idForum INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idFormation INT UNSIGNED NOT NULL,
    idUtilisateur INT UNSIGNED NOT NULL,
    TitreForum VARCHAR(150) NOT NULL,
    dateCreation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    CONSTRAINT fk_forum_evenement
        FOREIGN KEY (idFormation) REFERENCES evenement(idFormation)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_forum_utilisateur
        FOREIGN KEY (idUtilisateur) REFERENCES utilisateur(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_forum_formation (idFormation),
    KEY idx_forum_utilisateur (idUtilisateur)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS evenement_produit (
    idFormation INT UNSIGNED NOT NULL,
    idProduit INT UNSIGNED NOT NULL,
    PRIMARY KEY (idFormation, idProduit),
    CONSTRAINT fk_evenement_produit_evenement
        FOREIGN KEY (idFormation) REFERENCES evenement(idFormation)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_evenement_produit_produit
        FOREIGN KEY (idProduit) REFERENCES produit(idProduit)
        ON DELETE CASCADE ON UPDATE CASCADE,
    KEY idx_evenement_produit_produit (idProduit)
) ENGINE=InnoDB;