-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 23 Jun 2025 pada 02.08
-- Versi server: 11.4.5-MariaDB-deb11
-- Versi PHP: 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user_up`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `advertisers`
--

CREATE TABLE `advertisers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `advertisers`
--

INSERT INTO `advertisers` (`id`, `name`, `email`, `company`, `website`, `phone`, `address`, `contact_person`, `status`, `created_at`, `updated_at`) VALUES
(1, 'AdStart Network', 'admin@adstart.click', 'AdStart Media', 'https://adstart.click', NULL, NULL, 'Admin Team', 'active', '2025-06-22 18:49:14', '2025-06-22 18:49:14'),
(2, 'Premium Ads Co', 'contact@premiumads.com', 'Premium Advertising', 'https://premiumads.com', NULL, NULL, 'John Smith', 'active', '2025-06-22 18:49:14', '2025-06-22 18:49:14'),
(3, 'Digital Marketing Pro', 'hello@digitalmarketing.com', 'Digital Marketing Solutions', 'https://digitalmarketing.com', NULL, NULL, 'Jane Doe', 'active', '2025-06-22 18:49:14', '2025-06-22 18:49:14'),
(4, 'E-Commerce Giant', 'ads@ecommerce.com', 'E-Commerce Solutions', 'https://ecommerce.com', NULL, NULL, 'Bob Johnson', 'active', '2025-06-22 18:49:14', '2025-06-22 18:49:14'),
(5, 'Tech Startup Inc', 'marketing@techstartup.com', 'Tech Startup', 'https://techstartup.com', NULL, NULL, 'Alice Wilson', 'active', '2025-06-22 18:49:14', '2025-06-22 18:49:14'),
(6, 'Finance Pro Ltd', 'ads@financepro.com', 'Finance Professional', 'https://financepro.com', NULL, NULL, 'Mike Brown', 'active', '2025-06-22 18:49:14', '2025-06-22 18:49:14'),
(7, 'Retail Network', 'marketing@retail.com', 'Retail Solutions', 'https://retail.com', NULL, NULL, 'Sarah Davis', 'active', '2025-06-22 18:49:14', '2025-06-22 18:49:14'),
(8, 'AdStart Network', 'admin@adstart.click', 'AdStart Media', 'https://adstart.click', NULL, NULL, 'Admin Team', 'active', '2025-06-22 18:49:27', '2025-06-22 18:49:27'),
(9, 'Premium Ads Co', 'contact@premiumads.com', 'Premium Advertising', 'https://premiumads.com', NULL, NULL, 'John Smith', 'active', '2025-06-22 18:49:27', '2025-06-22 18:49:27'),
(10, 'Digital Marketing Pro', 'hello@digitalmarketing.com', 'Digital Marketing Solutions', 'https://digitalmarketing.com', NULL, NULL, 'Jane Doe', 'active', '2025-06-22 18:49:27', '2025-06-22 18:49:27'),
(11, 'E-Commerce Giant', 'ads@ecommerce.com', 'E-Commerce Solutions', 'https://ecommerce.com', NULL, NULL, 'Bob Johnson', 'active', '2025-06-22 18:49:27', '2025-06-22 18:49:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ad_formats`
--

CREATE TABLE `ad_formats` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('banner','native','video','popup','interstitial','rewarded','in_stream','out_stream') NOT NULL,
  `sizes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sizes`)),
  `min_width` int(11) DEFAULT NULL,
  `max_width` int(11) DEFAULT NULL,
  `min_height` int(11) DEFAULT NULL,
  `max_height` int(11) DEFAULT NULL,
  `supports_html5` tinyint(1) DEFAULT 1,
  `supports_script` tinyint(1) DEFAULT 1,
  `supports_image` tinyint(1) DEFAULT 1,
  `supports_video` tinyint(1) DEFAULT 0,
  `default_cpm` decimal(10,4) DEFAULT 0.0000,
  `default_cpc` decimal(10,4) DEFAULT 0.0000,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ad_formats`
--

INSERT INTO `ad_formats` (`id`, `name`, `description`, `type`, `sizes`, `min_width`, `max_width`, `min_height`, `max_height`, `supports_html5`, `supports_script`, `supports_image`, `supports_video`, `default_cpm`, `default_cpc`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Standard Banner', 'Standard banner ad format for display advertising', 'banner', '[\"728x90\", \"300x250\", \"160x600\", \"300x100\", \"300x50\"]', NULL, NULL, NULL, NULL, 1, 1, 1, 0, 1.5000, 0.2500, 'active', '2025-06-21 20:20:42', '2025-06-21 20:20:42'),
(2, 'Mobile Banner', 'Mobile-optimized banner ads', 'banner', '[\"320x50\", \"300x50\", \"300x100\", \"320x100\"]', NULL, NULL, NULL, NULL, 1, 1, 1, 0, 2.0000, 0.3000, 'active', '2025-06-21 20:20:42', '2025-06-21 20:20:42'),
(3, 'Video Pre-roll', 'Video advertisement played before main content', 'video', '[\"640x360\", \"480x270\", \"1280x720\"]', NULL, NULL, NULL, NULL, 1, 1, 0, 1, 5.0000, 0.5000, 'active', '2025-06-21 20:20:42', '2025-06-21 20:20:42'),
(4, 'Native Feed', 'Native ads that blend with content feed', 'native', '[]', NULL, NULL, NULL, NULL, 1, 1, 1, 1, 3.0000, 0.4000, 'active', '2025-06-21 20:20:42', '2025-06-21 20:20:42'),
(5, 'Popup', 'Popup advertisement window', 'popup', '[\"800x600\", \"1024x768\", \"600x400\"]', NULL, NULL, NULL, NULL, 1, 1, 1, 1, 4.0000, 0.3500, 'active', '2025-06-21 20:20:42', '2025-06-21 20:20:42'),
(6, 'Interstitial', 'Full-screen ad that covers the interface', 'interstitial', '[\"320x480\", \"768x1024\", \"414x736\"]', NULL, NULL, NULL, NULL, 1, 1, 1, 1, 6.0000, 0.6000, 'active', '2025-06-21 20:20:42', '2025-06-21 20:20:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `blacklists`
--

CREATE TABLE `blacklists` (
  `id` int(11) NOT NULL,
  `type` enum('ip','domain','user_agent','country','keyword') NOT NULL,
  `value` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_global` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `campaign_stats_daily`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `campaign_stats_daily` (
`date` date
,`campaign_id` int(11)
,`campaign_type` enum('rtb','ron')
,`publisher_id` int(11)
,`advertiser_id` int(11)
,`impressions` bigint(21)
,`clicks` bigint(21)
,`conversions` bigint(21)
,`revenue` decimal(32,4)
,`publisher_revenue` decimal(32,4)
,`platform_revenue` decimal(32,4)
,`ctr` decimal(26,2)
,`conversion_rate` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('adult','mainstream') DEFAULT 'mainstream',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `type`, `status`, `created_at`, `updated_at`) VALUES
(273, 'Adult', 'Adult content and related services', 'adult', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40'),
(274, 'Mainstream', 'General content suitable for all audiences', 'mainstream', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40'),
(275, 'Dating', 'Dating and relationship services', 'mainstream', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40'),
(276, 'Gaming', 'Video games and gaming services', 'mainstream', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40'),
(277, 'Finance', 'Financial services and products', 'mainstream', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40'),
(278, 'Health', 'Health and wellness products', 'mainstream', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40'),
(279, 'Technology', 'Technology and software products', 'mainstream', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40'),
(280, 'Entertainment', 'Entertainment and media content', 'mainstream', 'active', '2025-06-22 17:15:40', '2025-06-22 17:15:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `clicks`
--

CREATE TABLE `clicks` (
  `id` bigint(20) NOT NULL,
  `impression_id` bigint(20) DEFAULT NULL,
  `zone_id` varchar(100) DEFAULT NULL,
  `zone_token` varchar(100) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `campaign_type` enum('rtb','ron') DEFAULT NULL,
  `creative_id` int(11) DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `advertiser_id` int(11) DEFAULT NULL,
  `website_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `ip_hash` varchar(64) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `user_agent_hash` varchar(64) DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `device` varchar(20) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `ad_size` varchar(20) DEFAULT NULL,
  `bid_amount` decimal(10,4) DEFAULT NULL,
  `revenue_share` decimal(5,2) DEFAULT NULL,
  `publisher_revenue` decimal(10,4) DEFAULT NULL,
  `platform_revenue` decimal(10,4) DEFAULT NULL,
  `click_url` varchar(1000) DEFAULT NULL,
  `landing_page` varchar(1000) DEFAULT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `hour` tinyint(4) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `is_unique` tinyint(1) DEFAULT 1,
  `is_valid` tinyint(1) DEFAULT 1,
  `is_bot` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `conversions`
--

CREATE TABLE `conversions` (
  `id` bigint(20) NOT NULL,
  `click_id` bigint(20) DEFAULT NULL,
  `impression_id` bigint(20) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `campaign_type` enum('rtb','ron') DEFAULT NULL,
  `creative_id` int(11) DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `advertiser_id` int(11) DEFAULT NULL,
  `conversion_type` varchar(50) DEFAULT NULL,
  `conversion_value` decimal(15,4) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `postback_url` varchar(1000) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `hour` tinyint(4) DEFAULT NULL,
  `is_valid` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `creatives`
--

CREATE TABLE `creatives` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `campaign_type` enum('rtb','ron') NOT NULL,
  `name` varchar(255) NOT NULL,
  `creative_type` enum('banner','text','video','native') DEFAULT 'banner',
  `method` enum('html5','script') DEFAULT 'html5',
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `html_content` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `call_to_action` varchar(100) DEFAULT NULL,
  `bid_amount` decimal(10,4) DEFAULT 0.0000,
  `click_url` varchar(500) DEFAULT NULL,
  `impression_tracking_url` varchar(500) DEFAULT NULL,
  `click_tracking_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','pending','rejected') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `creatives`
--

INSERT INTO `creatives` (`id`, `campaign_id`, `campaign_type`, `name`, `creative_type`, `method`, `width`, `height`, `image_url`, `html_content`, `title`, `description`, `call_to_action`, `bid_amount`, `click_url`, `impression_tracking_url`, `click_tracking_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'ron', 'RTB Creative 300x250 (Medium Rectangle)', 'banner', 'html5', 300, 250, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>300x250 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(2, 1, 'ron', 'RON Creative 300x250 (Medium Rectangle)', 'banner', 'html5', 300, 250, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>300x250 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(3, 1, 'ron', 'RTB Creative 728x90 (Leaderboard)', 'banner', 'html5', 728, 90, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>728x90 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(4, 1, 'ron', 'RON Creative 728x90 (Leaderboard)', 'banner', 'html5', 728, 90, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>728x90 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(5, 1, 'ron', 'RTB Creative 320x50 (Mobile Banner)', 'banner', 'html5', 320, 50, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>320x50 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(6, 1, 'ron', 'RON Creative 320x50 (Mobile Banner)', 'banner', 'html5', 320, 50, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>320x50 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(7, 1, 'ron', 'RTB Creative 300x600 (Half Page)', 'banner', 'html5', 300, 600, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>300x600 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(8, 1, 'ron', 'RON Creative 300x600 (Half Page)', 'banner', 'html5', 300, 600, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>300x600 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(9, 1, 'ron', 'RTB Creative 970x250 (Billboard)', 'banner', 'html5', 970, 250, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>970x250 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(10, 1, 'ron', 'RON Creative 970x250 (Billboard)', 'banner', 'html5', 970, 250, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>970x250 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:35', '2025-06-22 18:34:31'),
(11, 1, 'ron', 'RTB Creative 160x600 (Wide Skyscraper)', 'banner', 'html5', 160, 600, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>160x600 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:36', '2025-06-22 18:34:31'),
(12, 1, 'ron', 'RON Creative 160x600 (Wide Skyscraper)', 'banner', 'html5', 160, 600, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>160x600 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:36', '2025-06-22 18:34:31'),
(13, 1, 'ron', 'RTB Creative 336x280 (Large Rectangle)', 'banner', 'html5', 336, 280, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>336x280 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:36', '2025-06-22 18:34:31'),
(14, 1, 'ron', 'RON Creative 336x280 (Large Rectangle)', 'banner', 'html5', 336, 280, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>336x280 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:36', '2025-06-22 18:34:31'),
(15, 1, 'ron', 'RTB Creative 468x60 (Banner)', 'banner', 'html5', 468, 60, NULL, '<div style=\'background:linear-gradient(135deg, #007bff 0%, #0056b3 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(0,123,255,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #ffd700, #ffed4e);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>💎</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Premium RTB Deal</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Exclusive premium offers! Limited time - Act now!</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>Get Deal →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>468x60 RTB</small>\n                </div>', 'Premium RTB Deal', 'Exclusive premium offers with limited time opportunity', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:36', '2025-06-22 18:34:31'),
(16, 1, 'ron', 'RON Creative 468x60 (Banner)', 'banner', 'html5', 468, 60, NULL, '<div style=\'background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%);color:white;padding:15px;text-align:center;height:100%;box-sizing:border-box;display:flex;flex-direction:column;justify-content:center;font-family:Arial,sans-serif;border-radius:8px;box-shadow:0 4px 12px rgba(40,167,69,0.3);position:relative;overflow:hidden;\'>\n                    <div style=\'position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg, #32cd32, #98fb98);\'></div>\n                    <div style=\'font-size:22px;margin-bottom:8px;\'>🚀</div>\n                    <h4 style=\'margin:0 0 8px 0;font-size:16px;font-weight:bold;text-shadow:0 1px 2px rgba(0,0,0,0.3);\'>Network Special</h4>\n                    <p style=\'margin:0 0 12px 0;font-size:12px;opacity:0.9;line-height:1.4;\'>Amazing network deals! Don\'t miss these offers.</p>\n                    <div style=\'margin-top:auto;\'>\n                        <a href=\'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network\' target=\'_blank\' rel=\'noopener\' style=\'display:inline-block;background:rgba(255,255,255,0.2);color:white;padding:8px 16px;text-decoration:none;border-radius:20px;font-size:11px;font-weight:bold;border:1px solid rgba(255,255,255,0.3);transition:all 0.3s;backdrop-filter:blur(10px);\'>View Deals →</a>\n                    </div>\n                    <small style=\'font-size:9px;opacity:0.6;margin-top:8px;\'>468x60 RON</small>\n                </div>', 'Network Special', 'Amazing network deals with incredible offers', NULL, 0.0000, NULL, NULL, NULL, 'active', '2025-06-22 18:33:36', '2025-06-22 18:34:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `financial_transactions`
--

CREATE TABLE `financial_transactions` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `advertiser_id` int(11) DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `transaction_type` enum('deposit','withdrawal','payment','revenue','refund','adjustment','bonus') NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `description` text DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `external_transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `impressions`
--

CREATE TABLE `impressions` (
  `id` bigint(20) NOT NULL,
  `zone_id` varchar(100) DEFAULT NULL,
  `zone_token` varchar(100) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `campaign_type` enum('rtb','ron') DEFAULT NULL,
  `creative_id` int(11) DEFAULT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `advertiser_id` int(11) DEFAULT NULL,
  `website_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `ip_hash` varchar(64) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `user_agent_hash` varchar(64) DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `device` varchar(20) DEFAULT NULL,
  `browser` varchar(50) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `ad_size` varchar(20) DEFAULT NULL,
  `bid_amount` decimal(10,4) DEFAULT NULL,
  `revenue_share` decimal(5,2) DEFAULT NULL,
  `publisher_revenue` decimal(10,4) DEFAULT NULL,
  `platform_revenue` decimal(10,4) DEFAULT NULL,
  `timestamp` bigint(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `hour` tinyint(4) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `is_unique` tinyint(1) DEFAULT 1,
  `is_bot` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `publishers`
--

CREATE TABLE `publishers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `revenue_share` decimal(5,2) DEFAULT 50.00,
  `payment_method` enum('paypal','bank_transfer','crypto','check') DEFAULT 'paypal',
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`)),
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `balance` decimal(15,2) DEFAULT 0.00,
  `total_earned` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `publishers`
--

INSERT INTO `publishers` (`id`, `user_id`, `company_name`, `contact_email`, `phone`, `address`, `website`, `revenue_share`, `payment_method`, `payment_details`, `status`, `balance`, `total_earned`, `created_at`, `updated_at`) VALUES
(1, 4, 'webpublhiser', 'webpublhiser@gmail.com', NULL, NULL, NULL, 50.00, 'paypal', NULL, 'active', 0.00, 0.00, '2025-06-21 20:24:43', '2025-06-21 20:24:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ron_campaigns`
--

CREATE TABLE `ron_campaigns` (
  `id` int(11) NOT NULL,
  `advertiser_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `landing_url` varchar(500) DEFAULT NULL,
  `bid_type` enum('cpm','cpc') DEFAULT 'cpm',
  `bid_amount` decimal(10,4) DEFAULT 0.0000,
  `format` varchar(50) DEFAULT 'banner',
  `cmp_bid` decimal(10,4) DEFAULT 0.0000,
  `cpm_bid` decimal(10,4) DEFAULT 0.0000,
  `cpc_bid` decimal(10,4) DEFAULT 0.0000,
  `daily_budget` decimal(10,2) DEFAULT 0.00,
  `total_budget` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget_spent` decimal(10,2) DEFAULT 0.00,
  `category_id` int(11) DEFAULT NULL,
  `target_countries` text DEFAULT NULL,
  `target_browsers` text DEFAULT NULL,
  `target_devices` text DEFAULT NULL,
  `target_os` text DEFAULT NULL,
  `target_languages` text DEFAULT NULL,
  `target_age` varchar(20) DEFAULT NULL,
  `target_gender` varchar(10) DEFAULT NULL,
  `frequency_cap` int(11) DEFAULT 0,
  `status` enum('active','inactive','paused') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `ron_campaigns`
--

INSERT INTO `ron_campaigns` (`id`, `advertiser_id`, `name`, `description`, `landing_url`, `bid_type`, `bid_amount`, `format`, `cmp_bid`, `cpm_bid`, `cpc_bid`, `daily_budget`, `total_budget`, `start_date`, `end_date`, `budget_spent`, `category_id`, `target_countries`, `target_browsers`, `target_devices`, `target_os`, `target_languages`, `target_age`, `target_gender`, `frequency_cap`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Network RON Campaign', 'Wide-reach RON campaign for maximum exposure across the advertising network', 'https://example.org/network-deals?utm_source=adstart&utm_medium=ron&utm_campaign=network', 'cpm', 0.0100, 'banner', 0.0000, 3.2500, 0.3200, 150.00, 1500.00, NULL, NULL, 0.00, NULL, '', '', '', '', '', '', '', 0, 'active', '2025-06-22 18:33:35', '2025-06-22 18:57:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rtb_campaigns`
--

CREATE TABLE `rtb_campaigns` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `landing_url` varchar(500) DEFAULT NULL,
  `cpm_bid` decimal(10,4) DEFAULT 0.0000,
  `cpc_bid` decimal(10,4) DEFAULT 0.0000,
  `daily_budget` decimal(10,2) DEFAULT 0.00,
  `total_budget` decimal(10,2) DEFAULT 0.00,
  `budget_spent` decimal(10,2) DEFAULT 0.00,
  `category_id` int(11) DEFAULT NULL,
  `target_countries` text DEFAULT NULL,
  `target_devices` text DEFAULT NULL,
  `target_os` text DEFAULT NULL,
  `frequency_cap` int(11) DEFAULT 0,
  `status` enum('active','inactive','paused') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `rtb_campaigns`
--

INSERT INTO `rtb_campaigns` (`id`, `name`, `description`, `landing_url`, `cpm_bid`, `cpc_bid`, `daily_budget`, `total_budget`, `budget_spent`, `category_id`, `target_countries`, `target_devices`, `target_os`, `frequency_cap`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Premium RTB Campaign', 'High-quality RTB campaign with premium ad placements and targeted audience', 'https://example.com/premium-offers?utm_source=adstart&utm_medium=rtb&utm_campaign=premium', 4.5000, 0.4500, 200.00, 2000.00, 0.00, NULL, NULL, NULL, NULL, 0, 'active', '2025-06-22 18:33:35', '2025-06-22 18:33:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rtb_endpoints`
--

CREATE TABLE `rtb_endpoints` (
  `id` int(11) NOT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `format` varchar(50) NOT NULL,
  `website_id` int(11) DEFAULT NULL,
  `endpoint_url` varchar(1000) DEFAULT NULL,
  `endpoint_token` varchar(100) DEFAULT NULL,
  `allowed_sizes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_sizes`)),
  `floor_price_cpm` decimal(10,4) DEFAULT 0.0000,
  `floor_price_cpc` decimal(10,4) DEFAULT 0.0000,
  `timeout_ms` int(11) DEFAULT 2000,
  `max_requests_per_hour` int(11) DEFAULT 10000,
  `status` enum('active','inactive','paused') DEFAULT 'active',
  `requests_today` bigint(20) DEFAULT 0,
  `responses_today` bigint(20) DEFAULT 0,
  `revenue_today` decimal(15,4) DEFAULT 0.0000,
  `last_reset_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rtb_endpoints`
--

INSERT INTO `rtb_endpoints` (`id`, `publisher_id`, `name`, `description`, `format`, `website_id`, `endpoint_url`, `endpoint_token`, `allowed_sizes`, `floor_price_cpm`, `floor_price_cpc`, `timeout_ms`, `max_requests_per_hour`, `status`, `requests_today`, `responses_today`, `revenue_today`, `last_reset_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Banner ', NULL, 'banner', 1, 'https://up.adstart.click/api/rtb/request?token=1bee66e3bee24d77474a12826c681e23&format=banner', NULL, NULL, 0.0000, 0.0000, 2000, 10000, 'active', 0, 0, 0.0000, NULL, '2025-06-21 20:31:15', '2025-06-21 20:31:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` enum('string','integer','float','boolean','json','text') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `category`, `is_public`, `updated_at`) VALUES
(1, 'platform_name', 'AdStart RTB & RON Platform', 'string', 'Name of the advertising platform', 'general', 0, '2025-06-21 20:20:42'),
(2, 'platform_domain', 'up.adstart.click', 'string', 'Main domain of the platform', 'general', 0, '2025-06-21 20:20:42'),
(3, 'default_currency', 'USD', 'string', 'Default currency for transactions', 'financial', 0, '2025-06-21 20:20:42'),
(4, 'default_revenue_share', '50.00', 'float', 'Default revenue share percentage for publishers', 'financial', 0, '2025-06-21 20:20:42'),
(5, 'min_payout_amount', '50.00', 'float', 'Minimum payout amount for publishers', 'financial', 0, '2025-06-21 20:20:42'),
(6, 'rtb_timeout_ms', '2000', 'integer', 'RTB request timeout in milliseconds', 'rtb', 0, '2025-06-21 20:20:42'),
(7, 'max_bid_requests_per_hour', '10000', 'integer', 'Maximum bid requests per hour per endpoint', 'rtb', 0, '2025-06-21 20:20:42'),
(8, 'enable_bot_filtering', '1', 'boolean', 'Enable automatic bot traffic filtering', 'fraud', 0, '2025-06-21 20:20:42'),
(9, 'enable_frequency_capping', '1', 'boolean', 'Enable frequency capping for campaigns', 'campaigns', 0, '2025-06-21 20:20:42'),
(10, 'enable_geo_targeting', '1', 'boolean', 'Enable geographical targeting', 'targeting', 0, '2025-06-21 20:20:42'),
(11, 'enable_device_targeting', '1', 'boolean', 'Enable device targeting', 'targeting', 0, '2025-06-21 20:20:42'),
(12, 'enable_browser_targeting', '1', 'boolean', 'Enable browser targeting', 'targeting', 0, '2025-06-21 20:20:42'),
(13, 'daily_stats_reset_hour', '0', 'integer', 'Hour of day to reset daily statistics (0-23)', 'reporting', 0, '2025-06-21 20:20:42'),
(14, 'enable_real_time_reporting', '1', 'boolean', 'Enable real-time reporting and analytics', 'reporting', 0, '2025-06-21 20:20:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('admin','advertiser','publisher') DEFAULT 'advertiser',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$es9vnaCNoGHeVIWWx8LtmeXaSV3uZyKUkuvG17Hb1SguhyGDjQvr2', 'admin@adstart.click', 'admin', 'active', NULL, '2025-06-21 20:20:42', '2025-06-21 20:23:06'),
(2, 'adsteer', '$2y$10$dHn5mkKDwPqILH1c/6GZ9efKX9eerCjKBpCsY33vZh3ey7ikneCa2', 'support@adsteer.com', 'advertiser', 'active', NULL, '2025-06-21 20:23:55', '2025-06-21 20:23:55'),
(4, 'webpublhiser', '$2y$10$KTkF4CqD8P9bDNH6mSFk4OFNR///ie6ofVag4SaDekzjumxu5Ntei', 'webpublhiser@gmail.com', 'publisher', 'active', NULL, '2025-06-21 20:24:43', '2025-06-21 20:24:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `websites`
--

CREATE TABLE `websites` (
  `id` int(11) NOT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `language` varchar(5) DEFAULT 'en',
  `monthly_pageviews` bigint(20) DEFAULT 0,
  `monthly_visitors` bigint(20) DEFAULT 0,
  `alexa_rank` int(11) DEFAULT NULL,
  `status` enum('active','inactive','pending','rejected') DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `websites`
--

INSERT INTO `websites` (`id`, `publisher_id`, `name`, `url`, `description`, `category_id`, `country`, `language`, `monthly_pageviews`, `monthly_visitors`, `alexa_rank`, `status`, `approval_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'xxtube', 'http://xxtube.com', NULL, 273, NULL, 'en', 0, 0, NULL, 'active', NULL, '2025-06-21 20:25:29', '2025-06-22 17:21:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `zones`
--

CREATE TABLE `zones` (
  `id` int(11) NOT NULL,
  `website_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `size` varchar(20) NOT NULL,
  `ad_format_id` int(11) DEFAULT NULL,
  `zone_code` text DEFAULT NULL,
  `zone_token` varchar(100) DEFAULT NULL,
  `floor_price_cpm` decimal(10,4) DEFAULT 0.0000,
  `floor_price_cpc` decimal(10,4) DEFAULT 0.0000,
  `status` enum('active','inactive','paused') DEFAULT 'active',
  `impressions_today` bigint(20) DEFAULT 0,
  `clicks_today` bigint(20) DEFAULT 0,
  `revenue_today` decimal(15,4) DEFAULT 0.0000,
  `last_reset_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `impressions_total` bigint(20) DEFAULT 0,
  `clicks_total` bigint(20) DEFAULT 0,
  `revenue_total` decimal(15,4) DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `zones`
--

INSERT INTO `zones` (`id`, `website_id`, `name`, `description`, `size`, `ad_format_id`, `zone_code`, `zone_token`, `floor_price_cpm`, `floor_price_cpc`, `status`, `impressions_today`, `clicks_today`, `revenue_today`, `last_reset_date`, `created_at`, `updated_at`, `impressions_total`, `clicks_total`, `revenue_total`) VALUES
(1, 1, 'Banner ', NULL, '300x250', NULL, '<!-- AdStart Zone: Banner  (300x250) -->\n<div id=\"adzone-zone_685844b55310f_b1aeef91\" style=\"width:300px; height:250px; border:1px solid #ddd; background:#f5f5f5; display:flex; align-items:center; justify-content:center; color:#666; font-family:Arial,sans-serif; font-size:14px; position:relative; overflow:hidden;\">\n    <span style=\"color:#999;\">Loading ad...</span>\n</div>\n<script>\n(function() {\n    const container = document.getElementById(\'adzone-zone_685844b55310f_b1aeef91\');\n    if (!container) {\n        console.error(\'AdZone container not found: adzone-zone_685844b55310f_b1aeef91\');\n        return;\n    }\n    \n    const domain = \'https://up.adstart.click\';\n    const zoneToken = \'zone_685844b55310f_b1aeef91\';\n    const size = \'300x250\';\n    \n    console.log(\'AdZone Loading:\', {domain, zoneToken, size});\n    \n    // Request ad content\n    fetch(domain + \'/api/rtb/request.php?token=\' + zoneToken + \'&format=banner&size=\' + size + \'&r=\' + Math.random())\n        .then(response => {\n            console.log(\'AdZone Response Status:\', response.status);\n            return response.json();\n        })\n        .then(data => {\n            console.log(\'AdZone Response Data:\', data);\n            \n            if (data.success && data.content) {\n                container.innerHTML = data.content;\n                container.style.border = \'none\';\n                container.style.background = \'transparent\';\n                \n                // Track impression\n                fetch(domain + \'/api/track/impression.php\', {\n                    method: \'POST\',\n                    headers: {\'Content-Type\': \'application/x-www-form-urlencoded\'},\n                    body: \'zone_id=\' + encodeURIComponent(zoneToken) + \n                          \'&campaign_id=\' + encodeURIComponent(data.campaign_id || \'\') + \n                          \'&type=\' + encodeURIComponent(data.type || \'unknown\') +\n                          \'&timestamp=\' + Date.now()\n                }).catch(e => console.warn(\'Impression tracking failed:\', e));\n                \n                // Add click tracking to all links\n                setTimeout(() => {\n                    const links = container.querySelectorAll(\'a, [onclick], button\');\n                    links.forEach(link => {\n                        link.addEventListener(\'click\', function(e) {\n                            fetch(domain + \'/api/track/click.php\', {\n                                method: \'POST\',\n                                headers: {\'Content-Type\': \'application/x-www-form-urlencoded\'},\n                                body: \'zone_id=\' + encodeURIComponent(zoneToken) + \n                                      \'&campaign_id=\' + encodeURIComponent(data.campaign_id || \'\') + \n                                      \'&type=\' + encodeURIComponent(data.type || \'unknown\') +\n                                      \'&timestamp=\' + Date.now()\n                            }).catch(e => console.warn(\'Click tracking failed:\', e));\n                        });\n                    });\n                }, 100);\n            } else {\n                container.innerHTML = \'<div style=\"display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-size:12px;text-align:center;\">No ads available<br><small>Zone: zone_685844b55310f_b1aeef91</small></div>\';\n            }\n        })\n        .catch(error => {\n            console.error(\'AdZone error:\', error);\n            container.innerHTML = \'<div style=\"display:flex;align-items:center;justify-content:center;height:100%;color:#cc0000;font-size:11px;text-align:center;\">Ad loading failed<br><small>Zone: zone_685844b55310f_b1aeef91</small><br><small>\' + error.message + \'</small></div>\';\n        });\n})();\n</script>\n<!-- End AdStart Zone -->', 'zone_685844b55310f_b1aeef91', 0.0000, 0.0000, 'active', 6, 0, 0.0000, NULL, '2025-06-22 16:59:12', '2025-06-22 19:02:09', 6, 0, 0.0000);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `advertisers`
--
ALTER TABLE `advertisers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_name` (`name`);

--
-- Indeks untuk tabel `ad_formats`
--
ALTER TABLE `ad_formats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `blacklists`
--
ALTER TABLE `blacklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `idx_type_value` (`type`,`value`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `clicks`
--
ALTER TABLE `clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `impression_id` (`impression_id`),
  ADD KEY `idx_zone_campaign` (`zone_id`,`campaign_id`),
  ADD KEY `idx_campaign_type` (`campaign_id`,`campaign_type`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_date_hour` (`date`,`hour`),
  ADD KEY `idx_publisher` (`publisher_id`),
  ADD KEY `idx_advertiser` (`advertiser_id`),
  ADD KEY `idx_country` (`country`),
  ADD KEY `idx_device` (`device`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_clicks_date_campaign` (`date`,`campaign_id`,`campaign_type`);

--
-- Indeks untuk tabel `conversions`
--
ALTER TABLE `conversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `click_id` (`click_id`),
  ADD KEY `impression_id` (`impression_id`),
  ADD KEY `idx_campaign` (`campaign_id`,`campaign_type`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_external` (`external_id`),
  ADD KEY `idx_conversions_date_campaign` (`date`,`campaign_id`,`campaign_type`);

--
-- Indeks untuk tabel `creatives`
--
ALTER TABLE `creatives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campaign` (`campaign_id`,`campaign_type`),
  ADD KEY `idx_size_status` (`width`,`height`,`status`),
  ADD KEY `idx_campaign_type_size` (`campaign_type`,`width`,`height`,`status`);

--
-- Indeks untuk tabel `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `advertiser_id` (`advertiser_id`),
  ADD KEY `publisher_id` (`publisher_id`),
  ADD KEY `idx_type` (`transaction_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reference` (`reference_id`,`reference_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indeks untuk tabel `impressions`
--
ALTER TABLE `impressions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_zone_campaign` (`zone_id`,`campaign_id`),
  ADD KEY `idx_campaign_type` (`campaign_id`,`campaign_type`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_date_hour` (`date`,`hour`),
  ADD KEY `idx_publisher` (`publisher_id`),
  ADD KEY `idx_advertiser` (`advertiser_id`),
  ADD KEY `idx_country` (`country`),
  ADD KEY `idx_device` (`device`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_impressions_date_campaign` (`date`,`campaign_id`,`campaign_type`);

--
-- Indeks untuk tabel `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `ron_campaigns`
--
ALTER TABLE `ron_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_budget` (`status`,`total_budget`,`budget_spent`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indeks untuk tabel `rtb_campaigns`
--
ALTER TABLE `rtb_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_budget` (`status`,`total_budget`,`budget_spent`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indeks untuk tabel `rtb_endpoints`
--
ALTER TABLE `rtb_endpoints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `endpoint_token` (`endpoint_token`),
  ADD KEY `publisher_id` (`publisher_id`),
  ADD KEY `website_id` (`website_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_category` (`category`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `websites`
--
ALTER TABLE `websites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publisher_id` (`publisher_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_country` (`country`);

--
-- Indeks untuk tabel `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `zone_token` (`zone_token`),
  ADD KEY `website_id` (`website_id`),
  ADD KEY `ad_format_id` (`ad_format_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_size` (`size`),
  ADD KEY `idx_zone_token` (`zone_token`),
  ADD KEY `idx_zone_status` (`status`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `advertisers`
--
ALTER TABLE `advertisers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `ad_formats`
--
ALTER TABLE `ad_formats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `blacklists`
--
ALTER TABLE `blacklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=287;

--
-- AUTO_INCREMENT untuk tabel `clicks`
--
ALTER TABLE `clicks`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `conversions`
--
ALTER TABLE `conversions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `creatives`
--
ALTER TABLE `creatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `financial_transactions`
--
ALTER TABLE `financial_transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `impressions`
--
ALTER TABLE `impressions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `ron_campaigns`
--
ALTER TABLE `ron_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rtb_campaigns`
--
ALTER TABLE `rtb_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rtb_endpoints`
--
ALTER TABLE `rtb_endpoints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `websites`
--
ALTER TABLE `websites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `zones`
--
ALTER TABLE `zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- --------------------------------------------------------

--
-- Struktur untuk view `campaign_stats_daily`
--
DROP TABLE IF EXISTS `campaign_stats_daily`;

CREATE ALGORITHM=UNDEFINED DEFINER=`user_up`@`localhost` SQL SECURITY DEFINER VIEW `campaign_stats_daily`  AS SELECT cast(`i`.`created_at` as date) AS `date`, `i`.`campaign_id` AS `campaign_id`, `i`.`campaign_type` AS `campaign_type`, `i`.`publisher_id` AS `publisher_id`, `i`.`advertiser_id` AS `advertiser_id`, count(`i`.`id`) AS `impressions`, count(`c`.`id`) AS `clicks`, count(`conv`.`id`) AS `conversions`, coalesce(sum(`i`.`bid_amount`),0) AS `revenue`, coalesce(sum(`i`.`publisher_revenue`),0) AS `publisher_revenue`, coalesce(sum(`i`.`platform_revenue`),0) AS `platform_revenue`, CASE WHEN count(`i`.`id`) > 0 THEN round(count(`c`.`id`) / count(`i`.`id`) * 100,2) ELSE 0 END AS `ctr`, CASE WHEN count(`c`.`id`) > 0 THEN round(count(`conv`.`id`) / count(`c`.`id`) * 100,2) ELSE 0 END AS `conversion_rate` FROM ((`impressions` `i` left join `clicks` `c` on(`i`.`id` = `c`.`impression_id`)) left join `conversions` `conv` on(`c`.`id` = `conv`.`click_id`)) GROUP BY cast(`i`.`created_at` as date), `i`.`campaign_id`, `i`.`campaign_type`, `i`.`publisher_id`, `i`.`advertiser_id` ;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `blacklists`
--
ALTER TABLE `blacklists`
  ADD CONSTRAINT `blacklists_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `clicks`
--
ALTER TABLE `clicks`
  ADD CONSTRAINT `clicks_ibfk_1` FOREIGN KEY (`impression_id`) REFERENCES `impressions` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `conversions`
--
ALTER TABLE `conversions`
  ADD CONSTRAINT `conversions_ibfk_1` FOREIGN KEY (`click_id`) REFERENCES `clicks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversions_ibfk_2` FOREIGN KEY (`impression_id`) REFERENCES `impressions` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `financial_transactions`
--
ALTER TABLE `financial_transactions`
  ADD CONSTRAINT `financial_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_transactions_ibfk_2` FOREIGN KEY (`advertiser_id`) REFERENCES `advertisers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_transactions_ibfk_3` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `publishers`
--
ALTER TABLE `publishers`
  ADD CONSTRAINT `publishers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rtb_endpoints`
--
ALTER TABLE `rtb_endpoints`
  ADD CONSTRAINT `rtb_endpoints_ibfk_1` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rtb_endpoints_ibfk_2` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `websites`
--
ALTER TABLE `websites`
  ADD CONSTRAINT `websites_ibfk_1` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `websites_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `zones`
--
ALTER TABLE `zones`
  ADD CONSTRAINT `zones_ibfk_1` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zones_ibfk_2` FOREIGN KEY (`ad_format_id`) REFERENCES `ad_formats` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
