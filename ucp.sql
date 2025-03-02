-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 02 mars 2025 à 19:30
-- Version du serveur : 9.1.0
-- Version de PHP : 8.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ucp`
--

-- --------------------------------------------------------

--
-- Structure de la table `characters`
--

DROP TABLE IF EXISTS `characters`;
CREATE TABLE IF NOT EXISTS `characters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `first_last_name` varchar(100) NOT NULL,
  `age` int NOT NULL,
  `ethnicity` varchar(100) NOT NULL,
  `background` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `wallet_balance` decimal(15,2) DEFAULT '1000.00',
  `admin_comment` text,
  `reviewer_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_transaction` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `reviewer_id` (`reviewer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `characters`
--

INSERT INTO `characters` (`id`, `user_id`, `first_last_name`, `age`, `ethnicity`, `background`, `status`, `wallet_balance`, `admin_comment`, `reviewer_id`, `created_at`, `updated_at`, `last_transaction`) VALUES
(1, 1, 'Sami Test', 28, 'Hispa', 'backgound de test', 'approved', 0.03, 'bienvenue et bon jeu à toi', 1, '2025-03-01 16:15:21', '2025-03-02 08:56:56', '2025-03-02 08:56:56'),
(2, 1, 'Frank_Sali', 29, 'Afro', 'TEST AFRO BACKGROUND', 'pending', 1000.00, NULL, NULL, '2025-03-01 16:58:36', '2025-03-01 16:58:36', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `character_crypto`
--

DROP TABLE IF EXISTS `character_crypto`;
CREATE TABLE IF NOT EXISTS `character_crypto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `crypto_symbol` varchar(20) NOT NULL,
  `crypto_name` varchar(100) NOT NULL,
  `amount` decimal(20,8) NOT NULL DEFAULT '0.00000000',
  `purchase_value_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `character_crypto_unique` (`character_id`,`crypto_symbol`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `character_crypto`
--

INSERT INTO `character_crypto` (`id`, `character_id`, `crypto_symbol`, `crypto_name`, `amount`, `purchase_value_total`, `last_updated`) VALUES
(3, 1, 'bitcoin', 'Bitcoin', 0.00500000, 411.03, '2025-03-01 19:53:40'),
(4, 1, 'ripple', 'XRP', 272.31000000, 593.64, '2025-03-02 08:56:56');

-- --------------------------------------------------------

--
-- Structure de la table `crypto_transactions`
--

DROP TABLE IF EXISTS `crypto_transactions`;
CREATE TABLE IF NOT EXISTS `crypto_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `character_id` int NOT NULL,
  `transaction_type` enum('buy','sell') NOT NULL,
  `crypto_symbol` varchar(20) NOT NULL,
  `crypto_name` varchar(100) NOT NULL,
  `amount` decimal(20,8) NOT NULL,
  `price_per_unit` decimal(15,2) NOT NULL,
  `total_value` decimal(15,2) NOT NULL,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `character_id` (`character_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `crypto_transactions`
--

INSERT INTO `crypto_transactions` (`id`, `character_id`, `transaction_type`, `crypto_symbol`, `crypto_name`, `amount`, `price_per_unit`, `total_value`, `transaction_date`) VALUES
(1, 1, 'buy', 'bitcoin', 'Bitcoin', 0.01000000, 81881.00, 818.81, '2025-03-01 16:29:16'),
(2, 1, 'buy', 'ripple', 'XRP', 80.00000000, 2.08, 166.40, '2025-03-01 17:01:02'),
(3, 1, 'sell', 'ripple', 'XRP', 80.00000000, 2.09, 167.20, '2025-03-01 19:46:03'),
(4, 1, 'sell', 'bitcoin', 'Bitcoin', 0.01000000, 82271.00, 822.71, '2025-03-01 19:51:20'),
(5, 1, 'buy', 'bitcoin', 'Bitcoin', 0.00500000, 82206.00, 411.03, '2025-03-01 19:53:40'),
(6, 1, 'buy', 'ripple', 'XRP', 272.31000000, 2.18, 593.64, '2025-03-02 08:56:56');

-- --------------------------------------------------------

--
-- Structure de la table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `country_code` varchar(2) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `success` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `ip_address`, `user_agent`, `country_code`, `country_name`, `login_time`, `success`) VALUES
(1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', NULL, 'Inconnu', '2025-03-01 15:41:02', 1),
(2, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36', NULL, 'Inconnu', '2025-03-01 15:57:42', 1),
(3, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', NULL, 'Inconnu', '2025-03-01 19:14:30', 0),
(4, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', NULL, 'Inconnu', '2025-03-01 19:14:36', 0),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', NULL, 'Inconnu', '2025-03-01 19:14:41', 1);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `role` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text,
  `is_banned` tinyint(1) DEFAULT '0',
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `is_admin`, `role`, `profile_image`, `bio`, `is_banned`, `last_login`, `last_ip`, `last_activity`) VALUES
(1, 'sami', 'samicaron6@gmail.com', '$2y$10$pmq3WWzEUAzuKK0lUFinaOqBxjFjURY.ujEloJ/1xUzCHB4sYGkme', '2025-03-01 14:57:59', 1, 'Fondateur', NULL, 'Fondateur du projet', 0, '2025-03-01 19:14:41', '::1', '2025-03-02 19:29:32');

-- --------------------------------------------------------

--
-- Structure de la table `user_bans`
--

DROP TABLE IF EXISTS `user_bans`;
CREATE TABLE IF NOT EXISTS `user_bans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `reason` text,
  `banned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ban_expiry` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
