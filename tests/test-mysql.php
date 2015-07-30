<?php
/**
 * Testing class for \commands\site.php mysql commands
 *
 */
namespace \Terminus;
$siteinfo = pathinfo('../php/commands/site.php');
$sitefile = dirname(__FILE__).'/'.$siteinfo['dirname'].'/'.$siteinfo['basename'];
include $sitefile;
class mysqlpdoTest extends PHPUnit_Framework_TestCase {
	public function testgetLines() {

		$testfile = 'tests/test-dumpfile.sql';
		$testcommands_block = Site_Command::getLines($testfile); 
		$tcds_individual = iterator_to_array($testcommands_block);
		$tcds_individualzero = $tcds_individual[0];
		$tcds_individualone = $tcds_individual[1];
		$tcds_individualtwo = $tcds_individual[2];
		$tcds_individualthree = $tcds_individual[3];
		$tcds_individualfour = $tcds_individual[4];
		$tcds_individualfive = $tcds_individual[5];
		$tcds_individualsix = $tcds_individual[6];
		$tcds_individualseven = $tcds_individual[7];
		$tcds_individualeight = $tcds_individual[8];
		$tcds_individualnine = $tcds_individual[9];
		$tcds_individualten = $tcds_individual[10];
		$tcds_individualeleven = $tcds_individual[11];
		$tcds_individualtwelve = $tcds_individual[12];
		$tcds_individualthirteen = $tcds_individual[13];
		$tcds_individualfourteen = $tcds_individual[14];
		$tcds_individualfifteen = $tcds_individual[15];
		$tcds_individualsixteen = $tcds_individual[16];
		$tcds_individualseventeen = $tcds_individual[17];
		$tcds_individualeighteen = $tcds_individual[18];
		$tcds_individualnineteen = $tcds_individual[19];
		$tcds_individualtwenty = $tcds_individual[20];
		$tcds_individualtwentyone = $tcds_individual[21];
		$tcds_individualtwentytwo = $tcds_individual[22];
		$this->assertEquals("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;", $tcds_individualzero[0]);
		$this->assertEquals("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;", $tcds_individualone[0]);
		$this->assertEquals("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;", $tcds_individualtwo[0]);
		$this->assertEquals("/*!40101 SET NAMES latin1 */;", $tcds_individualthree[0]);
		$this->assertEquals("/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;", $tcds_individualfour[0]);
		$this->assertEquals("/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;", $tcds_individualfive[0]);
		$this->assertEquals("/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;", $tcds_individualsix[0]);
		$this->assertEquals("/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;", $tcds_individualseven[0]);
		$this->assertEquals("DROP TABLE IF EXISTS `testtable`;", $tcds_individualeight[0]);
		$this->assertEquals("/*!40101 SET @saved_cs_client     = @@character_set_client */;", $tcds_individualnine[0]);
		$this->assertEquals("/*!40101 SET character_set_client = utf8 */;", $tcds_individualten[0]);					
		$this->assertEquals("CREATE TABLE `testtable` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Letter` char(35) NOT NULL DEFAULT '',
  `Triletter` char(3) NOT NULL DEFAULT '',
  `Thousand` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `Triletter` (`Triletter`),
  CONSTRAINT `testtable_ibfk_1` FOREIGN KEY (`Triletter`) REFERENCES `Tri` (`Letter`)
) ENGINE=InnoDB AUTO_INCREMENT=4080 DEFAULT CHARSET=latin1;", $tcds_individualeleven[0]);
		$this->assertEquals("/*!40101 SET character_set_client = @saved_cs_client */;", $tcds_individualtwelve[0]);
		$this->assertEquals("INSERT INTO `testtable` VALUES (1,'A','ABC', 1000);", $tcds_individualthirteen[0]);
		$this->assertEquals("INSERT INTO `testtable` VALUES (2,'B','DEF', 2000);", $tcds_individualfourteen[0]);
		$this->assertEquals("INSERT INTO `testtable` VALUES (3,'C','GHI', 3000);", $tcds_individualfifteen[0]);
		$this->assertEquals("INSERT INTO `testtable` VALUES (4,'D','JKL', 4000);", $tcds_individualsixteen[0]);
		$this->assertEquals("INSERT INTO `testtable` VALUES (5,'E','MNO', 5000);", $tcds_individualseventeen[0]);	
		$this->assertEquals("INSERT INTO `testtable` VALUES (6,'F','PQR', 6000); INSERT INTO `testtable` VALUES (7,'G','STU', 7000);", $tcds_individualeighteen[0]);			
		$this->assertEquals("LOCK TABLES `users` WRITE;", $tcds_individualnineteen[0]);
		$this->assertEquals("/*!40000 ALTER TABLE `users` DISABLE KEYS */;", $tcds_individualtwenty[0]);
		$this->assertEquals("/*!40000 ALTER TABLE `users` ENABLE KEYS */;", $tcds_individualtwentyone[0]);
		$this->assertEquals("UNLOCK TABLES;", $tcds_individualtwentytwo[0]);	
	}
}
