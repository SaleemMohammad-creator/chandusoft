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
-- Table structure for table `catalog`
--

DROP TABLE IF EXISTS `catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `image` varchar(255) DEFAULT NULL,
  `short_desc` text,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalog`
--

LOCK TABLES `catalog` WRITE;
/*!40000 ALTER TABLE `catalog` DISABLE KEYS */;
INSERT INTO `catalog` VALUES (1,'Be A Game Changer','server',45.00,'68f60868745cc.webp','','published','2025-10-20 06:28:44','2025-10-20 10:01:12'),(3,'Be A Game Changer','server-1',60.00,'68f8b927209b5.jfif','','published','2025-10-20 06:37:58','2025-10-22 10:59:51'),(4,'PIC','rrr',99.00,'img_68f9ca14f36d2.jfif','','published','2025-10-20 07:37:21','2025-10-23 06:24:20'),(5,'Bird','ttt',95.00,'68f8b9125c642.jfif','','published','2025-10-20 08:29:45','2025-10-22 10:59:30'),(7,'rrr','faq',66.00,'68f8b8fa4e473.jfif','','published','2025-10-20 09:27:22','2025-10-22 10:59:06'),(11,'tttt','faq-1',23.00,'68f8b8d452aa4.jfif','','published','2025-10-20 09:39:30','2025-10-22 10:58:28'),(12,'tttt','faq-2',35.00,'68f85ae257657.webp','','published','2025-10-20 09:45:13','2025-10-22 04:17:38'),(14,'wax','fff',55.00,'68f85e9f287db.webp','','published','2025-10-22 04:33:35','2025-10-22 04:33:35'),(15,'Nature','About Nature',55.00,'68f889e9d7212.webp','Qwerty','published','2025-10-22 07:38:19','2025-10-22 07:38:19'),(17,'Sample','sky',85.00,'68f8a0d71a67c.webp','','published','2025-10-22 09:16:07','2025-10-22 09:16:07'),(19,'zxcvbn','tyuiop',99.00,'68f8a4a1cf2e8.webp','','published','2025-10-22 09:32:18','2025-10-22 09:32:18'),(20,'Windows','image',50.00,'68f8b9aa018b2.jfif','','published','2025-10-22 11:02:02','2025-10-22 11:02:02'),(21,'Bird1','nature',66.00,'68f8baaaa2b47.jfif','','published','2025-10-22 11:06:18','2025-10-22 11:06:18'),(22,'Tiger','',89.00,'68f8be7b31019.jfif','tt','draft','2025-10-22 11:22:35','2025-10-22 11:22:35'),(23,'rat','rat',45.00,'img_68f99878ba0c5.webp','','published','2025-10-22 11:33:19','2025-10-23 02:52:41'),(24,'dddd','dddd',56.00,'img_68f997f8b1108.webp','','published','2025-10-23 02:50:33','2025-10-23 02:50:33'),(25,'shipping','shipping',41.00,'img_68f9984362a7a.webp','','published','2025-10-23 02:51:47','2025-10-23 02:51:47'),(26,'blog','blog',63.00,'2025/10/img_68f9a133ef0b9.webp','','published','2025-10-23 03:30:04','2025-10-23 03:30:04'),(27,'Cow','cow',86.00,'2025/10/img_68f9a32d1a095.webp','','published','2025-10-23 03:38:21','2025-10-23 03:38:21'),(28,'Farmer','farmer',111.00,'2025/10/img_68f9a89ac4454.jpg','','published','2025-10-23 04:01:30','2025-10-23 04:01:30'),(29,'Dairy','dairy',45.00,'2025/10/img_68f9aa8c9571e.png','','published','2025-10-23 04:09:49','2025-10-23 04:09:49'),(30,'Ghee','ghee',105.00,'2025/10/img_68f9ad487eace.jpg','','published','2025-10-23 04:21:29','2025-10-23 04:21:29'),(31,'Money','money',86.00,'2025/10/img_68f9af660981e.jpg','','published','2025-10-23 04:30:30','2025-10-24 09:56:39');
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enquiries`
--

LOCK TABLES `enquiries` WRITE;
/*!40000 ALTER TABLE `enquiries` DISABLE KEYS */;
INSERT INTO `enquiries` VALUES (1,14,'saleem','saleem@gmail.com','deatlis of thi product.','2025-10-22 10:27:44'),(2,14,'Basha','basha@gmail.com','Details of this product.','2025-10-22 10:37:23'),(3,7,'ddd','qwe@gmail.com','jjk','2025-10-22 11:47:28'),(4,7,'nirmal','nirmal@gmail.com','Describe about this Pic.','2025-10-22 11:50:30'),(5,5,'ddd','qwe@gmail.com','rrr','2025-10-22 12:15:10'),(6,5,'ddd','qwe@gmail.com','rrr','2025-10-22 12:16:46'),(7,15,'kohli','kohli@gmail.com','asdfghjkl','2025-10-22 13:29:15'),(8,31,'RRR','rrr@gmail.com','Explain this....','2025-10-24 09:31:34'),(9,31,'Money','money@gmail.com','ttttt','2025-10-24 10:00:07'),(10,31,'Money','money@gmail.com','ttttt','2025-10-24 10:09:45');
/*!40000 ALTER TABLE `enquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'John Doe','john@example.com','I am interested in your services','2025-10-04 06:13:25',''),(2,'Jane Smith','jane@example.com','Please contact me for a quote.','2025-10-04 06:15:00',''),(3,'Alice Brown','alice@example.com','Looking forward to your response.','2025-10-04 06:15:38',''),(4,'Michael Scott','michael@example.com','Can you provide more details about your services?','2025-10-04 06:20:29',''),(5,'Pam Beesly','pam@example.com','I am interested in collaboration.','2025-10-04 06:21:27',''),(6,'Jim Halpert','jim@example.com','Please send me a pricing list.','2025-10-04 06:21:53',''),(7,'Dwight Schrute','dwight@example.com','Looking for bulk services','2025-10-04 06:22:16',''),(8,'Angela Martin','angela@example.com','Can we schedule a meeting?','2025-10-04 06:22:35',''),(9,'Kevin Malone','kevin@example.com','Need more information about your products.','2025-10-04 06:22:57',''),(10,'Oscar Martinez','oscar@example.com','I have some questions before signing up.','2025-10-04 06:23:18',''),(11,'Stanley Hudson','stanley@example.com','Please contact me soon.','2025-10-04 06:23:37',''),(12,'Phyllis Vance','phyllis@example.com','Please contact me soon.','2025-10-04 06:24:24',''),(13,'Phyllis Vance','phyllis@example.com','Interested in a long-term contract.','2025-10-04 06:24:46',''),(14,'Meredith Palmer','meredith@example.com','Can I get a quotation?','2025-10-04 06:25:09',''),(15,'Habi','habi@gmail.com','Welcome.','2025-10-04 08:03:17',''),(16,'robin','robin@gmail.com','Hi, Hello world.','2025-10-04 08:04:35',''),(17,'Homer Simpson','homer@springfield.com','Do you accept doughnuts as payment?','2025-10-04 08:54:35',''),(18,'SpongeBob SquarePants','spongebob@bikini-bottom.com','Can you build a website underwater?','2025-10-04 08:54:57',''),(19,'Sherlock Holmes','sherlock@221bbaker.com','I deduced that you are the best developer.','2025-10-04 08:55:18',''),(20,'Harry Potter','harry@hogwarts.com','Need a magical landing page.','2025-10-04 08:55:43',''),(21,'Walter White','walter@heisenberg.com','Say my name... then build my site.','2025-10-04 08:56:06',''),(22,'qqq eee','qwe@gmail.com','eeeee','2025-10-04 09:34:09',''),(23,'ddd dd','qwe89@gmail.com','ffffff','2025-10-04 11:29:33',''),(24,'bbb','bbb@gmail.com','editor','2025-10-06 05:45:01',''),(95,'yyy','yy@gmail.com','toy','2025-10-09 06:36:29',NULL),(96,'dddyyy','qwe@gmail.com','rrrrr','2025-10-09 08:28:33',NULL),(97,'ddd','qwe@gmail.com','hdsjkjfljklf;','2025-10-10 09:08:06',NULL),(98,'ddd uuuuu','qwe@gmail.com','dyhtryrtrr','2025-10-10 09:14:17',NULL),(99,'saleem','saleem@gmail.com','Service provider.','2025-10-10 11:44:00',NULL),(100,'saleem','saleem@gmail.com','Service provider.','2025-10-10 11:45:44',NULL),(101,'ddd','qwe@gmail.com','ttttt','2025-10-13 11:16:59',NULL),(102,'ddd','qwe@gmail.com','fbfknfjf.lfkl','2025-10-18 07:59:49',NULL),(103,'ddd','rrr@gmail.com','yyyyy','2025-10-20 04:07:26',NULL),(104,'uuu','uu@gmail.com','yyyy','2025-10-23 07:09:05',NULL),(105,'uuu','uu@gmail.com','yyyy','2025-10-23 07:14:36',NULL),(106,'qqq eee','qwe@gmail.com','pbbbb','2025-10-23 07:15:08',NULL),(107,'saleem B','ww@gmail.com','yyyyyy','2025-10-23 07:18:36',NULL),(108,'qqq eee','qwe@gmail.com','pbbbb','2025-10-23 07:28:30',NULL),(109,'qqq eee','qwe@gmail.com','pbbbb','2025-10-23 07:28:39',NULL),(110,'qqq eee','qwe@gmail.com','pbbbb','2025-10-23 07:32:32',NULL),(111,'ddd','qwe@gmail.com','yyy','2025-10-24 02:52:12',NULL),(112,'qqq eee','qwe89@gmail.com','yyyy','2025-10-24 02:52:36',NULL),(113,'ttt','tt@gmail.com','yyyyy','2025-10-24 08:00:22',NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
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
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'Services','Our Services','<section id=\"Services\">\r\n    <h2 style=\"color: #2d5be3;\">Our Services</h2>\r\n    <div class=\"services-container\">\r\n        <div class=\"service-card\">\r\n            <i class=\"fas fa-building icon-blue\"></i>\r\n            <h3>Enterprise Application Solution</h3>\r\n            <p>Robust enterprise apps for seamless business operations.</p>\r\n        </div>\r\n        <div class=\"service-card\">\r\n            <i class=\"fas fa-mobile-alt icon-green\"></i>\r\n            <h3>Mobile Application Solution</h3>\r\n            <p>Cross-platform mobile apps with modern UI/UX.</p>\r\n        </div>\r\n        <div class=\"service-card\">\r\n            <i class=\"fas fa-laptop icon-black\"></i>\r\n            <h3>Web Portal Design & Solution</h3>\r\n            <p>Custom web portals for business and customer engagement.</p>\r\n        </div>\r\n        <div class=\"service-card\">\r\n            <i class=\"fas fa-tools icon-yellow\"></i>\r\n            <h3>Web Portal Maintenance & Content Management</h3>\r\n            <p>Continuous support, updates, and content handling.</p>\r\n        </div>\r\n        <div class=\"service-card\">\r\n            <i class=\"fas fa-vial icon-purple\"></i>\r\n            <h3>QA & Testing</h3>\r\n            <p>Quality assurance and testing for bug-free releases.</p>\r\n        </div>\r\n        <div class=\"service-card\">\r\n            <i class=\"fas fa-phone icon-red\"></i>\r\n            <h3>Business Process Outsourcing</h3>\r\n            <p>End-to-end BPO services with 24/7 operations.</p>\r\n        </div>\r\n    </div>\r\n</section>','draft','2025-10-06 11:06:18','2025-10-10 04:18:01'),(2,'History','servie plans','<?php \r\nrequire_once __DIR__ . \'/../app/config.php\'; \r\n\r\n?>\r\n\r\n<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Chandusoft</title>\r\n    <link rel=\"stylesheet\" href=\"styles.css\">\r\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\">\r\n</head>\r\n<body>\r\n    <!-- Placeholder for dynamic header -->\r\n    <div id=\"header\"></div>\r\n    <?php include(\"header.php\"); ?>\r\n \r\n    <main>\r\n        <section class=\"hero\">\r\n            <div class=\"hero-content\">\r\n                <h1>Welcome to Chandusoft</h1>\r\n                <p>Delivering IT & BPO solutions for over 15 years.</p>\r\n                <a href=\"services.php\" class=\"btn-hero\"><b>Explore Services</b></a>\r\n            </div>\r\n        </section>\r\n \r\n        <section class=\"testimonials\">\r\n  <h2 style=\"color: rgb(42, 105, 240);\">What Our Clients Say</h2>\r\n  <div class=\"testimonial-container\">\r\n    <div class=\"testimonial\">\r\n      <p>Chandusoft helped us streamline our processes. Their 24/7 support means we never miss a client query.\"</p>\r\n      <h4>John Smith</h4>\r\n      <span>Operations Manager, GlobalTech</span>\r\n    </div>\r\n    <div class=\"testimonial\">\r\n      <p>\"Our e-commerce platform scaled smoothly after migrating with Chandusoft. Sales grew by 40% in just 6 months!\"</p>\r\n      <h4>Priya Verma</h4>\r\n      <span>Founder, TrendyMart</span>\r\n    </div>\r\n    <div class=\"testimonial\">\r\n      <p>\"The QA team at Chandusoft made our product launch seamless. Bug-free delivery on time!\"</p>\r\n      <h4>Ahmed Khan</h4>\r\n      <span>Product Lead, Medisoft</span>\r\n    </div>\r\n</section>\r\n    </main>\r\n \r\n    <!-- Placeholder for dynamic footer -->\r\n    <div id=\"footer\"></div>\r\n    <?php include(\"footer.php\"); ?>\r\n \r\n    <!-- The \"Back to Top\" button -->\r\n    <button id=\"back-to-top\" title=\"Back to Top\">↑</button>\r\n \r\n    <!-- Include JavaScript for dynamic loading and smooth scroll -->\r\n     <script src=\"include.js\"></script>\r\n</body>\r\n</html>\r\n\r\n','draft','2025-10-06 11:07:09','2025-10-08 10:44:09'),(4,'Dashbord','qwerty','','draft','2025-10-06 11:21:09','2025-10-09 02:55:15'),(5,'Deals','Lot of deals in amazon','<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Chandusoft</title>\r\n    <!-- Only one link to styles.css -->\r\n    <link rel=\"stylesheet\" href=\"styles.css\">\r\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\">\r\n</head>\r\n<body>\r\n    <!-- Placeholder for dynamic header -->\r\n    <div id=\"header\"></div>\r\n    <?php include(\"header.php\"); ?>\r\n\r\n    <main>\r\n        <h4><span class=\"section-title\">About Us</span></h4>\r\n        <p>\r\n            <span class=\"highlight\">Chandusoft</span> is a well-established company with over\r\n            <span class=\"highlight\">15 years of experience</span> in delivering IT and BPO solutions. We have a team of more than \r\n            <span class=\"highlight\">200 skilled professionals</span> operating from multiple locations. One of our key strengths is \r\n            <span class=\"highlight\">24/7 operations</span>, which allows us to support clients across different time zones. We place a strong emphasis on <span class=\"highlight\">data integrity and security</span>, which has helped us earn long-term trust from our partners. Our core service areas include\r\n            <span class=\"highlight\">Software Development</span>,\r\n            <span class=\"highlight\">Medical Process Services</span>, and\r\n            <span class=\"highlight\">E-Commerce Solutions</span>, all backed by a commitment to\r\n            <span class=\"highlight\">quality and process excellence.</span>\r\n        </p>\r\n    </main>\r\n</body>\r\n</html>','draft','2025-10-08 07:00:26','2025-10-10 04:19:05'),(6,'Apparel deals','apparel','dkdjkwlfug jggjgk;klbfkfl jf;','draft','2025-10-08 07:25:36','2025-10-09 04:34:24'),(7,'Tops','tops','fashion Styles','draft','2025-10-08 07:39:40','2025-10-09 04:34:09'),(8,'Fields','fields','874561230','draft','2025-10-08 07:51:23','2025-10-09 02:55:04'),(9,'Our History','our-history','since 2001','published','2025-10-08 07:54:28','2025-10-20 07:12:49'),(10,'FAQ','faq','<h1> Hi <h1>','published','2025-10-08 09:33:13','2025-10-14 04:27:01'),(11,'Server','server','Hai im from server side','published','2025-10-09 11:14:09','2025-10-20 05:50:15'),(12,'Birds','birds','<?php\r\nrequire_once __DIR__ . \'/app/config.php\';\r\nrequire_once __DIR__ . \'/app/helpers.php\';\r\n?>\r\n<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <title>About Chandusoft</title>\r\n    <link rel=\"stylesheet\" href=\"/styles.css\">\r\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\">\r\n</head>\r\n<body>\r\n\r\n<?php include __DIR__ . \'/admin/header.php\'; ?>\r\n\r\n<main>\r\n    <h2>About Us</h2>\r\n    <p>\r\n        <span class=\"highlight\">Chandusoft</span> is a well-established company with over\r\n            <span class=\"highlight\">15 years of experience</span> in delivering IT and BPO solutions. We have a team of more than\r\n            <span class=\"highlight\">200 skilled professionals</span> operating from multiple locations. One of our key strengths is\r\n            <span class=\"highlight\">24/7 operations</span>, which allows us to support clients across different time zones. We place a strong emphasis on <span class=\"highlight\">data integrity and security</span>, which has helped us earn long-term trust from our partners. Our core service areas include\r\n            <span class=\"highlight\">Software Development</span>,\r\n            <span class=\"highlight\">Medical Process Services</span>, and\r\n            <span class=\"highlight\">E-Commerce Solutions</span>, all backed by a commitment to\r\n            <span class=\"highlight\">quality and process excellence.</span>\r\n    </p>\r\n</main>\r\n\r\n<?php include __DIR__ . \'/admin/footer.php\'; ?>\r\n</body>\r\n</html>','published','2025-10-23 10:11:35','2025-10-24 05:01:04');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name','Chandusoft Technologies','2025-10-18 10:42:45','2025-10-23 07:03:58'),(2,'site_logo','logo_1761203038.jpg','2025-10-18 10:42:45','2025-10-23 07:03:58');
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('admin','editor') NOT NULL DEFAULT 'editor',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Saleem.M','saleem@gmail.com','$2y$10$mIm7VarKZ8IqJuG8sbGvju/HKausdJQMLlh92oEciYJl4ctEkdxEa','2025-10-04 04:07:13','editor'),(2,'Musthafa.Sk','musthafa@gmail.com','$2y$10$xeGGcSKXVIBYMyKbT.0wbOoZh7NaDJGxtEejNucM9kAeHcLH85IUq','2025-10-04 04:21:29','admin'),(3,'Jaisai.Ch','jaisai@gmail.com','$2y$10$HNfxntWTczuAeepcgyz8w.QAqLRG2beBo7gXcYkXwgQsglZ9JS9BG','2025-10-04 04:22:09','editor'),(4,'Sameer.M','sameer@gmail.com','$2y$10$n6CDBoQyb/ErdAiE1hsT9u2R7rX7Txuhb5srXaWTYfLvIClTIRr..','2025-10-04 04:22:42','editor'),(5,'Phani.B','phani@gmail.com','$2y$10$xAH38icaxgE/J3/ntbnRIui1gqH.oHxcxj285jth/bWzPYgOGwg46','2025-10-04 04:23:34','editor'),(6,'ddd','ddd@gmail.com','$2y$10$dNkVNskjKWMqtYJFkou/3OoOCTlL3y3OUGbvrhRrXO.LGPpxSxIFq','2025-10-04 05:06:33','editor'),(7,'Rabin.S','rabin@gmail.com','$2y$10$s1ytEha8D8OvU52vfrw3YuBPIgpGm0SrMClfC6Awa5BnfEW9VjvdW','2025-10-04 06:49:22','editor'),(8,'ssss','ss@gmail.com','$2y$10$4HdJstIz3ZjVlai.ERXFReZRvmF.EkLTRslawyW.wjacxpMHGEme2','2025-10-04 10:56:27','editor'),(9,'Sai I','sai@gmail.com','$2y$10$1xCtJ3en72n0J0rxn5lfvuXNF4BmssWcnK0wEV2PIqGqlM7dEpHfm','2025-10-06 03:59:24','admin');
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

-- Dump completed on 2025-10-25 10:00:14
