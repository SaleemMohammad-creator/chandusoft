-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: chandusoft
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
INSERT INTO `admin_logs` VALUES (1,1,'Login Success','User sai@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-07 11:54:21'),(2,1,'View Pages List','Filter: all','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-07 11:54:32'),(3,1,'Logout','User sai logged out from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0','2025-11-07 11:54:38'),(4,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 02:53:12'),(5,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 02:53:19'),(6,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:00:07'),(7,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:00:18'),(8,2,'Catalog Item Added','Title: Milk | Slug: milk | Price: 5','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:01:20'),(9,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:02:03'),(10,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:02:28'),(11,2,'Catalog Item Added','Title: Ghee | Slug: ghee | Price: 10','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:02:57'),(12,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:03:23'),(13,2,'Catalog Item Added','Title: Paneer | Slug: paneer | Price: 7','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:04:24'),(14,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:04:45'),(15,2,'Catalog Item Added','Title: Curd | Slug: curd | Price: 6','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:05:17'),(16,2,'Catalog Item Added','Title: Money | Slug: money | Price: 11','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:06:06'),(17,2,'Catalog Item Added','Title: Nature | Slug: nature | Price: 15','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:08:11'),(18,2,'Catalog Item Added','Title: Honey | Slug: honey | Price: 20','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:15:09'),(19,2,'Logout','User Saleem.M logged out from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:16:01'),(20,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:31:32'),(21,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:31:46'),(22,2,'View Pages List','Filter: all','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:32:21'),(23,2,'Page Created','Title: About Us, Slug: about-us, Status: draft','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:32:58'),(24,2,'View Pages List','Filter: all','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:33:01'),(25,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:33:09'),(26,2,'Catalog Item Updated','ID: 7 | Title: Honey | Slug: honey | Price: 20','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:33:28'),(27,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:33:31'),(28,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:33:35'),(29,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:40:35'),(30,2,'View Pages List','Filter: all','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:41:47'),(31,2,'View Pages List','Filter: draft','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:41:52'),(32,2,'Page Archived','Page ID: 1 by Saleem.M','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:44:11'),(33,2,'View Pages List','Filter: all','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:44:11'),(34,2,'Page Unarchived','Page ID: 1 by Saleem.M','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:44:19'),(35,2,'View Pages List','Filter: all','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 03:44:19'),(36,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 08:15:23'),(37,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 08:15:26'),(38,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 09:55:10'),(39,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 10:00:22'),(40,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 11:19:29'),(41,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 11:19:39'),(42,2,'Catalog Item Added','Title: Logo | Slug: logo | Price: 4','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 11:20:38'),(43,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 11:21:11'),(44,2,'Login Success','User saleem@gmail.com logged in from IP ::1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 11:26:27'),(45,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 11:33:21'),(46,2,'Catalog Item Added','Title: Cow | Slug: cow | Price: 10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-10 11:34:31'),(47,1,'Login Success','User sai@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:38:27'),(48,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:39:43'),(49,2,'Login Success','User saleem@gmail.com logged in from IP ::1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:43:47'),(50,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:43:51'),(51,2,'Catalog Item Updated','ID: 9 | Title: Cow | Slug: cow | Price: 5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:44:10'),(52,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:44:17'),(53,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:44:23'),(54,2,'Lead Search','Saleem.M (Admin) searched leads for: \'CSE\'','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:45:03'),(55,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:45:38'),(56,2,'Catalog Item Added','Title: Farmer | Slug: farmer | Price: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:46:58'),(57,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:47:26'),(58,2,'View Pages List','Filter: all','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:48:08'),(59,2,'View Pages List','Filter: all','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:48:22'),(60,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:48:43'),(61,2,'Catalog Item Updated','ID: 10 | Title: Farmer | Slug: farmer | Price: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:48:57'),(62,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:49:07'),(63,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:49:11'),(64,2,'Catalog Item Updated','ID: 10 | Title: Farmer | Slug: farmer | Price: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:49:26'),(65,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:49:32'),(66,2,'Catalog Item Added','Title: Nutrition | Slug: nutrition | Price: 10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:53:43'),(67,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:54:21'),(68,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 02:54:26'),(69,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 03:03:50'),(70,2,'Catalog List Viewed','Viewed main catalog list','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 03:06:57'),(71,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 04:23:28'),(72,2,'Logout','User Saleem.M logged out from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 04:23:33'),(73,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 05:17:27'),(74,2,'Catalog List Viewed','Viewed main catalog list','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 05:17:31'),(75,2,'Login Success','User saleem@gmail.com logged in from IP 127.0.0.1','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 07:39:58'),(76,2,'View Pages List','Filter: all','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 07:40:34'),(77,2,'Lead Search','Saleem.M (Admin) searched leads for: \'cse\'','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-11 07:56:46');
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `image` varchar(255) DEFAULT NULL,
  `short_desc` text,
  `status` enum('published','draft','archived') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalog`
--

LOCK TABLES `catalog` WRITE;
/*!40000 ALTER TABLE `catalog` DISABLE KEYS */;
INSERT INTO `catalog` VALUES (1,'Milk','milk',5.00,'2025/11/img_69115580b8dda.jpg','','published','2025-11-10 03:01:20','2025-11-10 03:01:20'),(2,'Ghee','ghee',10.00,'2025/11/img_691155e147451.png','','published','2025-11-10 03:02:57','2025-11-10 03:02:57'),(3,'Paneer','paneer',7.00,'2025/11/img_691156386de42.png','','published','2025-11-10 03:04:24','2025-11-10 03:04:24'),(4,'Curd','curd',6.00,'2025/11/img_6911566d004d1.png','','published','2025-11-10 03:05:17','2025-11-10 03:05:17'),(5,'Money','money',11.00,'2025/11/img_6911569d5759e.jpg','','published','2025-11-10 03:06:06','2025-11-10 03:06:06'),(6,'Nature','nature',15.00,'2025/11/img_6911571b18679.jpg','','published','2025-11-10 03:08:11','2025-11-10 03:08:11'),(8,'Logo','logo',4.00,'2025/11/img_6911ca868bcb9.jpg','','published','2025-11-10 11:20:38','2025-11-10 11:20:38'),(9,'Cow','cow',5.00,'2025/11/img_6911cdc75a12e.jpg','','published','2025-11-10 11:34:31','2025-11-11 02:44:34'),(10,'Farmer','farmer',7.00,'2025/11/img_6912a3a270e7b.jpg','','published','2025-11-11 02:46:58','2025-11-11 02:49:26'),(11,'Nutrition','nutrition',10.00,'2025/11/img_6912a53663146.jpg','','published','2025-11-11 02:53:43','2025-11-11 02:53:43');
/*!40000 ALTER TABLE `catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enquiries`
--

DROP TABLE IF EXISTS `enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enquiries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `catalog_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `catalog_fk` (`catalog_id`),
  CONSTRAINT `enquiries_catalog_fk` FOREIGN KEY (`catalog_id`) REFERENCES `catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enquiries`
--

LOCK TABLES `enquiries` WRITE;
/*!40000 ALTER TABLE `enquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `enquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'CSE','cse@gmail.com','Hi, Welcome To CSE...','2025-11-11 02:39:19',NULL),(2,'John Doe','john.doe.1@example.com','This is a test message from John','2025-11-11 07:44:29',NULL),(3,'Jane Smith','jane.smith.2@example.com','Inquiring about test data generation.','2025-11-11 07:45:29',NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_order_items_catalog` FOREIGN KEY (`product_id`) REFERENCES `catalog` (`id`),
  CONSTRAINT `fk_order_items_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,4,'Curd',1,6.00,6.00,'2025-11-10 04:10:56'),(2,1,6,'Nature',2,15.00,30.00,'2025-11-10 04:10:56'),(3,2,6,'Nature',1,15.00,15.00,'2025-11-10 06:05:51'),(4,3,6,'Nature',1,15.00,15.00,'2025-11-10 06:28:06'),(5,4,5,'Money',1,11.00,11.00,'2025-11-10 07:02:15'),(6,5,4,'Curd',1,6.00,6.00,'2025-11-10 07:12:35'),(7,6,1,'Milk',1,5.00,5.00,'2025-11-10 07:20:11'),(8,7,1,'Milk',1,5.00,5.00,'2025-11-10 07:35:42'),(9,8,6,'Nature',1,15.00,15.00,'2025-11-10 07:44:30'),(10,9,6,'Nature',1,15.00,15.00,'2025-11-10 07:49:48'),(11,10,6,'Nature',1,15.00,15.00,'2025-11-10 07:55:38'),(12,11,4,'Curd',1,6.00,6.00,'2025-11-10 08:08:30'),(13,12,6,'Nature',1,15.00,15.00,'2025-11-10 08:19:42'),(14,13,6,'Nature',1,15.00,15.00,'2025-11-10 08:31:52'),(15,14,6,'Nature',1,15.00,15.00,'2025-11-10 09:08:34'),(16,15,3,'Paneer',1,7.00,7.00,'2025-11-10 09:14:56'),(17,16,5,'Money',1,11.00,11.00,'2025-11-10 09:22:56'),(18,17,6,'Nature',1,15.00,15.00,'2025-11-10 09:37:46'),(19,18,6,'Nature',1,15.00,15.00,'2025-11-10 09:42:56'),(20,19,6,'Nature',1,15.00,15.00,'2025-11-10 09:52:51'),(21,20,11,'Nutrition',1,10.00,10.00,'2025-11-11 07:48:22'),(22,21,11,'Nutrition',1,10.00,10.00,'2025-11-11 07:49:30'),(23,22,11,'Nutrition',1,10.00,10.00,'2025-11-11 07:51:07');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_ref` varchar(50) NOT NULL,
  `customer_name` varchar(120) NOT NULL,
  `customer_email` varchar(160) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_gateway` enum('stripe','paypal','razorpay') NOT NULL DEFAULT 'stripe',
  `payment_status` enum('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_ref_unique` (`order_ref`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ORD-43962C1E','qqq eee','qwe@gmail.com',36.00,'stripe','pending','cs_test_a1G7wJup1PPTurP94CEOqoy9HszDt8pzIQheg0BCupcnitcN6nxIFoqkCZ','[{\"quantity\": 1, \"product_id\": 4, \"unit_price\": 6, \"product_name\": \"Curd\"}, {\"quantity\": 2, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 04:10:56','2025-11-10 04:13:29'),(2,'ORD-FB96A628','qqq eee','qwe@gmail.com',15.00,'stripe','pending','cs_test_a1DlWjTgLaUFETRffhaplFQETyXGdO3wiHpOASAXtdPw0zPU7CI7etlzl9','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 06:05:51','2025-11-10 06:05:55'),(3,'ORD-9636936D','saleem','saleem@gmail.com',15.00,'stripe','paid','pi_3SRoaf5pZLGe3mwf1dcyyZbK','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 06:28:06','2025-11-10 09:29:10'),(4,'ORD-A3BF9169','qqq','qwe89@gmail.com',11.00,'stripe','paid','pi_3SRp7o5pZLGe3mwf0Fy3hA2g','[{\"quantity\": 1, \"product_id\": 5, \"unit_price\": 11, \"product_name\": \"Money\"}]','2025-11-10 07:02:15','2025-11-10 10:02:47'),(5,'ORD-BB433647','qqq eee','qwe@gmail.com',6.00,'stripe','paid','pi_3SRpHk5pZLGe3mwf06irpQxp','[{\"quantity\": 1, \"product_id\": 4, \"unit_price\": 6, \"product_name\": \"Curd\"}]','2025-11-10 07:12:35','2025-11-10 10:13:02'),(6,'ORD-2C29A4B1','saleem','saleem@gmail.com',5.00,'stripe','paid','pi_3SRpPA5pZLGe3mwf1bn19xi9','[{\"quantity\": 1, \"product_id\": 1, \"unit_price\": 5, \"product_name\": \"Milk\"}]','2025-11-10 07:20:11','2025-11-10 08:20:22'),(7,'ORD-E158B493','Rahim','rahim@gmail.com',5.00,'stripe','paid','pi_3SRpe95pZLGe3mwf02poo8Ar','[{\"quantity\": 1, \"product_id\": 1, \"unit_price\": 5, \"product_name\": \"Milk\"}]','2025-11-10 07:35:42','2025-11-10 08:36:11'),(8,'ORD-9E4FA204','qqq eee','ww@gmail.com',15.00,'stripe','paid','pi_3SRpmd5pZLGe3mwf1DHvyRkl','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 07:44:30','2025-11-10 08:46:06'),(9,'ORD-AE2105CB','qqq eee','qwe@gmail.com',15.00,'stripe','paid','pi_3SRprj5pZLGe3mwf0xVrsOjw','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 07:49:48','2025-11-10 08:49:23'),(10,'ORD-EDE99C63','qqq eee','qwe78823@gmail.com',15.00,'stripe','paid','pi_3SRpxN5pZLGe3mwf0GTAVZ6z','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 07:55:38','2025-11-10 08:56:46'),(11,'ORD-8C7A79DF','qqq','qwe@gmail.com',6.00,'stripe','paid','pi_3SRq9t5pZLGe3mwf1OxPscC0','[{\"quantity\": 1, \"product_id\": 4, \"unit_price\": 6, \"product_name\": \"Curd\"}]','2025-11-10 08:08:30','2025-11-10 08:09:08'),(12,'ORD-6E390F6F','saleem','saleem@gmail.com',15.00,'stripe','cancelled','cs_test_a192p4FMcL8DOYVDVNxQnHuNHPYC76WG5AeyxYHegor0Hdw8YUSTl5G4oh','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 08:19:42','2025-11-10 08:27:09'),(13,'ORD-14FC52EF','qqq eee','qwe@gmail.com',15.00,'stripe','cancelled','cs_test_a1M04IpwGNS1OUIeGQ7QQVSSN5p37vpHqTeoD2XW7nyyaRsBFWPAQaeBB5','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 08:31:52','2025-11-10 08:32:06'),(14,'ORD-6FE7B718','qqq eee','ww@gmail.com',15.00,'stripe','cancelled','cs_test_a1TSaIQosfuKtiRcSl3QJKWWwK76pIeGKKc6Y42X1LNzTayQMZqHktYaUQ','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 09:08:34','2025-11-10 09:08:54'),(15,'ORD-7CFD6378','qqq eee','qwe@gmail.com',7.00,'stripe','failed','cs_test_a16JKyWtiVcVqKTZsX1zwhCjChx96UImWlbVlcZ12DQHKolpeWvErVXCGX','[{\"quantity\": 1, \"product_id\": 3, \"unit_price\": 7, \"product_name\": \"Paneer\"}]','2025-11-10 09:14:56','2025-11-10 09:16:37'),(16,'ORD-0056647E','qqq eee','qwe@gmail.com',11.00,'stripe','failed','cs_test_a1mxUGebJptlLUenZg2nhAmgKqNFhjj9OnRwuMkdWVOaXYHo1yKAC2X9MJ','[{\"quantity\": 1, \"product_id\": 5, \"unit_price\": 11, \"product_name\": \"Money\"}]','2025-11-10 09:22:56','2025-11-10 09:26:31'),(17,'ORD-B27B85D6','qqq eee','qwe@gmail.com',15.00,'stripe','failed','cs_test_a1bi7pjxx5pgg0ekAokShTMxLvUSf3bokNsc81hlqIomBNpNj2UCIyuaNi','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 09:37:46','2025-11-10 09:38:03'),(18,'ORD-622D3F14','CSE','cse@gmail.com',15.00,'stripe','failed','cs_test_a1HJWAJfWyleoppb6BgxUzx89x6hbGHAeEh5USoVzO3g3W2HhVeXmcupny','[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 09:42:56','2025-11-10 09:43:12'),(19,'ORD-99234D90','Rahul','rahul@gmail.com',15.00,'stripe','failed',NULL,'[{\"quantity\": 1, \"product_id\": 6, \"unit_price\": 15, \"product_name\": \"Nature\"}]','2025-11-10 09:52:51','2025-11-10 09:53:08'),(20,'ORD-CB102080','Youth','yyy@gmail.com',10.00,'stripe','paid','pi_3SSCJm5pZLGe3mwf1TISiLRa','[{\"quantity\": 1, \"product_id\": 11, \"unit_price\": 10, \"product_name\": \"Nutrition\"}]','2025-11-11 07:48:22','2025-11-11 07:48:49'),(21,'ORD-85BFB5A8','Sam','sam@gmail.com',10.00,'stripe','failed',NULL,'[{\"quantity\": 1, \"product_id\": 11, \"unit_price\": 10, \"product_name\": \"Nutrition\"}]','2025-11-11 07:49:30','2025-11-11 07:49:44'),(22,'ORD-E9A00A3E','DDD','sam@gmail.com',10.00,'stripe','failed',NULL,'[{\"quantity\": 1, \"product_id\": 11, \"unit_price\": 10, \"product_name\": \"Nutrition\"}]','2025-11-11 07:51:07','2025-11-11 07:58:10');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content_html` longtext NOT NULL,
  `status` enum('published','draft','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'About Us','about-us','Since 1998.........','draft','2025-11-10 03:32:58','2025-11-10 03:44:19');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','Chandusoft Technologies','2025-11-11 05:19:03','2025-11-11 05:19:03'),(2,'site_logo','logo_1762838343.jpg','2025-11-11 05:19:03','2025-11-11 05:19:03');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('admin','editor','user') NOT NULL DEFAULT 'editor',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'sai','sai@gmail.com','$2y$10$IbxrdiFk8QEvVYSe2fc0LOLk8JV1EoR16unZ5j4bMNQ2xrHL1e/ty','2025-11-07 11:54:08','editor'),(2,'Saleem.M','saleem@gmail.com','$2y$10$Oo4Py8UdF0IeI5R.ztRzE.rnGFY.TCGTkrKj9Tg2JRXlp6ACzlJk6','2025-11-10 02:52:27','admin'),(3,'Musthafa.Sk','musthafa@gmail.com','$2y$10$T.YH19Wr/i3NofhXVSCMJOADHCPvhI7zs.UceJqJHFWS.iO2KvhC.','2025-11-11 02:38:14','editor');
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

-- Dump completed on 2025-11-11 13:43:28
