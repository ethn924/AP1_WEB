-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 15 oct. 2025 à 14:59
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ap1_ethan_2025`
--

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

DROP TABLE IF EXISTS `commentaires`;
CREATE TABLE IF NOT EXISTS `commentaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `professeur_id` int NOT NULL,
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cr_id` (`cr_id`),
  KEY `professeur_id` (`professeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cr`
--

DROP TABLE IF EXISTS `cr`;
CREATE TABLE IF NOT EXISTS `cr` (
  `num` bigint NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contenu_html` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `vu` tinyint(1) DEFAULT '0',
  `datetime` datetime DEFAULT NULL,
  `num_utilisateur` int DEFAULT NULL,
  PRIMARY KEY (`num`),
  KEY `num_utilisateur` (`num_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cr`
--

INSERT INTO `cr` (`num`, `date`, `description`, `vu`, `datetime`, `num_utilisateur`) VALUES
(4, '2025-10-15', 'Premier compte rendu de Ethan', 0, '2025-10-15 15:49:12', 4),
(5, '2025-10-15', 'Premier compte rendu de Ethan : début de création du site !', 0, '2025-10-15 16:23:06', 1),
(6, '2025-10-15', 'Rayane le BG', 0, '2025-10-15 16:28:05', 1);

-- --------------------------------------------------------

--
-- Structure de la table `logs_erreurs`
--

DROP TABLE IF EXISTS `logs_erreurs`;
CREATE TABLE IF NOT EXISTS `logs_erreurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int DEFAULT NULL,
  `page` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `erreur` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trace` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lien` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lue` tinyint(1) DEFAULT '0',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pieces_jointes`
--

DROP TABLE IF EXISTS `pieces_jointes`;
CREATE TABLE IF NOT EXISTS `pieces_jointes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `nom_fichier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_mime` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taille` int DEFAULT NULL,
  `donnees` longblob,
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cr_id` (`cr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stage`
--

DROP TABLE IF EXISTS `stage`;
CREATE TABLE IF NOT EXISTS `stage` (
  `num` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CP` int DEFAULT NULL,
  `ville` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libelleStage` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_tuteur` int DEFAULT NULL,
  PRIMARY KEY (`num`),
  KEY `num_tuteur` (`num_tuteur`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stage`
--

INSERT INTO `stage` (`num`, `nom`, `adresse`, `CP`, `ville`, `tel`, `libelleStage`, `email`, `num_tuteur`) VALUES
(1, 'First Car', '15 Rue Carnot', 92400, 'Courbevoie', '01 23 45 67 89', 'BTS SIO SLAM : faire un site internet', 'firstcar92@gmail.com', 1);

-- --------------------------------------------------------

--
-- Structure de la table `tuteur`
--

DROP TABLE IF EXISTS `tuteur`;
CREATE TABLE IF NOT EXISTS `tuteur` (
  `num` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tuteur`
--

INSERT INTO `tuteur` (`num`, `nom`, `prenom`, `tel`, `email`) VALUES
(1, 'Morin', 'Nicolas', '01 23 45 67 89', 'nicolasmorin@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `num` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `motdepasse` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option` int DEFAULT NULL,
  `num_stage` int DEFAULT NULL,
  `token` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date DEFAULT NULL,
  `token_created_at` datetime DEFAULT NULL,
  `email_valide` tinyint(1) DEFAULT '0',
  `code_verification` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verification_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verification_token_created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`num`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`),
  KEY `num_stage` (`num_stage`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`num`, `nom`, `prenom`, `tel`, `login`, `motdepasse`, `type`, `email`, `option`, `num_stage`, `token`, `date`, `token_created_at`, `email_valide`, `code_verification`, `email_verification_token`, `email_verification_token_created_at`) VALUES
(1, 'Lalienne', 'Ethan', NULL, 'ethan.lalienne', 'ad7eb3718391f5f61012e6f890019e1d', 0, 'ethanlalienne92@gmail.com', NULL, 1, '', NULL, NULL, 1, NULL, NULL, NULL),
(2, 'Gravouil', 'Benjamin', NULL, 'benjamin.gravouil', 'e83f1ddc6bb6a17d0c275bdfe90d3a71', 1, 'benjamin.gravouil@gmail.com', NULL, NULL, '', NULL, NULL, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `modeles_cr`
--

DROP TABLE IF EXISTS `modeles_cr`;
CREATE TABLE IF NOT EXISTS `modeles_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contenu_html` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `professeur_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `professeur_id` (`professeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `modeles_cr`
--

INSERT INTO `modeles_cr` (`titre`, `description`, `contenu_html`, `professeur_id`) VALUES
('Modèle de compte rendu standard', 'Modèle de base pour les comptes rendus hebdomadaires', '<h2>Activités réalisées</h2><p>Décrivez ici les activités que vous avez réalisées cette semaine.</p><h2>Compétences développées</h2><p>Listez les compétences que vous avez développées ou mises en pratique.</p><h2>Difficultés rencontrées</h2><p>Mentionnez les difficultés que vous avez rencontrées et comment vous les avez surmontées.</p><h2>Objectifs pour la semaine prochaine</h2><p>Définissez vos objectifs pour la semaine à venir.</p>', 2);

--
-- Script pour appliquer les modifications à la base de données
-- Ce script ajoute la colonne contenu_html à la table cr si elle n'existe pas déjà
-- et crée la table modeles_cr si elle n'existe pas déjà
--

-- Vérifier si la colonne contenu_html existe déjà dans la table cr
SET @exists = 0;
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'ap1_ethan_2025' AND TABLE_NAME = 'cr' AND COLUMN_NAME = 'contenu_html';

-- Ajouter la colonne contenu_html si elle n'existe pas
SET @query = IF(@exists = 0, 
    'ALTER TABLE cr ADD COLUMN contenu_html LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AFTER description',
    'SELECT "La colonne contenu_html existe déjà dans la table cr"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier si la table modeles_cr existe déjà
SET @tableExists = 0;
SELECT COUNT(*) INTO @tableExists FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'ap1_ethan_2025' AND TABLE_NAME = 'modeles_cr';

-- Créer la table modeles_cr si elle n'existe pas
SET @createTable = IF(@tableExists = 0,
    'CREATE TABLE modeles_cr (
      id int NOT NULL AUTO_INCREMENT,
      titre varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      description text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
      contenu_html LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
      professeur_id int NOT NULL,
      date_creation datetime DEFAULT CURRENT_TIMESTAMP,
      actif tinyint(1) DEFAULT 1,
      PRIMARY KEY (id),
      KEY professeur_id (professeur_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT "La table modeles_cr existe déjà"');
PREPARE stmt FROM @createTable;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier si la contrainte de clé étrangère existe déjà
SET @constraintExists = 0;
SELECT COUNT(*) INTO @constraintExists FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'ap1_ethan_2025' AND TABLE_NAME = 'modeles_cr' AND CONSTRAINT_NAME = 'modeles_cr_ibfk_1';

-- Ajouter la contrainte de clé étrangère si elle n'existe pas et si la table existe
SET @addConstraint = IF(@tableExists = 1 AND @constraintExists = 0,
    'ALTER TABLE modeles_cr ADD CONSTRAINT modeles_cr_ibfk_1 FOREIGN KEY (professeur_id) REFERENCES utilisateur (num) ON DELETE CASCADE',
    'SELECT "La contrainte existe déjà ou la table n\'existe pas"');
PREPARE stmt FROM @addConstraint;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier si le modèle par défaut existe déjà
SET @modelExists = 0;
SELECT COUNT(*) INTO @modelExists FROM modeles_cr WHERE titre = 'Modèle de compte rendu standard';

-- Insérer un modèle par défaut si aucun n'existe
SET @insertModel = IF(@modelExists = 0,
    'INSERT INTO modeles_cr (titre, description, contenu_html, professeur_id) VALUES 
    ("Modèle de compte rendu standard", "Modèle de base pour les comptes rendus hebdomadaires", 
    "<h2>Activités réalisées</h2><p>Décrivez ici les activités que vous avez réalisées cette semaine.</p><h2>Compétences développées</h2><p>Listez les compétences que vous avez développées ou mises en pratique.</p><h2>Difficultés rencontrées</h2><p>Mentionnez les difficultés que vous avez rencontrées et comment vous les avez surmontées.</p><h2>Objectifs pour la semaine prochaine</h2><p>Définissez vos objectifs pour la semaine à venir.</p>", 
    2)',
    'SELECT "Un modèle par défaut existe déjà"');
PREPARE stmt FROM @insertModel;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateur` (`num`);

--
-- Contraintes pour la table `logs_erreurs`
--
ALTER TABLE `logs_erreurs`
  ADD CONSTRAINT `logs_erreurs_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD CONSTRAINT `pieces_jointes_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modeles_cr`
--
ALTER TABLE `modeles_cr`
  ADD CONSTRAINT `modeles_cr_ibfk_1` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;