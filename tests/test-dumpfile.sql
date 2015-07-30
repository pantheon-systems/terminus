/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES latin1 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
--
-- Table structure for table `testtable`
--
DROP TABLE IF EXISTS `testtable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `testtable` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Letter` char(35) NOT NULL DEFAULT '',
  `Triletter` char(3) NOT NULL DEFAULT '',
  `Thousand` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `Triletter` (`Triletter`),
  CONSTRAINT `testtable_ibfk_1` FOREIGN KEY (`Triletter`) REFERENCES `Tri` (`Letter`)
) ENGINE=InnoDB AUTO_INCREMENT=4080 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- dump data
INSERT INTO `testtable` VALUES (1,'A','ABC', 1000);
INSERT INTO `testtable` VALUES (2,'B','DEF', 2000);
INSERT INTO `testtable` VALUES (3,'C','GHI', 3000);
INSERT INTO `testtable` VALUES (4,'D','JKL', 4000);
INSERT INTO `testtable` VALUES (5,'E','MNO', 5000);
INSERT INTO `testtable` VALUES (6,'F','PQR', 6000); INSERT INTO `testtable` VALUES (7,'G','STU', 7000);
--
-- Dumping data for table `users`
--
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

