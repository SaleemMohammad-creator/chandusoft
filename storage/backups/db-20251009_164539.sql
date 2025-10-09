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
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'John Doe','john@example.com','I am interested in your services','2025-10-04 06:13:25',''),(2,'Jane Smith','jane@example.com','Please contact me for a quote.','2025-10-04 06:15:00',''),(3,'Alice Brown','alice@example.com','Looking forward to your response.','2025-10-04 06:15:38',''),(4,'Michael Scott','michael@example.com','Can you provide more details about your services?','2025-10-04 06:20:29',''),(5,'Pam Beesly','pam@example.com','I am interested in collaboration.','2025-10-04 06:21:27',''),(6,'Jim Halpert','jim@example.com','Please send me a pricing list.','2025-10-04 06:21:53',''),(7,'Dwight Schrute','dwight@example.com','Looking for bulk services','2025-10-04 06:22:16',''),(8,'Angela Martin','angela@example.com','Can we schedule a meeting?','2025-10-04 06:22:35',''),(9,'Kevin Malone','kevin@example.com','Need more information about your products.','2025-10-04 06:22:57',''),(10,'Oscar Martinez','oscar@example.com','I have some questions before signing up.','2025-10-04 06:23:18',''),(11,'Stanley Hudson','stanley@example.com','Please contact me soon.','2025-10-04 06:23:37',''),(12,'Phyllis Vance','phyllis@example.com','Please contact me soon.','2025-10-04 06:24:24',''),(13,'Phyllis Vance','phyllis@example.com','Interested in a long-term contract.','2025-10-04 06:24:46',''),(14,'Meredith Palmer','meredith@example.com','Can I get a quotation?','2025-10-04 06:25:09',''),(15,'Habi','habi@gmail.com','Welcome.','2025-10-04 08:03:17',''),(16,'robin','robin@gmail.com','Hi, Hello world.','2025-10-04 08:04:35',''),(17,'Homer Simpson','homer@springfield.com','Do you accept doughnuts as payment?','2025-10-04 08:54:35',''),(18,'SpongeBob SquarePants','spongebob@bikini-bottom.com','Can you build a website underwater?','2025-10-04 08:54:57',''),(19,'Sherlock Holmes','sherlock@221bbaker.com','I deduced that you are the best developer.','2025-10-04 08:55:18',''),(20,'Harry Potter','harry@hogwarts.com','Need a magical landing page.','2025-10-04 08:55:43',''),(21,'Walter White','walter@heisenberg.com','Say my name... then build my site.','2025-10-04 08:56:06',''),(22,'qqq eee','qwe@gmail.com','eeeee','2025-10-04 09:34:09',''),(23,'ddd dd','qwe89@gmail.com','ffffff','2025-10-04 11:29:33',''),(24,'bbb','bbb@gmail.com','editor','2025-10-06 05:45:01',''),(95,'yyy','yy@gmail.com','toy','2025-10-09 06:36:29',NULL),(96,'dddyyy','qwe@gmail.com','rrrrr','2025-10-09 08:28:33',NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'Our Services','our services','','draft','2025-10-06 11:06:18','2025-10-06 11:06:18'),(2,'History','servie plans','<?php \r\nrequire_once __DIR__ . \'/../app/config.php\'; \r\n\r\n?>\r\n\r\n<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Chandusoft</title>\r\n    <link rel=\"stylesheet\" href=\"styles.css\">\r\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\">\r\n</head>\r\n<body>\r\n    <!-- Placeholder for dynamic header -->\r\n    <div id=\"header\"></div>\r\n    <?php include(\"header.php\"); ?>\r\n \r\n    <main>\r\n        <section class=\"hero\">\r\n            <div class=\"hero-content\">\r\n                <h1>Welcome to Chandusoft</h1>\r\n                <p>Delivering IT & BPO solutions for over 15 years.</p>\r\n                <a href=\"services.php\" class=\"btn-hero\"><b>Explore Services</b></a>\r\n            </div>\r\n        </section>\r\n \r\n        <section class=\"testimonials\">\r\n  <h2 style=\"color: rgb(42, 105, 240);\">What Our Clients Say</h2>\r\n  <div class=\"testimonial-container\">\r\n    <div class=\"testimonial\">\r\n      <p>Chandusoft helped us streamline our processes. Their 24/7 support means we never miss a client query.\"</p>\r\n      <h4>John Smith</h4>\r\n      <span>Operations Manager, GlobalTech</span>\r\n    </div>\r\n    <div class=\"testimonial\">\r\n      <p>\"Our e-commerce platform scaled smoothly after migrating with Chandusoft. Sales grew by 40% in just 6 months!\"</p>\r\n      <h4>Priya Verma</h4>\r\n      <span>Founder, TrendyMart</span>\r\n    </div>\r\n    <div class=\"testimonial\">\r\n      <p>\"The QA team at Chandusoft made our product launch seamless. Bug-free delivery on time!\"</p>\r\n      <h4>Ahmed Khan</h4>\r\n      <span>Product Lead, Medisoft</span>\r\n    </div>\r\n</section>\r\n    </main>\r\n \r\n    <!-- Placeholder for dynamic footer -->\r\n    <div id=\"footer\"></div>\r\n    <?php include(\"footer.php\"); ?>\r\n \r\n    <!-- The \"Back to Top\" button -->\r\n    <button id=\"back-to-top\" title=\"Back to Top\">Γåæ</button>\r\n \r\n    <!-- Include JavaScript for dynamic loading and smooth scroll -->\r\n     <script src=\"include.js\"></script>\r\n</body>\r\n</html>\r\n\r\n','draft','2025-10-06 11:07:09','2025-10-08 10:44:09'),(4,'Dashbord','qwerty','','draft','2025-10-06 11:21:09','2025-10-09 02:55:15'),(5,'Deals','Lot of deals in amazon','<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>Chandusoft</title>\r\n    <!-- Only one link to styles.css -->\r\n    <link rel=\"stylesheet\" href=\"styles.css\">\r\n    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\">\r\n</head>\r\n<body>\r\n    <!-- Placeholder for dynamic header -->\r\n    <div id=\"header\"></div>\r\n    <?php include(\"header.php\"); ?>\r\n\r\n    <main>\r\n        <h4><span class=\"section-title\">About Us</span></h4>\r\n        <p>\r\n            <span class=\"highlight\">Chandusoft</span> is a well-established company with over\r\n            <span class=\"highlight\">15 years of experience</span> in delivering IT and BPO solutions. We have a team of more than \r\n            <span class=\"highlight\">200 skilled professionals</span> operating from multiple locations. One of our key strengths is \r\n            <span class=\"highlight\">24/7 operations</span>, which allows us to support clients across different time zones. We place a strong emphasis on <span class=\"highlight\">data integrity and security</span>, which has helped us earn long-term trust from our partners. Our core service areas include\r\n            <span class=\"highlight\">Software Development</span>,\r\n            <span class=\"highlight\">Medical Process Services</span>, and\r\n            <span class=\"highlight\">E-Commerce Solutions</span>, all backed by a commitment to\r\n            <span class=\"highlight\">quality and process excellence.</span>\r\n        </p>\r\n    </main>\r\n</body>\r\n</html>','published','2025-10-08 07:00:26','2025-10-09 04:38:31'),(6,'Apparel deals','apparel','dkdjkwlfug jggjgk;klbfkfl jf;','draft','2025-10-08 07:25:36','2025-10-09 04:34:24'),(7,'Tops','tops','fashion Styles','draft','2025-10-08 07:39:40','2025-10-09 04:34:09'),(8,'Fields','fields','874561230','draft','2025-10-08 07:51:23','2025-10-09 02:55:04'),(9,'Our History','our-history','since 2001','draft','2025-10-08 07:54:28','2025-10-08 07:54:28'),(10,'FAQ','faq','<h1> Hi <h1>','published','2025-10-08 09:33:13','2025-10-09 05:02:59'),(11,'Server','server','Hai im from server side','published','2025-10-09 11:14:09','2025-10-09 11:14:09');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
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

-- Dump completed on 2025-10-09 16:45:39
