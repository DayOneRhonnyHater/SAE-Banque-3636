-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 06 avr. 2025 à 05:35
-- Version du serveur : 5.7.40
-- Version de PHP : 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `finbot`
--

-- --------------------------------------------------------

--
-- Structure de la table `beneficiaires`
--

DROP TABLE IF EXISTS `beneficiaires`;
CREATE TABLE IF NOT EXISTS `beneficiaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_compte` varchar(34) COLLATE utf8mb4_unicode_ci NOT NULL,
  `banque` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_ajout` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `favori` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_beneficiaire_utilisateur` (`utilisateur_id`,`numero_compte`),
  KEY `idx_utilisateur` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cartes`
--

DROP TABLE IF EXISTS `cartes`;
CREATE TABLE IF NOT EXISTS `cartes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compte_id` int(11) NOT NULL,
  `numero_carte` varchar(19) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_expiration` date NOT NULL,
  `cryptogramme` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plafond_retrait` decimal(10,2) NOT NULL,
  `plafond_paiement` decimal(10,2) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `contactless` tinyint(1) NOT NULL DEFAULT '1',
  `virtuelle` tinyint(1) NOT NULL DEFAULT '0',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_carte` (`numero_carte`),
  KEY `fk_carte_compte` (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `comptes`
--

DROP TABLE IF EXISTS `comptes`;
CREATE TABLE IF NOT EXISTS `comptes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `numero_compte` varchar(34) COLLATE utf8mb4_unicode_ci NOT NULL,
  `solde` decimal(15,2) NOT NULL DEFAULT '0.00',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_derniere_operation` datetime DEFAULT NULL,
  `type_compte_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('ACTIF','BLOQUE','CLOTURE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIF',
  `date_prochaine_capitalisation` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_compte` (`numero_compte`),
  KEY `fk_compte_utilisateur` (`utilisateur_id`),
  KEY `fk_compte_type` (`type_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `connexions`
--

DROP TABLE IF EXISTS `connexions`;
CREATE TABLE IF NOT EXISTS `connexions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `date_connexion` datetime NOT NULL,
  `ip_adresse` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SUCCESS',
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demandes_prets`
--

DROP TABLE IF EXISTS `demandes_prets`;
CREATE TABLE IF NOT EXISTS `demandes_prets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `duree` int(11) NOT NULL,
  `taux` decimal(4,2) NOT NULL,
  `mensualite` decimal(10,2) NOT NULL,
  `motif` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('EN_ATTENTE','APPROUVE','REFUSE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ATTENTE',
  `motif_refus` text COLLATE utf8mb4_unicode_ci,
  `date_demande` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_traitement` datetime DEFAULT NULL,
  `administrateur_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_demande_utilisateur` (`utilisateur_id`),
  KEY `fk_demande_admin` (`administrateur_id`),
  KEY `idx_statut_date` (`statut`,`date_demande`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

DROP TABLE IF EXISTS `factures`;
CREATE TABLE IF NOT EXISTS `factures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `compte_id` int(11) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_echeance` date NOT NULL,
  `date_paiement` date DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('EN_ATTENTE','PAYEE','RETARD') COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodicite` enum('UNIQUE','MENSUEL','TRIMESTRIEL','ANNUEL') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `fk_facture_utilisateur` (`utilisateur_id`),
  KEY `fk_facture_compte` (`compte_id`),
  KEY `idx_statut_echeance` (`statut`,`date_echeance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `logs_administrateur`
--

DROP TABLE IF EXISTS `logs_administrateur`;
CREATE TABLE IF NOT EXISTS `logs_administrateur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `administrateur_id` int(11) NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_action` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_date` (`administrateur_id`,`date_action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages_support`
--

DROP TABLE IF EXISTS `messages_support`;
CREATE TABLE IF NOT EXISTS `messages_support` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL,
  `statut` enum('OUVERT','EN_COURS','RESOLU') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OUVERT',
  `priorite` enum('BASSE','NORMALE','HAUTE','URGENTE') COLLATE utf8mb4_unicode_ci DEFAULT 'NORMALE',
  `administrateur_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_message_utilisateur` (`utilisateur_id`),
  KEY `fk_message_admin` (`administrateur_id`),
  KEY `idx_statut_priorite` (`statut`,`priorite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('TRANSACTION','PRET','FACTURE','SECURITE','AUTRE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_lecture` datetime DEFAULT NULL,
  `lue` tinyint(1) NOT NULL DEFAULT '0',
  `lien` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`utilisateur_id`,`lue`),
  KEY `idx_date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres_systeme`
--

DROP TABLE IF EXISTS `parametres_systeme`;
CREATE TABLE IF NOT EXISTS `parametres_systeme` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modifie_par` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_parametre_modifie` (`modifie_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres_systeme`
--

INSERT INTO `parametres_systeme` (`id`, `valeur`, `description`, `date_modification`, `modifie_par`) VALUES
('duree_session', '30', 'Durée de la session en minutes', '2025-04-04 22:23:41', NULL),
('max_tentatives_connexion', '5', 'Nombre maximum de tentatives de connexion avant blocage', '2025-04-04 22:23:41', NULL),
('plafond_ldds', '12000.00', 'Plafond du LDDS', '2025-04-04 22:23:41', NULL),
('plafond_livret_a', '22950.00', 'Plafond du Livret A', '2025-04-04 22:23:41', NULL),
('plafond_pel', '61200.00', 'Plafond du PEL', '2025-04-04 22:23:41', NULL),
('taux_ldds', '3.00', 'Taux d\'intérêt du LDDS', '2025-04-04 22:23:41', NULL),
('taux_livret_a', '3.00', 'Taux d\'intérêt du Livret A', '2025-04-04 22:23:41', NULL),
('taux_pel', '2.00', 'Taux d\'intérêt du PEL', '2025-04-04 22:23:41', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `preferences_utilisateurs`
--

DROP TABLE IF EXISTS `preferences_utilisateurs`;
CREATE TABLE IF NOT EXISTS `preferences_utilisateurs` (
  `utilisateur_id` int(11) NOT NULL,
  `notification_email` tinyint(1) DEFAULT '1',
  `notification_sms` tinyint(1) DEFAULT '0',
  `mode_sombre` tinyint(1) DEFAULT '0',
  `langue` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'fr',
  PRIMARY KEY (`utilisateur_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prets`
--

DROP TABLE IF EXISTS `prets`;
CREATE TABLE IF NOT EXISTS `prets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `type_pret_id` int(11) NOT NULL,
  `compte_id` int(11) NOT NULL,
  `conseiller_id` int(11) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `duree_mois` int(11) NOT NULL,
  `taux_interet` decimal(5,2) DEFAULT NULL,
  `mensualite` decimal(10,2) DEFAULT NULL,
  `taeg` decimal(5,2) DEFAULT NULL,
  `statut` enum('EN_ATTENTE','APPROUVE','REFUSE','ANNULE','ACCEPTE','REJETE','ACTIF','TERMINE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_demande` datetime NOT NULL,
  `date_decision` datetime DEFAULT NULL,
  `date_acceptation` datetime DEFAULT NULL,
  `date_rejet` datetime DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `date_activation` datetime DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `type_pret_id` (`type_pret_id`),
  KEY `compte_id` (`compte_id`),
  KEY `conseiller_id` (`conseiller_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reponses_support`
--

DROP TABLE IF EXISTS `reponses_support`;
CREATE TABLE IF NOT EXISTS `reponses_support` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_reponse_message` (`message_id`),
  KEY `fk_reponse_auteur` (`auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `securite`
--

DROP TABLE IF EXISTS `securite`;
CREATE TABLE IF NOT EXISTS `securite` (
  `utilisateur_id` int(11) NOT NULL,
  `derniere_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `derniere_localisation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dernier_user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tentatives_connexion` int(11) NOT NULL DEFAULT '0',
  `compte_bloque` tinyint(1) NOT NULL DEFAULT '0',
  `date_blocage` datetime DEFAULT NULL,
  `date_derniere_connexion` datetime DEFAULT NULL,
  `date_derniere_tentative` datetime DEFAULT NULL,
  `cle_2fa` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions_persistantes`
--

DROP TABLE IF EXISTS `sessions_persistantes`;
CREATE TABLE IF NOT EXISTS `sessions_persistantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` datetime NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `derniere_utilisation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `idx_expiration` (`expiration`),
  KEY `idx_user_token` (`utilisateur_id`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compte_id` int(11) NOT NULL,
  `type_transaction` enum('DEBIT','CREDIT','VIREMENT','INTERET') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `date_transaction` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_valeur` timestamp NULL DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `categorie` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beneficiaire` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compte_destinataire` varchar(34) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_compte_date` (`compte_id`,`date_transaction`),
  KEY `idx_categorie` (`categorie`),
  KEY `idx_date` (`date_transaction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `types_comptes`
--

DROP TABLE IF EXISTS `types_comptes`;
CREATE TABLE IF NOT EXISTS `types_comptes` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `taux_interet` decimal(4,2) NOT NULL DEFAULT '0.00',
  `plafond` decimal(15,2) DEFAULT NULL,
  `conditions_ouverture` text COLLATE utf8mb4_unicode_ci,
  `frais_tenue` decimal(6,2) DEFAULT '0.00',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `types_comptes`
--

INSERT INTO `types_comptes` (`id`, `nom`, `description`, `taux_interet`, `plafond`, `conditions_ouverture`, `frais_tenue`, `actif`, `date_modification`) VALUES
('COURANT', 'Compte Courant', 'Compte bancaire standard pour les opérations quotidiennes', '0.00', NULL, NULL, '0.00', 1, '2025-04-04 22:23:41'),
('LDDS', 'Livret Développement Durable', 'Compte d\'épargne pour le développement durable', '3.00', '12000.00', NULL, '0.00', 1, '2025-04-04 22:23:41'),
('LIVRET_A', 'Livret A', 'Compte d\'épargne réglementé par l\'État français', '3.00', '22950.00', NULL, '0.00', 1, '2025-04-04 22:23:41'),
('PEL', 'Plan Épargne Logement', 'Compte d\'épargne pour un projet immobilier', '2.00', '61200.00', NULL, '0.00', 1, '2025-04-04 22:23:41');

-- --------------------------------------------------------

--
-- Structure de la table `types_prets`
--

DROP TABLE IF EXISTS `types_prets`;
CREATE TABLE IF NOT EXISTS `types_prets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `taux_min` decimal(5,2) NOT NULL,
  `taux_max` decimal(5,2) NOT NULL,
  `montant_min` decimal(10,2) NOT NULL,
  `montant_max` decimal(10,2) NOT NULL,
  `duree_min` int(11) NOT NULL,
  `duree_max` int(11) NOT NULL,
  `actif` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(161) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'France',
  `deux_facteurs` tinyint(1) NOT NULL DEFAULT '0',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` datetime DEFAULT NULL,
  `avatar` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('ACTIF','BLOQUE','INACTIF') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIF',
  `preferences_notifications` json DEFAULT NULL,
  `role` enum('CLIENT','ADMINISTRATEUR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CLIENT',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_nom_prenom` (`nom`,`prenom`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `virements_programmes`
--

DROP TABLE IF EXISTS `virements_programmes`;
CREATE TABLE IF NOT EXISTS `virements_programmes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `compte_source_id` int(11) NOT NULL,
  `compte_destinataire` varchar(34) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequence` enum('UNIQUE','HEBDOMADAIRE','MENSUEL','TRIMESTRIEL','ANNUEL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `jour_execution` tinyint(2) DEFAULT NULL,
  `statut` enum('ACTIF','SUSPENDU','TERMINE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIF',
  `derniere_execution` datetime DEFAULT NULL,
  `prochaine_execution` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_programmation_utilisateur` (`utilisateur_id`),
  KEY `fk_programmation_compte` (`compte_source_id`),
  KEY `idx_prochaine_execution` (`prochaine_execution`,`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `beneficiaires`
--
ALTER TABLE `beneficiaires`
  ADD CONSTRAINT `fk_beneficiaire_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cartes`
--
ALTER TABLE `cartes`
  ADD CONSTRAINT `fk_carte_compte` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `comptes`
--
ALTER TABLE `comptes`
  ADD CONSTRAINT `fk_compte_type` FOREIGN KEY (`type_compte_id`) REFERENCES `types_comptes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compte_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes_prets`
--
ALTER TABLE `demandes_prets`
  ADD CONSTRAINT `fk_demande_admin` FOREIGN KEY (`administrateur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `fk_demande_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `factures`
--
ALTER TABLE `factures`
  ADD CONSTRAINT `fk_facture_compte` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_facture_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `logs_administrateur`
--
ALTER TABLE `logs_administrateur`
  ADD CONSTRAINT `fk_log_administrateur` FOREIGN KEY (`administrateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `messages_support`
--
ALTER TABLE `messages_support`
  ADD CONSTRAINT `fk_message_admin` FOREIGN KEY (`administrateur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `fk_message_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `parametres_systeme`
--
ALTER TABLE `parametres_systeme`
  ADD CONSTRAINT `fk_parametre_modifie` FOREIGN KEY (`modifie_par`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `reponses_support`
--
ALTER TABLE `reponses_support`
  ADD CONSTRAINT `fk_reponse_auteur` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `fk_reponse_message` FOREIGN KEY (`message_id`) REFERENCES `messages_support` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `securite`
--
ALTER TABLE `securite`
  ADD CONSTRAINT `fk_securite_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions_persistantes`
--
ALTER TABLE `sessions_persistantes`
  ADD CONSTRAINT `fk_session_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transaction_compte` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `virements_programmes`
--
ALTER TABLE `virements_programmes`
  ADD CONSTRAINT `fk_programmation_compte` FOREIGN KEY (`compte_source_id`) REFERENCES `comptes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_programmation_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
