-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: studenthub
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

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
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `venue` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `poster_filename` varchar(255) DEFAULT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'General',
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Annual Tech Fest','A college-wide technology festival featuring coding contests, project exhibitions, and technical talks.','2026-11-15','Main Auditorium','2026-10-28 21:18:15',NULL,'Technical','open'),(2,'Web Development Workshop','A hands-on workshop covering HTML, CSS, JavaScript, and modern web development practices.','2026-11-20','Computer Lab 1','2026-10-28 21:18:15',NULL,'Workshop','open'),(3,'Inter-College Cricket Tournament','A sports tournament bringing together cricket teams from nearby colleges.','2026-12-05','College Sports Ground','2026-10-28 21:18:15',NULL,'Sports','open'),(4,'Cultural Night','An evening of music, dance, drama, and student performances.','2026-12-12','Open-Air Stage','2026-10-28 21:18:15',NULL,'Cultural','open'),(5,'Cybersecurity Awareness Seminar','A seminar about online safety, phishing, passwords, and responsible digital practices.','2026-11-25','Seminar Hall','2026-10-28 21:18:15',NULL,'Seminar','open'),(6,'Innovation Project Exhibition','Students present prototypes and innovative solutions to real-world problems.','2026-12-18','Innovation Centre','2026-10-28 21:18:15',NULL,'Technical','open'),(7,'Resume Building Session','A career-development session covering resumes, interview preparation, and professional profiles.','2026-11-10','Placement Cell','2026-10-28 21:18:15',NULL,'Career','closed'),(8,'Photography Competition','A campus photography competition celebrating creativity and student life.','2026-10-20','Central Campus','2026-10-28 21:18:15',NULL,'Cultural','closed'),(9,'Data Structures Coding Challenge','A programming challenge focused on arrays, linked lists, stacks, queues, trees, and algorithms.','2026-11-28','Computer Lab 2','2026-10-28 21:18:15',NULL,'Technical','open'),(10,'Environmental Awareness Drive','A student-led initiative promoting campus cleanliness, recycling, and environmental awareness.','2026-12-22','College Campus','2026-10-28 21:18:15',NULL,'Social','open');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-29  3:16:22
