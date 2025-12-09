-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 09 déc. 2025 à 17:52
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaires`
--

INSERT INTO `commentaires` (`id`, `cr_id`, `professeur_id`, `commentaire`, `date_creation`) VALUES
(1, 12, 2, 'Bon début, n\'oublie pas de détailler davantage la partie analyse', '2025-10-25 14:30:00'),
(2, 12, 2, 'Merci d\'avoir corrigé. La version 2 est beaucoup meilleure.', '2025-10-22 10:00:00'),
(3, 13, 2, 'Excellent travail! Très complet et bien structuré.', '2025-10-26 10:00:00'),
(4, 11, 2, 'À revoir: manque la conclusion finale', '2025-10-22 14:15:00');

-- --------------------------------------------------------

--
-- Structure de la table `cr`
--

DROP TABLE IF EXISTS `cr`;
CREATE TABLE IF NOT EXISTS `cr` (
  `num` bigint NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `titre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenu_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `vu` tinyint(1) DEFAULT '0',
  `archivé` tinyint(1) DEFAULT '0',
  `datetime` datetime DEFAULT NULL,
  `num_version` int DEFAULT '1',
  `num_utilisateur` int DEFAULT NULL,
  `groupe_id` int DEFAULT NULL,
  `supprime` tinyint(1) DEFAULT '0',
  `date_suppression` datetime DEFAULT NULL,
  PRIMARY KEY (`num`),
  KEY `num_utilisateur` (`num_utilisateur`),
  KEY `groupe_id` (`groupe_id`),
  KEY `idx_supprime` (`supprime`),
  KEY `idx_utilisateur_supprime` (`num_utilisateur`,`supprime`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cr`
--

INSERT INTO `cr` (`num`, `date`, `description`, `titre`, `contenu_html`, `vu`, `archivé`, `datetime`, `num_version`, `num_utilisateur`, `groupe_id`, `supprime`, `date_suppression`) VALUES
(10, '2025-10-21', 'Rapport de stage - Semaine 1', NULL, '<p>Première semaine au sein de l\'entreprise FirstCar. Accueil et prise en main des outils...</p>', 0, 0, '2025-10-21 22:21:07', 1, 1, NULL, 0, NULL),
(11, '2025-10-21', 'Rapport de stage - Semaine 2', NULL, '<p>Travail sur le site web. Mise en place du design et architecture...</p>', 0, 0, '2025-10-21 22:33:35', 1, 3, NULL, 0, NULL),
(12, '2025-10-21', 'Analyse technique du projet', NULL, '<p>Analyse détaillée des technologies utilisées et des défis rencontrés...</p>', 0, 0, '2025-10-21 23:03:35', 1, 3, NULL, 0, NULL),
(13, '2025-10-21', 'Rapport final de stage', NULL, '<p>Synthèse complète du stage avec résultats et recommandations...</p>', 0, 0, '2025-10-21 23:16:54', 1, 3, NULL, 0, NULL),
(14, '2025-10-20', 'Rapport initial', NULL, '<p>Début du stage et découverte de l\'environnement...</p>', 0, 0, '2025-10-20 14:30:00', 1, 4, NULL, 0, NULL),
(15, '2025-10-22', 'Progression SISR Week 1', NULL, '<p>Installation et configuration des serveurs...</p>', 0, 0, '2025-10-22 10:15:00', 1, 4, NULL, 0, NULL),
(16, '2025-10-19', 'Rapport de démarrage', NULL, '<p>Premier jour au stage...</p>', 1, 0, '2025-10-19 16:45:00', 1, 6, NULL, 0, NULL),
(17, '2025-10-21', 'Rapport Semaine 1', NULL, '<p>Première semaine productive...</p>', 0, 0, '2025-10-21 11:20:00', 1, 6, NULL, 0, NULL),
(18, '2025-10-22', 'Développement des modules', NULL, '<p>Implémentation des modules principaux...</p>', 0, 0, '2025-10-22 13:45:00', 1, 6, NULL, 0, NULL),
(19, '2025-10-18', 'Intégration système', NULL, '<p>Tâches d\'intégration système...</p>', 0, 0, '2025-10-18 09:30:00', 1, 7, NULL, 0, NULL),
(20, '2025-10-21', 'Administration réseau', NULL, '<p>Configuration des services réseau...</p>', 0, 0, '2025-10-21 15:00:00', 1, 7, NULL, 0, NULL),
(21, '2025-10-17', 'Commencement du projet', NULL, '<p>Présentation du projet et des objectifs...</p>', 1, 0, '2025-10-17 10:00:00', 1, 9, NULL, 0, NULL),
(22, '2025-10-22', 'Avancement du projet', NULL, '<p>Progression significative sur les tâches assignées...</p>', 0, 0, '2025-10-22 14:20:00', 1, 9, NULL, 0, NULL),
(23, '2025-10-20', 'Rapport hebdomadaire', NULL, '<p>Synthèse de la semaine 2...</p>', 0, 0, '2025-10-20 17:30:00', 1, 10, NULL, 0, NULL),
(24, '2025-10-22', 'Résultats et retours', NULL, '<p>Feedback du tuteur et améliorations...</p>', 0, 0, '2025-10-22 16:00:00', 1, 10, NULL, 1, '2025-10-23 09:00:00'),
(25, '2025-12-07', 'Titouan', 'TITOUAN HAS BECOME A GAY ', '<p>Bonjour je m\'appelle Titouan je rentre en France pour voir ma grosse daronne qui s\'appelle Mia Khalifa !</p>', 0, 0, '2025-12-07 11:59:19', 1, 1, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `groupes`
--

DROP TABLE IF EXISTS `groupes`;
CREATE TABLE IF NOT EXISTS `groupes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `professeur_responsable_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `professeur_responsable_id` (`professeur_responsable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `groupes`
--

INSERT INTO `groupes` (`id`, `nom`, `description`, `professeur_responsable_id`, `date_creation`, `actif`) VALUES
(1, 'Groupe A - BTS SIO SLAM', 'Groupe de développement web et applications', 2, '2025-01-10 10:00:00', 1),
(2, 'Groupe B - BTS SIO SISR', 'Groupe d\'administration systèmes et réseaux', 5, '2025-01-10 10:15:00', 1);

-- --------------------------------------------------------

--
-- Structure de la table `membres_groupe`
--

DROP TABLE IF EXISTS `membres_groupe`;
CREATE TABLE IF NOT EXISTS `membres_groupe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupe_utilisateur` (`groupe_id`,`utilisateur_id`),
  KEY `groupe_id` (`groupe_id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `membres_groupe`
--

INSERT INTO `membres_groupe` (`id`, `groupe_id`, `utilisateur_id`, `date_ajout`, `statut`) VALUES
(1, 1, 1, '2025-01-10 10:00:00', 'actif'),
(2, 1, 3, '2025-01-10 10:05:00', 'actif'),
(3, 1, 6, '2025-01-10 10:10:00', 'actif'),
(4, 2, 4, '2025-01-10 10:15:00', 'actif'),
(5, 2, 7, '2025-01-10 10:20:00', 'actif'),
(6, 2, 9, '2025-01-10 10:25:00', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lien` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lue` tinyint(1) DEFAULT '0',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `type`, `titre`, `message`, `lien`, `lue`, `date_creation`) VALUES
(1, 3, 'commentaire', 'Commentaire sur votre CR', 'Bon début, n\'oublie pas de détailler davantage la partie analyse', 'editer_cr.php?id=12', 1, '2025-10-25 14:30:00'),
(2, 3, 'validation', 'CR validé', 'Votre compte rendu n°13 a été approuvé', 'liste_cr.php?id=13', 1, '2025-10-26 11:00:00'),
(4, 4, 'rappel', 'Délai de soumission', 'Vous avez jusqu\'au 21 novembre pour soumettre vos comptes rendus', 'tableau_bord_eleve.php', 0, '2025-11-01 09:00:00'),
(5, 2, 'modification', 'Modification d\'un CR', 'Pierre Martin a modifié le CR n°12', 'liste_cr_prof.php', 0, '2025-10-22 09:15:00'),
(6, 3, 'feedback', 'Feedback reçu', 'Vous avez reçu un nouveau feedback sur le CR n°11', 'editer_cr.php?id=11', 0, '2025-10-22 14:20:00'),
(8, 5, 'groupe', 'Nouveau groupe', 'Vous êtes responsable du groupe \'Groupe B - BTS SIO SISR\'', 'gestion_groupes.php', 0, '2025-01-10 10:15:00');

-- --------------------------------------------------------

--
-- Structure de la table `pieces_jointes`
--

DROP TABLE IF EXISTS `pieces_jointes`;
CREATE TABLE IF NOT EXISTS `pieces_jointes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `nom_fichier` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_mime` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taille` int DEFAULT NULL,
  `donnees` longblob,
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cr_id` (`cr_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pieces_jointes`
--

INSERT INTO `pieces_jointes` (`id`, `cr_id`, `nom_fichier`, `type_mime`, `taille`, `donnees`, `date_upload`) VALUES
(1, 11, 'rapport_analyse.pdf', 'application/pdf', 245120, NULL, '2025-10-21 22:35:00'),
(2, 12, 'schemas_techniques.pdf', 'application/pdf', 523400, NULL, '2025-10-21 23:05:00'),
(3, 12, 'code_source.zip', 'application/zip', 1024000, NULL, '2025-10-22 09:20:00'),
(4, 13, 'documentation_complete.docx', 'application/vnd.openxmlformats-officedocument.word', 325600, NULL, '2025-10-21 23:18:00'),
(5, 13, 'diagrammes_uml.png', 'image/png', 156780, NULL, '2025-10-22 10:35:00'),
(6, 25, 'ceinture.jpg', 'image/jpeg', 24373, 0x363933353565303762646461325f6365696e747572652e6a7067, '2025-12-07 11:59:19');

-- --------------------------------------------------------

--
-- Structure de la table `sauvegardes_auto`
--

DROP TABLE IF EXISTS `sauvegardes_auto`;
CREATE TABLE IF NOT EXISTS `sauvegardes_auto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `utilisateur_id` int NOT NULL,
  `contenu_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_sauvegarde` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cr_id` (`cr_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `date_sauvegarde` (`date_sauvegarde`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sauvegardes_auto`
--

INSERT INTO `sauvegardes_auto` (`id`, `cr_id`, `utilisateur_id`, `contenu_html`, `description`, `date_sauvegarde`) VALUES
(1, 10, 3, '<p>Brouillon en cours de rédaction...</p>', 'ffff', '2025-10-22 14:00:00'),
(2, 11, 3, '<p>ddddddddd - brouillon</p>', 'ddddd', '2025-10-22 15:30:00'),
(3, 12, 3, '<p>dddddd - version 2 en progression</p>', 'fdddd', '2025-10-22 16:45:00'),
(4, 13, 3, '<p>ffffff - brouillon final</p>', 'fff', '2025-10-22 17:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `stage`
--

DROP TABLE IF EXISTS `stage`;
CREATE TABLE IF NOT EXISTS `stage` (
  `num` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CP` int DEFAULT NULL,
  `ville` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tel` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libelleStage` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_tuteur` int DEFAULT NULL,
  PRIMARY KEY (`num`),
  KEY `num_tuteur` (`num_tuteur`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stage`
--

INSERT INTO `stage` (`num`, `nom`, `adresse`, `CP`, `ville`, `tel`, `libelleStage`, `email`, `num_tuteur`) VALUES
(1, 'First Car', '15 Rue Carnot', 92400, 'Courbevoie', '0123456789', 'BTS SIO SLAM : faire un site internet', 'firstcar92@gmail.com', 1),
(2, 'First Car', '15 Rue Carnot', 92400, 'Courbevoie', '0123456789', 'Faire un site internet', 'firstcar92@gmail.com', 2);

-- --------------------------------------------------------

--
-- Structure de la table `statuts_cr`
--

DROP TABLE IF EXISTS `statuts_cr`;
CREATE TABLE IF NOT EXISTS `statuts_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `statut` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `date_soumission` datetime DEFAULT NULL,
  `date_evaluation` datetime DEFAULT NULL,
  `date_approbation` datetime DEFAULT NULL,
  `date_limite_soumission` datetime DEFAULT NULL,
  `professeur_evaluateur_id` int DEFAULT NULL,
  `notes_evaluation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `feedback_general` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_modification_statut` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_id` (`cr_id`),
  KEY `professeur_evaluateur_id` (`professeur_evaluateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `statuts_cr`
--

INSERT INTO `statuts_cr` (`id`, `cr_id`, `statut`, `date_soumission`, `date_evaluation`, `date_approbation`, `date_limite_soumission`, `professeur_evaluateur_id`, `notes_evaluation`, `feedback_general`, `date_modification_statut`) VALUES
(1, 10, 'brouillon', NULL, NULL, NULL, '2025-11-21 00:00:00', NULL, NULL, NULL, '2025-12-06 10:42:37'),
(2, 11, 'soumis', '2025-10-21 22:33:35', NULL, NULL, '2025-11-21 00:00:00', 2, NULL, NULL, '2025-12-06 10:42:37'),
(3, 12, 'en_evaluation', '2025-10-21 23:03:35', '2025-10-25 14:30:00', NULL, '2025-11-21 00:00:00', 2, 'Bon travail, quelques points à revoir', 'À approfondir la partie technique', '2025-12-06 10:42:37'),
(4, 13, 'approuve', '2025-10-21 23:16:54', '2025-10-26 10:00:00', '2025-10-26 11:00:00', '2025-11-21 00:00:00', 2, 'Excellent rapport', 'Travail complété avec succès', '2025-12-06 10:42:37');

-- --------------------------------------------------------

--
-- Structure de la table `tuteur`
--

DROP TABLE IF EXISTS `tuteur`;
CREATE TABLE IF NOT EXISTS `tuteur` (
  `num` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tel` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`num`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tuteur`
--

INSERT INTO `tuteur` (`num`, `nom`, `prenom`, `tel`, `email`) VALUES
(1, 'Morin', 'Nicolas', '0123456789', 'nicolasmorin@gmail.com'),
(2, 'Morin', 'Nicolas', '0123456789', 'nicolas@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `num` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tel` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mdp` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int DEFAULT NULL,
  `email` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `option` int DEFAULT NULL,
  `num_stage` int DEFAULT NULL,
  `token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `token_created_at` datetime DEFAULT NULL,
  `code_verification` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verification_token` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verification_token_created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`num`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`num`, `nom`, `prenom`, `tel`, `login`, `mdp`, `type`, `email`, `verified`, `option`, `num_stage`, `token`, `date`, `token_created_at`, `code_verification`, `email_verification_token`, `email_verification_token_created_at`) VALUES
(1, 'Lalienne', 'Ethan', '0123456789', 'ethan.lalienne', '482c811da5d5b4bc6d497ffa98491e38', 0, 'ethanlalienne92@gmail.com', 1, 1, 1, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(2, 'Dubois', 'Marie', '0134567890', 'marie.dubois', '482c811da5d5b4bc6d497ffa98491e38', 1, 'marie.dubois@edu.esiee.fr', 1, NULL, NULL, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(3, 'Martin', 'Pierre', '0145678901', 'pierre.martin', '482c811da5d5b4bc6d497ffa98491e38', 0, 'pierre.martin@edu.esiee.fr', 1, 1, 1, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(4, 'Bernard', 'Sophie', '0156789012', 'sophie.bernard', '482c811da5d5b4bc6d497ffa98491e38', 0, 'sophie.bernard@edu.esiee.fr', 1, 2, 2, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(5, 'Richard', 'Thomas', '0167890123', 'thomas.richard', '482c811da5d5b4bc6d497ffa98491e38', 1, 'thomas.richard@edu.esiee.fr', 1, NULL, NULL, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(6, 'Petit', 'Laura', '0178901234', 'laura.petit', '482c811da5d5b4bc6d497ffa98491e38', 0, 'laura.petit@edu.esiee.fr', 1, 1, 1, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(7, 'Robert', 'Kevin', '0189012345', 'kevin.robert', '482c811da5d5b4bc6d497ffa98491e38', 0, 'kevin.robert@edu.esiee.fr', 1, 2, 2, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(8, 'Durand', 'Alice', '0190123456', 'alice.durand', '482c811da5d5b4bc6d497ffa98491e38', 1, 'alice.durand@edu.esiee.fr', 1, NULL, NULL, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(9, 'Moreau', 'Julien', '0112345678', 'julien.moreau', '482c811da5d5b4bc6d497ffa98491e38', 0, 'julien.moreau@edu.esiee.fr', 1, 1, 1, NULL, '2025-01-15', NULL, NULL, NULL, NULL),
(10, 'Simon', 'Chloé', '0123456789', 'chloe.simon', '482c811da5d5b4bc6d497ffa98491e38', 0, 'chloe.simon@edu.esiee.fr', 1, 2, 2, NULL, '2025-01-15', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `validations_cr`
--

DROP TABLE IF EXISTS `validations_cr`;
CREATE TABLE IF NOT EXISTS `validations_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `professeur_id` int NOT NULL,
  `valide` tinyint(1) DEFAULT '0',
  `commentaire_validation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_validation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_id` (`cr_id`),
  KEY `professeur_id` (`professeur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `validations_cr`
--

INSERT INTO `validations_cr` (`id`, `cr_id`, `professeur_id`, `valide`, `commentaire_validation`, `date_validation`) VALUES
(1, 10, 2, 0, NULL, '2025-10-21 22:25:00'),
(2, 11, 2, 0, 'En attente de finalisation', '2025-10-21 22:35:00'),
(3, 12, 2, 1, 'Approuvé après corrections', '2025-10-22 09:30:00'),
(4, 13, 2, 1, 'Excellent', '2025-10-26 11:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `versions_cr`
--

DROP TABLE IF EXISTS `versions_cr`;
CREATE TABLE IF NOT EXISTS `versions_cr` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `numero_version` int NOT NULL,
  `titre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contenu_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `utilisateur_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `note_version` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `restaurable` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cr_id` (`cr_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `versions_cr`
--

INSERT INTO `versions_cr` (`id`, `cr_id`, `numero_version`, `titre`, `description`, `contenu_html`, `utilisateur_id`, `date_creation`, `note_version`, `restaurable`) VALUES
(1, 11, 1, 'ddddd', 'ddddd', '<p>ddddd version initiale</p>', 3, '2025-10-21 22:33:35', NULL, 1),
(2, 11, 2, 'ddddd', 'ddddd', '<p>ddddddddd</p>', 3, '2025-10-21 23:00:00', 'Corrections orthographe', 1),
(3, 12, 1, 'fdddd', 'fdddd', '<p>ddddd</p>', 3, '2025-10-21 23:03:35', NULL, 1),
(4, 12, 2, 'fdddd', 'fdddd', '<p>dddddd - V2</p>', 3, '2025-10-22 09:15:00', 'Ajout détails techniques', 1),
(5, 13, 1, 'fff', 'fff', '<p>ffffff</p>', 3, '2025-10-21 23:16:54', NULL, 1),
(6, 13, 2, 'fff', 'fff', '<p>ffffff - version finale</p>', 3, '2025-10-22 10:30:00', 'Révision complète', 1);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `fk_commentaires_utilisateur` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateur` (`num`);

--
-- Contraintes pour la table `cr`
--
ALTER TABLE `cr`
  ADD CONSTRAINT `fk_cr_groupe` FOREIGN KEY (`groupe_id`) REFERENCES `groupes` (`id`),
  ADD CONSTRAINT `fk_cr_utilisateur` FOREIGN KEY (`num_utilisateur`) REFERENCES `utilisateur` (`num`);

--
-- Contraintes pour la table `membres_groupe`
--
ALTER TABLE `membres_groupe`
  ADD CONSTRAINT `fk_membres_groupe` FOREIGN KEY (`groupe_id`) REFERENCES `groupes` (`id`),
  ADD CONSTRAINT `fk_membres_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`);

--
-- Contraintes pour la table `statuts_cr`
--
ALTER TABLE `statuts_cr`
  ADD CONSTRAINT `fk_statuts_evaluateur` FOREIGN KEY (`professeur_evaluateur_id`) REFERENCES `utilisateur` (`num`);

--
-- Contraintes pour la table `validations_cr`
--
ALTER TABLE `validations_cr`
  ADD CONSTRAINT `fk_validations_cr` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`),
  ADD CONSTRAINT `fk_validations_professeur` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateur` (`num`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
