-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 26, 2025 at 11:57 PM
-- Server version: 8.0.30
-- PHP Version: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test-intern`
--

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_products`
--

CREATE TABLE `master_products` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `satuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `master_products`
--

INSERT INTO `master_products` (`id`, `kode`, `nama`, `satuan`, `deskripsi`, `created_at`, `updated_at`) VALUES
('a0338080-9c9f-4611-a555-d8573e8178bc', 'kode1', 'ajksdjlaksjd', 'pcs', 'jkasdjlkajsdkajsld', '2025-10-25 15:16:36', '2025-10-25 15:16:36'),
('a03570a7-775f-4fbc-a05e-0bae82838213', 'kode2', 'asdhkjasdhjkas', 'pcs', 'hajsdhjkashdjkasd', '2025-10-26 14:23:57', '2025-10-26 14:23:57');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_10_25_025204_create_master_products_table', 1),
(6, '2025_10_25_025209_create_production_plans_table', 1),
(7, '2025_10_25_025215_create_production_orders_table', 1),
(8, '2025_10_25_025221_create_production_order_histories_table', 1),
(9, '2025_10_25_025225_create_production_reports_table', 1),
(10, '2025_10_25_025229_create_production_rejects_table', 1),
(11, '2025_10_25_062731_create_production_plan_histories_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(21, 'App\\Models\\User', '145edc02-e951-44fd-8600-4dd78468699c', 'api', '73abc331a11978540484490dc57ce711dab649da5cd98fc36ca9771a4f55d304', '[\"*\"]', '2025-10-26 16:39:27', NULL, '2025-10-26 16:32:51', '2025-10-26 16:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `production_orders`
--

CREATE TABLE `production_orders` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_order` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rencana_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produk_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_jumlah` int NOT NULL,
  `jumlah_aktual` int DEFAULT NULL,
  `jumlah_reject` int DEFAULT NULL,
  `status` enum('menunggu','dalam_proses','selesai','ditutup') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `mulai_pada` timestamp NULL DEFAULT NULL,
  `selesai_pada` timestamp NULL DEFAULT NULL,
  `dikerjakan_oleh` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_orders`
--

INSERT INTO `production_orders` (`id`, `nomor_order`, `rencana_id`, `produk_id`, `target_jumlah`, `jumlah_aktual`, `jumlah_reject`, `status`, `mulai_pada`, `selesai_pada`, `dikerjakan_oleh`, `created_at`, `updated_at`) VALUES
('a03380e2-5e15-4755-80f1-080a63d27a23', 'ORD-0001', 'a0338096-37c4-4337-9fe3-affe50710dbf', 'a0338080-9c9f-4611-a555-d8573e8178bc', 1000, 1000, 0, 'selesai', '2025-10-25 15:18:12', '2025-10-25 15:28:42', '145edc02-e951-44fd-8600-4dd78468699c', '2025-10-25 15:17:40', '2025-10-25 15:28:42'),
('a0348ac2-b000-43fc-8bcc-889dc6ee0ee9', 'ORD-0002', 'a0338b59-4221-49d6-85b8-beeb0bec9443', 'a0338080-9c9f-4611-a555-d8573e8178bc', 500, NULL, NULL, 'dalam_proses', '2025-10-26 03:42:40', NULL, '145edc02-e951-44fd-8600-4dd78468699c', '2025-10-26 03:41:07', '2025-10-26 03:42:40');

-- --------------------------------------------------------

--
-- Table structure for table `production_order_histories`
--

CREATE TABLE `production_order_histories` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_sebelumnya` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_baru` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diubah_oleh` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `diubah_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_order_histories`
--

INSERT INTO `production_order_histories` (`id`, `order_id`, `status_sebelumnya`, `status_baru`, `diubah_oleh`, `keterangan`, `diubah_pada`, `created_at`, `updated_at`) VALUES
('a03380e2-6190-4d65-8e37-ac6b3f57d53b', 'a03380e2-5e15-4755-80f1-080a63d27a23', NULL, 'menunggu', '145edc02-e951-44fd-8600-4dd78468699c', 'Order produksi dibuat dari rencana: RP-20251025-0001', '2025-10-25 15:17:40', '2025-10-25 15:17:40', '2025-10-25 15:17:40'),
('a0338112-9dd4-4007-a4a1-2327c2fd1f22', 'a03380e2-5e15-4755-80f1-080a63d27a23', 'menunggu', 'dalam_proses', '145edc02-e951-44fd-8600-4dd78468699c', 'Memulai proses produksi', '2025-10-25 15:18:12', '2025-10-25 15:18:12', '2025-10-25 15:18:12'),
('a03384d3-32e3-493f-b355-0c8bb77c7f5c', 'a03380e2-5e15-4755-80f1-080a63d27a23', 'dalam_proses', 'selesai', '145edc02-e951-44fd-8600-4dd78468699c', 'Selesai proses produksi', '2025-10-25 15:28:42', '2025-10-25 15:28:42', '2025-10-25 15:28:42'),
('a0348ac2-b26d-4473-97dc-ade61d546f3f', 'a0348ac2-b000-43fc-8bcc-889dc6ee0ee9', NULL, 'menunggu', '145edc02-e951-44fd-8600-4dd78468699c', 'Order produksi dibuat dari rencana: RP-20251025-0002', '2025-10-26 03:41:07', '2025-10-26 03:41:07', '2025-10-26 03:41:07'),
('a0348b4f-c4ef-48c5-99d0-5f82c613d0ba', 'a0348ac2-b000-43fc-8bcc-889dc6ee0ee9', 'menunggu', 'dalam_proses', '145edc02-e951-44fd-8600-4dd78468699c', 'Memulai proses produksi', '2025-10-26 03:42:40', '2025-10-26 03:42:40', '2025-10-26 03:42:40');

-- --------------------------------------------------------

--
-- Table structure for table `production_plans`
--

CREATE TABLE `production_plans` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_rencana` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produk_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` int NOT NULL,
  `dibuat_oleh` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disetujui_oleh` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','menunggu_persetujuan','disetujui','ditolak','menjadi_order') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `batas_selesai` date DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `disetujui_pada` timestamp NULL DEFAULT NULL,
  `ditolak_pada` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_plans`
--

INSERT INTO `production_plans` (`id`, `nomor_rencana`, `produk_id`, `jumlah`, `dibuat_oleh`, `disetujui_oleh`, `status`, `batas_selesai`, `catatan`, `disetujui_pada`, `ditolak_pada`, `created_at`, `updated_at`) VALUES
('a0338096-37c4-4337-9fe3-affe50710dbf', 'RP-20251025-0001', 'a0338080-9c9f-4611-a555-d8573e8178bc', 1000, '30e7783c-5631-4305-8202-62b3bfa07307', '145edc02-e951-44fd-8600-4dd78468699c', 'menjadi_order', '2025-11-01', NULL, '2025-10-25 15:17:40', NULL, '2025-10-25 15:16:51', '2025-10-25 15:17:40'),
('a0338b59-4221-49d6-85b8-beeb0bec9443', 'RP-20251025-0002', 'a0338080-9c9f-4611-a555-d8573e8178bc', 500, 'a02da3c6-f2d7-467e-8b45-428c72a9bed5', '145edc02-e951-44fd-8600-4dd78468699c', 'menjadi_order', '2025-11-02', NULL, '2025-10-26 03:41:07', NULL, '2025-10-25 15:46:56', '2025-10-26 03:41:07'),
('a035739d-6ad9-40a9-afda-16cdd21ed8cf', 'RP-20251026-0001', 'a03570a7-775f-4fbc-a05e-0bae82838213', 1000, '30e7783c-5631-4305-8202-62b3bfa07307', NULL, 'menunggu_persetujuan', '2025-10-28', 'asdasdasdasd', NULL, NULL, '2025-10-26 14:32:14', '2025-10-26 14:32:18');

-- --------------------------------------------------------

--
-- Table structure for table `production_plan_histories`
--

CREATE TABLE `production_plan_histories` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rencana_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aksi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_sebelum` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_baru` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `waktu_aksi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_plan_histories`
--

INSERT INTO `production_plan_histories` (`id`, `rencana_id`, `user_id`, `aksi`, `status_sebelum`, `status_baru`, `keterangan`, `waktu_aksi`, `created_at`, `updated_at`) VALUES
('a0338096-3a0e-4b85-a5ce-f2843d1a85f0', 'a0338096-37c4-4337-9fe3-affe50710dbf', '30e7783c-5631-4305-8202-62b3bfa07307', 'dibuat', 'baru', 'draft', 'Rencana produksi dibuat oleh PPIC', '2025-10-25 15:16:51', '2025-10-25 15:16:51', '2025-10-25 15:16:51'),
('a03380c1-6a1f-45ae-b0a2-406bb7a6837c', 'a0338096-37c4-4337-9fe3-affe50710dbf', '30e7783c-5631-4305-8202-62b3bfa07307', 'diajukan', 'draft', 'menunggu_persetujuan', 'Rencana diajukan untuk persetujuan manager produksi', '2025-10-25 15:17:19', '2025-10-25 15:17:19', '2025-10-25 15:17:19'),
('a03380e2-59e5-4c79-8926-8a250b66374e', 'a0338096-37c4-4337-9fe3-affe50710dbf', '145edc02-e951-44fd-8600-4dd78468699c', 'disetujui', 'menunggu_persetujuan', 'disetujui', 'Rencana disetujui oleh manager produksi', '2025-10-25 15:17:40', '2025-10-25 15:17:40', '2025-10-25 15:17:40'),
('a03380e2-6070-408f-8480-2b1b7d95ff1d', 'a0338096-37c4-4337-9fe3-affe50710dbf', '145edc02-e951-44fd-8600-4dd78468699c', 'diproses', 'disetujui', 'menjadi_order', 'Rencana diproses menjadi order produksi', '2025-10-25 15:17:40', '2025-10-25 15:17:40', '2025-10-25 15:17:40'),
('a0338b59-498c-43ec-8a05-b84f55a83278', 'a0338b59-4221-49d6-85b8-beeb0bec9443', 'a02da3c6-f2d7-467e-8b45-428c72a9bed5', 'dibuat', 'baru', 'draft', 'Rencana produksi dibuat oleh PPIC', '2025-10-25 15:46:56', '2025-10-25 15:46:56', '2025-10-25 15:46:56'),
('a0338b5f-944a-4399-a91a-fc727ee1984b', 'a0338b59-4221-49d6-85b8-beeb0bec9443', 'a02da3c6-f2d7-467e-8b45-428c72a9bed5', 'diajukan', 'draft', 'menunggu_persetujuan', 'Rencana diajukan untuk persetujuan manager produksi', '2025-10-25 15:47:00', '2025-10-25 15:47:00', '2025-10-25 15:47:00'),
('a0348ac2-a7ec-4274-bda1-acda4e8c42d7', 'a0338b59-4221-49d6-85b8-beeb0bec9443', '145edc02-e951-44fd-8600-4dd78468699c', 'disetujui', 'menunggu_persetujuan', 'disetujui', 'Rencana disetujui oleh manager produksi', '2025-10-26 03:41:07', '2025-10-26 03:41:07', '2025-10-26 03:41:07'),
('a0348ac2-b1a1-4b17-9479-16acb37d0449', 'a0338b59-4221-49d6-85b8-beeb0bec9443', '145edc02-e951-44fd-8600-4dd78468699c', 'diproses', 'disetujui', 'menjadi_order', 'Rencana diproses menjadi order produksi', '2025-10-26 03:41:07', '2025-10-26 03:41:07', '2025-10-26 03:41:07'),
('a035739d-6f63-4278-bda6-b664d9d9a986', 'a035739d-6ad9-40a9-afda-16cdd21ed8cf', '30e7783c-5631-4305-8202-62b3bfa07307', 'dibuat', 'baru', 'draft', 'Rencana produksi dibuat oleh PPIC', '2025-10-26 14:32:14', '2025-10-26 14:32:14', '2025-10-26 14:32:14'),
('a03573a4-0cd7-4c2b-af95-8d357e0f02c0', 'a035739d-6ad9-40a9-afda-16cdd21ed8cf', '30e7783c-5631-4305-8202-62b3bfa07307', 'diajukan', 'draft', 'menunggu_persetujuan', 'Rencana diajukan untuk persetujuan manager produksi', '2025-10-26 14:32:18', '2025-10-26 14:32:18', '2025-10-26 14:32:18');

-- --------------------------------------------------------

--
-- Table structure for table `production_reports`
--

CREATE TABLE `production_reports` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_laporan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periode_awal` date NOT NULL,
  `periode_akhir` date NOT NULL,
  `dibuat_oleh` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_reports`
--

INSERT INTO `production_reports` (`id`, `nomor_laporan`, `periode_awal`, `periode_akhir`, `dibuat_oleh`, `catatan`, `created_at`, `updated_at`) VALUES
('a0349f90-8bff-4ef8-9d9e-7ca9c83f33c3', 'LAP-PROD-20251026-0001', '2025-10-17', '2025-10-26', '145edc02-e951-44fd-8600-4dd78468699c', NULL, '2025-10-26 04:39:17', '2025-10-26 04:39:17'),
('a034a249-9821-4dc7-846f-668863801824', 'LAP-PROD-20251026-0002', '2025-10-17', '2025-10-27', '145edc02-e951-44fd-8600-4dd78468699c', NULL, '2025-10-26 04:46:54', '2025-10-26 04:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('managerppic','managerpproduksi','staffppic','staffproduksi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staffproduksi',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `department` enum('ppic','produksi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'produksi',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `status`, `department`, `remember_token`, `created_at`, `updated_at`) VALUES
('145edc02-e951-44fd-8600-4dd78468699c', 'Manager Produksi', 'manager.produksi@example.com', NULL, '$2y$10$.PXCURgLFQ0Y7Aul9tyDSOgSUtMZBMFvyuoli7wVHyHzk/aHE7PnS', 'managerpproduksi', 'active', 'produksi', NULL, '2025-10-25 15:15:47', '2025-10-25 15:15:47'),
('30e7783c-5631-4305-8202-62b3bfa07307', 'Manager PPIC', 'manager.ppic@example.com', NULL, '$2y$10$Rzzg6rs9TPuMVTb9AmRKhOjnWG.E1jkpIt.Q347/gh93IiJrg0u2u', 'managerppic', 'active', 'ppic', NULL, '2025-10-25 15:15:47', '2025-10-25 15:15:47'),
('a02da3c6-f2d7-467e-8b45-428c72a9bed5', 'Staff PPIC', 'staff.ppic@example.com', NULL, '$2y$10$tz7.d6A6A6rSf5CZX3M.muK4in9xMplrqhM57xm75x9NwhFaI.p9C', 'staffppic', 'active', 'ppic', NULL, '2025-10-25 15:15:47', '2025-10-25 15:15:47'),
('b1e5cd39-9a1a-450f-bbf1-389a00bb0082', 'Staff Produksi', 'staff.produksi@example.com', NULL, '$2y$10$87ErpJ5WovJBbP9AAiKHFeFH3n/ufJbJ0QeShlJVH/0DAChqHH1OC', 'staffproduksi', 'active', 'produksi', NULL, '2025-10-25 15:15:47', '2025-10-25 15:15:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `master_products`
--
ALTER TABLE `master_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `master_products_kode_unique` (`kode`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `production_orders`
--
ALTER TABLE `production_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_orders_nomor_order_unique` (`nomor_order`),
  ADD KEY `production_orders_rencana_id_foreign` (`rencana_id`),
  ADD KEY `production_orders_produk_id_foreign` (`produk_id`),
  ADD KEY `production_orders_dikerjakan_oleh_foreign` (`dikerjakan_oleh`);

--
-- Indexes for table `production_order_histories`
--
ALTER TABLE `production_order_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `production_order_histories_order_id_foreign` (`order_id`),
  ADD KEY `production_order_histories_diubah_oleh_foreign` (`diubah_oleh`);

--
-- Indexes for table `production_plans`
--
ALTER TABLE `production_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_plans_nomor_rencana_unique` (`nomor_rencana`),
  ADD KEY `production_plans_produk_id_foreign` (`produk_id`),
  ADD KEY `production_plans_dibuat_oleh_foreign` (`dibuat_oleh`),
  ADD KEY `production_plans_disetujui_oleh_foreign` (`disetujui_oleh`);

--
-- Indexes for table `production_plan_histories`
--
ALTER TABLE `production_plan_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `production_plan_histories_rencana_id_foreign` (`rencana_id`),
  ADD KEY `production_plan_histories_user_id_foreign` (`user_id`);

--
-- Indexes for table `production_reports`
--
ALTER TABLE `production_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_reports_nomor_laporan_unique` (`nomor_laporan`),
  ADD KEY `production_reports_dibuat_oleh_foreign` (`dibuat_oleh`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `production_orders`
--
ALTER TABLE `production_orders`
  ADD CONSTRAINT `production_orders_dikerjakan_oleh_foreign` FOREIGN KEY (`dikerjakan_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_orders_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `master_products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_orders_rencana_id_foreign` FOREIGN KEY (`rencana_id`) REFERENCES `production_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production_order_histories`
--
ALTER TABLE `production_order_histories`
  ADD CONSTRAINT `production_order_histories_diubah_oleh_foreign` FOREIGN KEY (`diubah_oleh`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_order_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production_plans`
--
ALTER TABLE `production_plans`
  ADD CONSTRAINT `production_plans_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_plans_disetujui_oleh_foreign` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_plans_produk_id_foreign` FOREIGN KEY (`produk_id`) REFERENCES `master_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production_plan_histories`
--
ALTER TABLE `production_plan_histories`
  ADD CONSTRAINT `production_plan_histories_rencana_id_foreign` FOREIGN KEY (`rencana_id`) REFERENCES `production_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_plan_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production_reports`
--
ALTER TABLE `production_reports`
  ADD CONSTRAINT `production_reports_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
