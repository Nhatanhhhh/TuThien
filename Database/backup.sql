-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: tuthien
-- ------------------------------------------------------
-- Server version	9.0.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `trip_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `refund_status` enum('pending','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `refunded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `trip_id` (`trip_id`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_chk_1` CHECK ((`amount` >= 10000))
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (36,8,7,100000.00,'cancelled','completed','2025-05-18 04:16:26','2025-05-18 08:22:57'),(37,8,7,500000.00,'cancelled','completed','2025-05-18 05:06:59','2025-05-18 08:22:57'),(38,8,6,200000.00,'cancelled','completed','2025-05-18 05:07:35','2025-05-18 07:04:33'),(39,8,7,500000.00,'cancelled','completed','2025-05-18 07:16:36','2025-05-18 08:22:57'),(40,8,8,200000.00,'cancelled','completed','2025-05-18 08:25:26','2025-05-18 08:27:34');
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `trip_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('deposit','donation','refund','deduction') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('vnpay','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vnpay',
  `order_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `trip_id` (`trip_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transactions_chk_1` CHECK ((`payment_method` in (_utf8mb4'vnpay',_utf8mb4'system')))
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (136,8,NULL,2000000.00,'deposit','vnpay','DEPOSIT_1747541663_4199',NULL,'completed','2025-05-18 04:14:23','2025-05-18 04:14:45'),(137,8,NULL,500000.00,'deposit','vnpay','DEPOSIT_1747541733_7111',NULL,'completed','2025-05-18 04:15:33','2025-05-18 04:15:51'),(138,8,7,100000.00,'refund','system',NULL,'Hoàn tiền do chuyến đi bị hủy','completed','2025-05-18 04:17:14','2025-05-18 04:17:14'),(139,8,7,500000.00,'refund','system',NULL,'Hoàn tiền do chuyến đi bị hủy','completed','2025-05-18 07:01:34','2025-05-18 07:01:34'),(140,8,6,200000.00,'refund','system',NULL,'Hoàn tiền do chuyến đi bị hủy','completed','2025-05-18 07:02:00','2025-05-18 07:02:00'),(141,8,7,500000.00,'refund','system',NULL,'Hoàn tiền do chuyến đi bị hủy','completed','2025-05-18 08:22:18','2025-05-18 08:22:18'),(142,8,8,200000.00,'refund','system',NULL,'Hoàn tiền do chuyến đi bị hủy','completed','2025-05-18 08:27:34','2025-05-18 08:27:34');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_participants`
--

DROP TABLE IF EXISTS `trip_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trip_participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `user_id` int NOT NULL,
  `trip_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  KEY `trip_id` (`trip_id`),
  CONSTRAINT `trip_participants_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trip_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trip_participants_ibfk_3` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_participants`
--

LOCK TABLES `trip_participants` WRITE;
/*!40000 ALTER TABLE `trip_participants` DISABLE KEYS */;
INSERT INTO `trip_participants` VALUES (24,40,8,8,'Đặng Xuân Toàn','nguyennhatanhnnacm@gmail.com','0813052062',NULL,'2025-05-18 08:25:26');
/*!40000 ALTER TABLE `trip_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trips`
--

DROP TABLE IF EXISTS `trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_cancelled` tinyint(1) NOT NULL DEFAULT '0',
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `refund_status` enum('pending','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trips`
--

LOCK TABLES `trips` WRITE;
/*!40000 ALTER TABLE `trips` DISABLE KEYS */;
INSERT INTO `trips` VALUES (5,'Duyệt binh (50 năm hoàn toàn giải phóng đất nước)','Kỷ niệm 50 năm thống nhất đất nước','/TuThien/images/68295649368a1_vi-sao-hoc-sinh-can-tham-gia-cac-hoat-dong-sinh-hoat-cong-dong-511068.jpg','2025-05-17','08:00:00','Hà Nội','2025-05-18 03:38:49',0,0,0,NULL,NULL,NULL),(6,'Thăm em nhỏ vùng cao	','Hỗ trợ trẻ em vùng cao	','/TuThien/images/682956b034b4c_682469861baba_batch_trai-nghiem-nen-thu-o-tay-nguyen-cong-chieng.jpg1.jpg','2025-06-01','07:00:00','Lào Cai	','2025-05-18 03:40:32',0,1,0,NULL,NULL,NULL),(7,'Hành trình Rực Rỡ','Khám phá văn hóa miền Trung','https://image.baophapluat.vn/w840/Uploaded/2025/athlrainaghat/2023_05_21/den-vau-trong-san-pham-am-nhac-moi-ra-mat-anh-nhan-vat-4409.jpeg','2025-07-15','09:00:00','Huế','2025-05-18 03:42:38',0,1,0,NULL,NULL,NULL),(8,'Lành tình thương','Hỗ trợ người khó khăn','https://media.thuonghieucongluan.vn/uploads/2022/12/12/318131150-1316613772213013-7406241561577940955-n-1670851964.jpg','2025-05-20','08:30:00','TP. Hồ Chí Minh','2025-05-18 03:45:13',0,0,1,'Do vấn đề thời tiết','2025-05-18 08:27:34','completed');
/*!40000 ALTER TABLE `trips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_funds`
--

DROP TABLE IF EXISTS `user_funds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_funds` (
  `user_id` int NOT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `user_funds_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_funds`
--

LOCK TABLES `user_funds` WRITE;
/*!40000 ALTER TABLE `user_funds` DISABLE KEYS */;
INSERT INTO `user_funds` VALUES (8,2500000.00,'2025-05-18 08:27:34');
/*!40000 ALTER TABLE `user_funds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthdate` date NOT NULL,
  `role` enum('admin','user','staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Toan2','Đặng Xuân Toàn','$2y$10$b6PBKezETv/BOni8RCefseDyK/b353/oksBFqg2rtQ5EmQECN/E7O','renboy122333444@gmail.com','0944169148','male','2004-11-23','admin','2025-05-05 21:46:23',0,NULL),(2,'Toankudien','Đặng Xuân Toàn','$2y$10$b6PBKezETv/BOni8RCefseDyK/b353/oksBFqg2rtQ5EmQECN/E7O','renboy122333@gmail.com','0944444444','male','2007-05-09','user','2025-05-06 15:13:13',0,NULL),(3,'Toan','Đặng Xuân Toàn','$2y$10$b6PBKezETv/BOni8RCefseDyK/b353/oksBFqg2rtQ5EmQECN/E7O','Menboy122333444@gmail.com','0913722716','male','2004-07-16','user','2025-05-07 05:33:30',0,NULL),(4,'huy','Huy','$2y$10$b6PBKezETv/BOni8RCefseDyK/b353/oksBFqg2rtQ5EmQECN/E7O','biluu174@gmail.com','0764726046','male','2000-07-18','user','2025-05-09 13:00:30',0,NULL),(6,'huy300','Huy','$2y$10$b6PBKezETv/BOni8RCefseDyK/b353/oksBFqg2rtQ5EmQECN/E7O','biluu1744@gmail.com','0764726047','male','2000-05-04','user','2025-05-11 03:59:02',0,NULL),(7,'admin','Admin','$2y$10$Vo0NKPr8xcaXEIR.V0CvE.d7jc1keSXOJg1QakJG5GblD027Y9e3u','admin@hopelink.org','0123456789','male','1990-01-01','admin','2025-05-14 09:04:46',0,NULL),(8,'nhatanh8i','Nguyễn Nhật Anh','$2y$10$1d7Hl76Rm.idjNKoUxZGRuTJ79HNSroKWbppoGuiY5oyBEgHu/KaW','nguyennhatanhnnacm@gmail.com','0813052062','male','2004-07-09','user','2025-05-14 09:05:06',0,NULL),(9,'staff',NULL,'$2y$10$OD35IvIYRAO3y6vaK.F09uwNh.q9oKHayhIlF6eESN2p2BoKVp6am','staff1@gmail.com','012345678','male','2007-05-17','staff','2025-05-17 15:25:48',0,NULL),(10,'staff2','Nguyễn Nhật Anh','$2y$10$fTPnMfCDQF6faigsQJKI0OzNOxF61ddJXbsj3oxTesxEPmI2yx5Yy','staff2@gmail.com','012345677','male','2007-05-17','staff','2025-05-17 16:38:16',0,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-18 15:28:55
