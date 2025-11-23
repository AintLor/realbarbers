-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: realbarbers-db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `barber_availability`
--

DROP TABLE IF EXISTS `barber_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barber_availability` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `barber_id` bigint(20) unsigned NOT NULL,
  `weekday` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `time_slot` time NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_barber_slot` (`barber_id`,`weekday`,`time_slot`),
  CONSTRAINT `barber_availability_ibfk_1` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=186 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barber_availability`
--

LOCK TABLES `barber_availability` WRITE;
/*!40000 ALTER TABLE `barber_availability` DISABLE KEYS */;
INSERT INTO `barber_availability` VALUES (1,1,'Sunday','09:00:00',1),(2,1,'Sunday','10:00:00',1),(3,1,'Sunday','11:00:00',1),(4,1,'Sunday','13:00:00',1),(5,1,'Sunday','14:00:00',1),(6,1,'Sunday','15:00:00',1),(7,1,'Sunday','16:00:00',1),(8,1,'Monday','11:00:00',1),(9,1,'Monday','14:00:00',1),(10,1,'Monday','15:00:00',1),(11,1,'Monday','16:00:00',1),(12,1,'Monday','17:00:00',1),(13,1,'Tuesday','09:00:00',1),(14,1,'Tuesday','10:00:00',1),(15,1,'Tuesday','11:00:00',1),(16,1,'Tuesday','13:00:00',1),(17,1,'Tuesday','14:00:00',1),(18,1,'Tuesday','15:00:00',1),(19,1,'Tuesday','16:00:00',1),(20,1,'Thursday','11:00:00',1),(21,1,'Thursday','14:00:00',1),(22,1,'Thursday','15:00:00',1),(23,1,'Thursday','16:00:00',1),(24,1,'Thursday','17:00:00',1),(25,1,'Friday','09:00:00',1),(26,1,'Friday','10:00:00',1),(27,1,'Friday','11:00:00',1),(28,1,'Friday','13:00:00',1),(29,1,'Friday','14:00:00',1),(30,1,'Friday','15:00:00',1),(31,1,'Friday','16:00:00',1),(32,1,'Saturday','11:00:00',1),(33,1,'Saturday','14:00:00',1),(34,1,'Saturday','15:00:00',1),(35,1,'Saturday','16:00:00',1),(36,1,'Saturday','17:00:00',1),(37,2,'Sunday','11:00:00',1),(38,2,'Sunday','14:00:00',1),(39,2,'Sunday','15:00:00',1),(40,2,'Sunday','16:00:00',1),(41,2,'Sunday','17:00:00',1),(42,2,'Monday','09:00:00',1),(43,2,'Monday','10:00:00',1),(44,2,'Monday','11:00:00',1),(45,2,'Monday','13:00:00',1),(46,2,'Monday','14:00:00',1),(47,2,'Monday','15:00:00',1),(48,2,'Monday','16:00:00',1),(49,2,'Wednesday','11:00:00',1),(50,2,'Wednesday','14:00:00',1),(51,2,'Wednesday','15:00:00',1),(52,2,'Wednesday','16:00:00',1),(53,2,'Wednesday','17:00:00',1),(54,2,'Thursday','11:00:00',1),(55,2,'Thursday','14:00:00',1),(56,2,'Thursday','15:00:00',1),(57,2,'Thursday','16:00:00',1),(58,2,'Thursday','17:00:00',1),(59,2,'Friday','11:00:00',1),(60,2,'Friday','14:00:00',1),(61,2,'Friday','15:00:00',1),(62,2,'Friday','16:00:00',1),(63,2,'Friday','17:00:00',1),(64,2,'Saturday','09:00:00',1),(65,2,'Saturday','10:00:00',1),(66,2,'Saturday','11:00:00',1),(67,2,'Saturday','13:00:00',1),(68,2,'Saturday','14:00:00',1),(69,2,'Saturday','15:00:00',1),(70,2,'Saturday','16:00:00',1),(71,3,'Sunday','11:00:00',1),(72,3,'Sunday','14:00:00',1),(73,3,'Sunday','15:00:00',1),(74,3,'Sunday','16:00:00',1),(75,3,'Sunday','17:00:00',1),(76,3,'Monday','11:00:00',1),(77,3,'Monday','14:00:00',1),(78,3,'Monday','15:00:00',1),(79,3,'Monday','16:00:00',1),(80,3,'Monday','17:00:00',1),(81,3,'Tuesday','11:00:00',1),(82,3,'Tuesday','14:00:00',1),(83,3,'Tuesday','15:00:00',1),(84,3,'Tuesday','16:00:00',1),(85,3,'Tuesday','17:00:00',1),(86,3,'Wednesday','09:00:00',1),(87,3,'Wednesday','10:00:00',1),(88,3,'Wednesday','11:00:00',1),(89,3,'Wednesday','13:00:00',1),(90,3,'Wednesday','14:00:00',1),(91,3,'Wednesday','15:00:00',1),(92,3,'Wednesday','16:00:00',1),(93,3,'Friday','11:00:00',1),(94,3,'Friday','14:00:00',1),(95,3,'Friday','15:00:00',1),(96,3,'Friday','16:00:00',1),(97,3,'Friday','17:00:00',1),(98,3,'Saturday','09:00:00',1),(99,3,'Saturday','10:00:00',1),(100,3,'Saturday','11:00:00',1),(101,3,'Saturday','13:00:00',1),(102,3,'Saturday','14:00:00',1),(103,3,'Saturday','15:00:00',1),(104,3,'Saturday','16:00:00',1),(105,4,'Sunday','09:00:00',1),(106,4,'Sunday','10:00:00',1),(107,4,'Sunday','11:00:00',1),(108,4,'Sunday','13:00:00',1),(109,4,'Sunday','14:00:00',1),(110,4,'Sunday','15:00:00',1),(111,4,'Sunday','16:00:00',1),(112,4,'Tuesday','11:00:00',1),(113,4,'Tuesday','14:00:00',1),(114,4,'Tuesday','15:00:00',1),(115,4,'Tuesday','16:00:00',1),(116,4,'Tuesday','17:00:00',1),(117,4,'Wednesday','11:00:00',1),(118,4,'Wednesday','14:00:00',1),(119,4,'Wednesday','15:00:00',1),(120,4,'Wednesday','16:00:00',1),(121,4,'Wednesday','17:00:00',1),(122,4,'Thursday','09:00:00',1),(123,4,'Thursday','10:00:00',1),(124,4,'Thursday','11:00:00',1),(125,4,'Thursday','13:00:00',1),(126,4,'Thursday','14:00:00',1),(127,4,'Thursday','15:00:00',1),(128,4,'Thursday','16:00:00',1),(129,4,'Friday','09:00:00',1),(130,4,'Friday','10:00:00',1),(131,4,'Friday','11:00:00',1),(132,4,'Friday','13:00:00',1),(133,4,'Friday','14:00:00',1),(134,4,'Friday','15:00:00',1),(135,4,'Friday','16:00:00',1),(136,4,'Saturday','11:00:00',1),(137,4,'Saturday','14:00:00',1),(138,4,'Saturday','15:00:00',1),(139,4,'Saturday','16:00:00',1),(140,4,'Saturday','17:00:00',1),(141,5,'Sunday','09:00:00',1),(142,5,'Sunday','10:00:00',1),(143,5,'Sunday','11:00:00',1),(144,5,'Sunday','13:00:00',1),(145,5,'Sunday','14:00:00',1),(146,5,'Sunday','15:00:00',1),(147,5,'Sunday','16:00:00',1),(148,5,'Monday','09:00:00',1),(149,5,'Monday','10:00:00',1),(150,5,'Monday','11:00:00',1),(151,5,'Monday','13:00:00',1),(152,5,'Monday','14:00:00',1),(153,5,'Monday','15:00:00',1),(154,5,'Monday','16:00:00',1),(155,5,'Tuesday','09:00:00',1),(156,5,'Tuesday','10:00:00',1),(157,5,'Tuesday','11:00:00',1),(158,5,'Tuesday','13:00:00',1),(159,5,'Tuesday','14:00:00',1),(160,5,'Tuesday','15:00:00',1),(161,5,'Tuesday','16:00:00',1),(162,5,'Wednesday','09:00:00',1),(163,5,'Wednesday','10:00:00',1),(164,5,'Wednesday','11:00:00',1),(165,5,'Wednesday','13:00:00',1),(166,5,'Wednesday','14:00:00',1),(167,5,'Wednesday','15:00:00',1),(168,5,'Wednesday','16:00:00',1),(169,5,'Thursday','09:00:00',1),(170,5,'Thursday','10:00:00',1),(171,5,'Thursday','11:00:00',1),(172,5,'Thursday','13:00:00',1),(173,5,'Thursday','14:00:00',1),(174,5,'Thursday','15:00:00',1),(175,5,'Thursday','16:00:00',1),(176,5,'Saturday','09:00:00',1),(177,5,'Saturday','10:00:00',1),(178,5,'Saturday','11:00:00',1),(179,5,'Saturday','13:00:00',1),(180,5,'Saturday','14:00:00',1),(181,5,'Saturday','15:00:00',1),(182,5,'Saturday','16:00:00',1);
/*!40000 ALTER TABLE `barber_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `barbers`
--

DROP TABLE IF EXISTS `barbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barbers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `specialty` varchar(120) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_barber_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barbers`
--

LOCK TABLES `barbers` WRITE;
/*!40000 ALTER TABLE `barbers` DISABLE KEYS */;
INSERT INTO `barbers` VALUES (1,'Barber Angelo',NULL,1,'2025-11-22 18:14:57'),(2,'Barber Reymart',NULL,1,'2025-11-22 18:14:57'),(3,'Barber Rod',NULL,1,'2025-11-22 18:14:57'),(4,'Barber Lyndon',NULL,1,'2025-11-22 18:14:57'),(5,'Barber Ed',NULL,1,'2025-11-22 18:14:57');
/*!40000 ALTER TABLE `barbers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `barber_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `status` enum('pending','confirmed','completed','canceled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_res_service` (`service_id`),
  KEY `fk_res_barber` (`barber_id`),
  KEY `idx_res_user` (`user_id`),
  KEY `idx_res_sched` (`scheduled_at`),
  KEY `idx_res_status` (`status`),
  CONSTRAINT `fk_res_barber` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_res_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,1,1,3,'2025-11-25 17:00:00','completed',NULL,'2025-11-22 18:30:40','2025-11-22 19:30:07'),(2,1,2,1,'2025-11-25 11:00:00','completed',NULL,'2025-11-22 18:33:03','2025-11-22 19:30:06'),(3,1,2,1,'2025-11-28 11:00:00','completed',NULL,'2025-11-22 18:33:20','2025-11-22 19:30:10'),(4,1,1,4,'2025-11-26 16:00:00','completed',NULL,'2025-11-22 19:28:06','2025-11-22 19:30:09'),(5,1,2,4,'2025-11-26 14:00:00','completed',NULL,'2025-11-22 19:29:28','2025-11-22 19:30:08'),(6,1,2,5,'2025-11-24 16:00:00','completed',NULL,'2025-11-22 19:30:46','2025-11-22 19:31:21');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reservation_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` text DEFAULT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_rev_res` (`reservation_id`),
  KEY `fk_rev_user` (`user_id`),
  KEY `idx_rev_hidden` (`hidden`),
  KEY `idx_rev_created` (`created_at`),
  CONSTRAINT `fk_rev_res` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,NULL,NULL,'Ivan',5,'Very Good',0,'2025-11-22 18:49:56','2025-11-22 18:50:13'),(2,NULL,NULL,'Test Reviewer',4,'Good Service',0,'2025-11-22 19:10:26','2025-11-22 19:10:26'),(3,NULL,NULL,'Good Samaritan',4,'Good Haircuts',0,'2025-11-22 19:10:48','2025-11-22 19:10:48');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `duration_min` smallint(5) unsigned NOT NULL DEFAULT 30,
  `price_cents` int(10) unsigned NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_service_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Signature Haircut: ₱289',30,0,1),(2,'Regular Haircut: ₱239',30,0,1);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'customer','Test','test@gmail.com','999999999',NULL,'2025-11-22 18:30:40','2025-11-22 18:30:40');
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

-- Dump completed on 2025-11-23 10:02:06
