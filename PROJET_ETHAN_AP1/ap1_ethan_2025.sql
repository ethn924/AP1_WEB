-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 22 oct. 2025 à 11:27
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

--
-- Base de données : `ap1_ethan_2025`
--

-- --------------------------------------------------------

--
-- Structure de la table `analytics_cr`
--

DROP TABLE IF EXISTS `analytics_cr`;
CREATE TABLE IF NOT EXISTS `analytics_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int DEFAULT NULL,
  `mois` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_cr` int DEFAULT '0',
  `cr_soumis` int DEFAULT '0',
  `cr_evalues` int DEFAULT '0',
  `cr_approuves` int DEFAULT '0',
  `taux_soumission` decimal(5,2) DEFAULT '0.00',
  `taux_evaluation` decimal(5,2) DEFAULT '0.00',
  `delai_moyen_evaluation` decimal(10,2) DEFAULT '0.00',
  `date_calcul` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupe_mois` (`groupe_id`,`mois`),
  KEY `groupe_id` (`groupe_id`),
  KEY `mois` (`mois`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `archives_cr`
--

DROP TABLE IF EXISTS `archives_cr`;
CREATE TABLE IF NOT EXISTS `archives_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `utilisateur_id` int NOT NULL,
  `raison_archivage` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_archivage` datetime DEFAULT CURRENT_TIMESTAMP,
  `archivable` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cr_id` (`cr_id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE IF NOT EXISTS `audit_trail` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entite` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entite_id` bigint DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anciennes_donnees` json DEFAULT NULL,
  `nouvelles_donnees` json DEFAULT NULL,
  `adresse_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_action` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `action` (`action`),
  KEY `entite` (`entite`),
  KEY `entite_id` (`entite_id`),
  KEY `date_action` (`date_action`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `versions_cr_audit`
--

DROP TABLE IF EXISTS `versions_cr_audit`;
CREATE TABLE IF NOT EXISTS `versions_cr_audit` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `numero_version` int NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contenu_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `utilisateur_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `note_version` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type_modification` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'modification',
  `nb_caracteres_ajoutes` int DEFAULT '0',
  `nb_caracteres_supprimes` int DEFAULT '0',
  `taille_fichier` int DEFAULT NULL,
  `restaurable` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cr_id` (`cr_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `date_creation` (`date_creation`),
  KEY `numero_version` (`numero_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_export`
--

DROP TABLE IF EXISTS `audit_export`;
CREATE TABLE IF NOT EXISTS `audit_export` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_export` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pdf',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `filtres` json DEFAULT NULL,
  `nombre_enregistrements` int DEFAULT '0',
  `chemin_fichier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_export` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_suppression` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `date_export` (`date_export`),
  KEY `type_export` (`type_export`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `checklists_modeles`
--

DROP TABLE IF EXISTS `checklists_modeles`;
CREATE TABLE IF NOT EXISTS `checklists_modeles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modele_id` int NOT NULL,
  `item_texte` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordre` int NOT NULL,
  `obligatoire` tinyint(1) DEFAULT '1',
  `description_aide` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `modele_id` (`modele_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenu_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `vu` tinyint(1) DEFAULT '0',
  `archivé` tinyint(1) DEFAULT '0',
  `datetime` datetime DEFAULT NULL,
  `num_version` int DEFAULT '1',
  `num_utilisateur` int DEFAULT NULL,
  `groupe_id` int DEFAULT NULL,
  PRIMARY KEY (`num`),
  KEY `num_utilisateur` (`num_utilisateur`),
  KEY `groupe_id` (`groupe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cr`
--

INSERT INTO `cr` (`num`, `date`, `description`, `titre`, `contenu_html`, `vu`, `archivé`, `datetime`, `num_version`, `num_utilisateur`, `groupe_id`) VALUES
(10, '2025-10-21', 'ffff', NULL, '<p>ffff</p>', 0, 0, '2025-10-21 22:21:07', 1, 3, NULL),
(11, '2025-10-21', 'ddddd', NULL, '<p>ddddddddd</p>', 0, 0, '2025-10-21 22:33:35', 1, 3, NULL),
(12, '2025-10-21', 'fdddd', NULL, '<p>ddddd</p>', 0, 0, '2025-10-21 23:03:35', 1, 3, NULL),
(13, '2025-10-21', 'fff', NULL, '<p>ffffff</p>', 0, 0, '2025-10-21 23:16:54', 1, 3, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `groupes`
--

DROP TABLE IF EXISTS `groupes`;
CREATE TABLE IF NOT EXISTS `groupes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `professeur_responsable_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `professeur_responsable_id` (`professeur_responsable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Structure de la table `membres_groupe`
--

DROP TABLE IF EXISTS `membres_groupe`;
CREATE TABLE IF NOT EXISTS `membres_groupe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupe_utilisateur` (`groupe_id`,`utilisateur_id`),
  KEY `groupe_id` (`groupe_id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `modeles_cr`
--

DROP TABLE IF EXISTS `modeles_cr`;
CREATE TABLE IF NOT EXISTS `modeles_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `contenu_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `professeur_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `professeur_id` (`professeur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Structure de la table `rappels_soumission`
--

DROP TABLE IF EXISTS `rappels_soumission`;
CREATE TABLE IF NOT EXISTS `rappels_soumission` (
  `id` int NOT NULL AUTO_INCREMENT,
  `groupe_id` int NOT NULL,
  `date_limite` datetime NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `professeur_id` int NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `groupe_id` (`groupe_id`),
  KEY `professeur_id` (`professeur_id`),
  KEY `date_limite` (`date_limite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stage`
--

INSERT INTO `stage` (`num`, `nom`, `adresse`, `CP`, `ville`, `tel`, `libelleStage`, `email`, `num_tuteur`) VALUES
(1, 'First Car', '15 Rue Carnot', 92400, 'Courbevoie', '01 23 45 67 89', 'BTS SIO SLAM : faire un site internet', 'firstcar92@gmail.com', 1),
(2, 'First Car', '15 Rue Carnot', 92400, 'Courbevoie', '01 23 45 67 89', 'Faire un site internet', 'firstcar92@gmail.com', 2);

-- --------------------------------------------------------

--
-- Structure de la table `statuts_cr`
--

DROP TABLE IF EXISTS `statuts_cr`;
CREATE TABLE IF NOT EXISTS `statuts_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `statut` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tuteur`
--

INSERT INTO `tuteur` (`num`, `nom`, `prenom`, `tel`, `email`) VALUES
(1, 'Morin', 'Nicolas', '01 23 45 67 89', 'nicolasmorin@gmail.com'),
(2, 'Morin', 'Nicolas', '01 23 45 67 89', 'nicolas@gmail.com');

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`num`, `nom`, `prenom`, `tel`, `login`, `motdepasse`, `type`, `email`, `option`, `num_stage`, `token`, `date`, `token_created_at`, `email_valide`, `code_verification`, `email_verification_token`, `email_verification_token_created_at`) VALUES
(3, 'Lalienne', 'Ethan', NULL, 'ethan.lalienne', '29f5ea398a0230273b0231d8643d6b9d', 0, 'ethanlalienne92@gmail.com', NULL, 2, '', NULL, NULL, 1, NULL, NULL, NULL),
(4, 'Gravouil', 'Benjamin', NULL, 'benjamin.gravouil', '3e5df8603e419d62842d33ff7e00e5db', 1, 'benjamin.gravouil@gmail.com', NULL, NULL, '', NULL, NULL, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `validations_cr`
--

DROP TABLE IF EXISTS `validations_cr`;
CREATE TABLE IF NOT EXISTS `validations_cr` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `checklist_item_id` int NOT NULL,
  `complete` tinyint(1) DEFAULT '0',
  `commentaire` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_verification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_item` (`cr_id`,`checklist_item_id`),
  KEY `cr_id` (`cr_id`),
  KEY `checklist_item_id` (`checklist_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `versions_cr`
--

DROP TABLE IF EXISTS `versions_cr`;
CREATE TABLE IF NOT EXISTS `versions_cr` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `cr_id` bigint NOT NULL,
  `numero_version` int NOT NULL,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `analytics_cr`
--
ALTER TABLE `analytics_cr`
  ADD CONSTRAINT `analytics_cr_ibfk_1` FOREIGN KEY (`groupe_id`) REFERENCES `groupes` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `archives_cr`
--
ALTER TABLE `archives_cr`
  ADD CONSTRAINT `archives_cr_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `archives_cr_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `versions_cr_audit`
--
ALTER TABLE `versions_cr_audit`
  ADD CONSTRAINT `versions_cr_audit_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `versions_cr_audit_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `audit_export`
--
ALTER TABLE `audit_export`
  ADD CONSTRAINT `audit_export_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `checklists_modeles`
--
ALTER TABLE `checklists_modeles`
  ADD CONSTRAINT `checklists_modeles_ibfk_1` FOREIGN KEY (`modele_id`) REFERENCES `modeles_cr` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateur` (`num`);

--
-- Contraintes pour la table `groupes`
--
ALTER TABLE `groupes`
  ADD CONSTRAINT `groupes_ibfk_1` FOREIGN KEY (`professeur_responsable_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `logs_erreurs`
--
ALTER TABLE `logs_erreurs`
  ADD CONSTRAINT `logs_erreurs_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE SET NULL;

--
-- Contraintes pour la table `membres_groupe`
--
ALTER TABLE `membres_groupe`
  ADD CONSTRAINT `membres_groupe_ibfk_1` FOREIGN KEY (`groupe_id`) REFERENCES `groupes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membres_groupe_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modeles_cr`
--
ALTER TABLE `modeles_cr`
  ADD CONSTRAINT `modeles_cr_ibfk_1` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

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
-- Contraintes pour la table `rappels_soumission`
--
ALTER TABLE `rappels_soumission`
  ADD CONSTRAINT `rappels_soumission_ibfk_1` FOREIGN KEY (`groupe_id`) REFERENCES `groupes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rappels_soumission_ibfk_2` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sauvegardes_auto`
--
ALTER TABLE `sauvegardes_auto`
  ADD CONSTRAINT `sauvegardes_auto_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `sauvegardes_auto_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

--
-- Contraintes pour la table `statuts_cr`
--
ALTER TABLE `statuts_cr`
  ADD CONSTRAINT `statuts_cr_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `statuts_cr_ibfk_2` FOREIGN KEY (`professeur_evaluateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE SET NULL;

--
-- Contraintes pour la table `validations_cr`
--
ALTER TABLE `validations_cr`
  ADD CONSTRAINT `validations_cr_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `validations_cr_ibfk_2` FOREIGN KEY (`checklist_item_id`) REFERENCES `checklists_modeles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `versions_cr`
--
ALTER TABLE `versions_cr`
  ADD CONSTRAINT `versions_cr_ibfk_1` FOREIGN KEY (`cr_id`) REFERENCES `cr` (`num`) ON DELETE CASCADE,
  ADD CONSTRAINT `versions_cr_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`num`) ON DELETE CASCADE;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;