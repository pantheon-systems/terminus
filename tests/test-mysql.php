<?php
/**
 * Testing class for \commands\site.php mysql commands
 *
 */
namespace \Terminus;
$siteinfo = pathinfo('../php/commands/site.php');
$sitefile = dirname(__FILE__).'/'.$siteinfo['dirname'].'/'.$siteinfo['basename'];
include $sitefile;

function newTestGen() {
    $lines = array();
    yield array('INSERT INTO `wp_bp_user_blogs` VALUES("2", "2", "1");', false);
    yield array('INSERT INTO `wp_bp_user_blogs` VALUES("3", "4", "1");', false);
    yield array("INSERT INTO `testtable` VALUES (3,'C','GHI', 3000);", false);
    yield array("INSERT INTO `testtable` VALUES (4,'D','JKL', 4000);", false);
    yield array("INSERT INTO `testtable` VALUES (5,'E','MNO', 5000);", false);
    yield array("INSERT INTO `testtable` VALUES (6,'F','PQR', 6000); INSERT INTO `testtable` VALUES (7,'G','STU', 7000);", true);
    yield array('INSERT INTO `wp_term_relationships VALUES("1051", "75", "0");', false);
    yield array('INSERT INTO `wp_term_relationships` VALUES("1041", "70", "0");', false);
}

class mysqlpdoTest extends PHPUnit_Framework_TestCase {
    public function testgetLines() {
        $testfile = 'tests/test-dumpfile.sql';
        $testcommands_block = Site_Command::getLines($testfile);
        $tcds_individual = iterator_to_array($testcommands_block);
        $tcds_individualzero = trim($tcds_individual[0]);
        $tcds_individualone = trim($tcds_individual[1]);
        $tcds_individualtwo = trim($tcds_individual[2]);
        $tcds_individualthree = trim($tcds_individual[3]);
        $tcds_individualfour = trim($tcds_individual[4]);
        $tcds_individualfive = trim($tcds_individual[5]);
        $tcds_individualsix =  trim($tcds_individual[6]);
        $tcds_individualseven = trim($tcds_individual[7]);
        $tcds_individualeight = trim($tcds_individual[8]);
        $tcds_individualnine = trim($tcds_individual[9]);
        $tcds_individualten = trim($tcds_individual[10]);
        $tcds_individualeleven = trim($tcds_individual[11]);
        $tcds_individualtwelve = trim($tcds_individual[12]);
        $tcds_individualthirteen = trim($tcds_individual[13]);
        $tcds_individualfourteen = trim($tcds_individual[14]);
        $tcds_individualfifteen = trim($tcds_individual[15]);
        $tcds_individualsixteen = trim($tcds_individual[16]);
        $tcds_individualseventeen = trim($tcds_individual[17]);
        $tcds_individualeighteen = trim($tcds_individual[18]);
        $tcds_individualnineteen = trim($tcds_individual[19]);
        $tcds_individualtwenty = trim($tcds_individual[20]);
        $tcds_individualtwentyone = trim($tcds_individual[21]);
        $tcds_individualtwentytwo = trim($tcds_individual[22]);
        $tcds_individualtwentythree = trim($tcds_individual[23]);
        $tcds_individualtwentyfour = trim($tcds_individual[24]);
        $tcds_individualtwentyfive = trim($tcds_individual[25]);
        $tcds_individualtwentysix = trim($tcds_individual[26]);
        $tcds_individualtwentyseven = trim($tcds_individual[27]);
        $tcds_individualtwentyeight = trim($tcds_individual[28]);
        $tcds_individualtwentynine = trim($tcds_individual[29]);
        $tcds_individualthirty = trim($tcds_individual[30]);
        $tcds_individualthirtyone = trim($tcds_individual[31]);
        $this->assertEquals("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;", $tcds_individualzero);
        $this->assertEquals("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;", $tcds_individualone);
        $this->assertEquals("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;", $tcds_individualtwo);
        $this->assertEquals("/*!40101 SET NAMES latin1 */;", $tcds_individualthree);
        $this->assertEquals("/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;", $tcds_individualfour);
        $this->assertEquals("/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;", $tcds_individualfive);
        $this->assertEquals("/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;", $tcds_individualsix);
        $this->assertEquals("/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;", $tcds_individualseven);
        $this->assertEquals("-- Table structure for table `testtable`", $tcds_individualeight);
        $this->assertEquals("DROP TABLE IF EXISTS `testtable`;", $tcds_individualnine);
        $this->assertEquals("/*!40101 SET @saved_cs_client     = @@character_set_client */;", $tcds_individualten);
        $this->assertEquals("/*!40101 SET character_set_client = utf8 */;", $tcds_individualeleven);
        $this->assertEquals("CREATE TABLE `testtable` (", $tcds_individualtwelve);
        $this->assertEquals("`ID` int(11) NOT NULL AUTO_INCREMENT,", $tcds_individualthirteen);
        $this->assertEquals("`Letter` char(35) NOT NULL DEFAULT '',", $tcds_individualfourteen);
        $this->assertEquals("`Triletter` char(3) NOT NULL DEFAULT '',", $tcds_individualfifteen);
        $this->assertEquals("`Thousand` int(11) NOT NULL DEFAULT '0',", $tcds_individualsixteen);
        $this->assertEquals("PRIMARY KEY (`ID`),", $tcds_individualseventeen);
        $this->assertEquals("KEY `Triletter` (`Triletter`),", $tcds_individualeighteen);
        $this->assertEquals("CONSTRAINT `testtable_ibfk_1` FOREIGN KEY (`Triletter`) REFERENCES `Tri` (`Letter`)", $tcds_individualnineteen);
        $this->assertEquals(") ENGINE=InnoDB AUTO_INCREMENT=4080 DEFAULT CHARSET=latin1;", $tcds_individualtwenty);
        $this->assertEquals("/*!40101 SET character_set_client = @saved_cs_client */;", $tcds_individualtwentyone);
        $this->assertEquals("INSERT INTO `testtable` VALUES (1,'A','ABC', 1000);", $tcds_individualtwentytwo);
        $this->assertEquals("INSERT INTO `testtable` VALUES (2,'B','DEF', 2000);", $tcds_individualtwentythree);
        $this->assertEquals("INSERT INTO `testtable` VALUES (3,'C','GHI', 3000);", $tcds_individualtwentyfour);
        $this->assertEquals("INSERT INTO `testtable` VALUES (4,'D','JKL', 4000);", $tcds_individualtwentyfive);
        $this->assertEquals("INSERT INTO `testtable` VALUES (5,'E','MNO', 5000);", $tcds_individualtwentysix);
        $this->assertEquals("INSERT INTO `testtable` VALUES (6,'F','PQR', 6000); INSERT INTO `testtable` VALUES (7,'G','STU', 7000);", $tcds_individualtwentyseven);
        $this->assertEquals("LOCK TABLES `users` WRITE;", $tcds_individualtwentyeight);
        $this->assertEquals("/*!40000 ALTER TABLE `users` DISABLE KEYS */;", $tcds_individualtwentynine);
        $this->assertEquals("/*!40000 ALTER TABLE `users` ENABLE KEYS */;", $tcds_individualthirty);
        $this->assertEquals("UNLOCK TABLES;", $tcds_individualthirtyone);
    }
    public function testgetQueries() {
        function myTestGen() {
            $lines = array();
            $lines[0] = "CREATE TABLE `testtable` (";
            $lines[1] = "`ID` int(11) NOT NULL AUTO_INCREMENT,";
            $lines[2] = "`Letter` char(35) NOT NULL DEFAULT '',";
            $lines[3] = "`Triletter` char(3) NOT NULL DEFAULT '',";
            $lines[4] = "`Thousand` int(11) NOT NULL DEFAULT '0',";
            $lines[5] = "PRIMARY KEY (`ID`),";
            $lines[6] = "KEY `Triletter` (`Triletter`),";
            $lines[7] = "CONSTRAINT `testtable_ibfk_1` FOREIGN KEY (`Triletter`) REFERENCES `Tri` (`Letter`)";
            $lines[8] = ") ENGINE=InnoDB AUTO_INCREMENT=4080 DEFAULT CHARSET=latin1;";
            $lines[9] = "/*!40101 SET character_set_client = @saved_cs_client */;";
            $lines[10] = "INSERT INTO `testtable` VALUES (1,'A','ABC', 1000);";
            foreach($lines as $line) {
                yield trim($line);
            }
        }
        $qfl = Site_Command::getQueries(myTestGen());
        $q = iterator_to_array($qfl);
        $a = $q[0];
        $this->assertEquals("CREATE TABLE `testtable` (`ID` int(11) NOT NULL AUTO_INCREMENT,`Letter` char(35) NOT NULL DEFAULT '',`Triletter` char(3) NOT NULL DEFAULT '',`Thousand` int(11) NOT NULL DEFAULT '0',PRIMARY KEY (`ID`),KEY `Triletter` (`Triletter`),CONSTRAINT `testtable_ibfk_1` FOREIGN KEY (`Triletter`) REFERENCES `Tri` (`Letter`)) ENGINE=InnoDB AUTO_INCREMENT=4080 DEFAULT CHARSET=latin1;", $a[0]);
        $this->assertEquals(false, $a[1]);
        $b = $q[1];
        $this->assertEquals("/*!40101 SET character_set_client = @saved_cs_client */;", $b[0]);
        $this->assertEquals(false, $b[1]);
        $c = $q[2];
        $this->assertEquals("INSERT INTO `testtable` VALUES (1,'A','ABC', 1000);", $c[0]);
        $this->assertEquals(false, $c[1]);
    }

    public function testgetUnquotedParts() {
        $cases = array(
            '1;2;3' => array('1;', '2;', '3'),
            '1; \'2;3\'' => array('1;', ' \'2;3\''),
            '1; "2;3"' => array('1;', ' "2;3"'),
            '1;"2\';3"' => array('1;', '"2\';3"'),
            '1;`2;3`' => array('1;', '`2;3`'),
            '1;2;3;' => array('1;', '2;', '3;', ''),
        );

        foreach ($cases as $input => $expected_output) {
            $output = iterator_to_array(Site_Command::getUnquotedParts($input));
            $this->assertEquals($expected_output, $output);
        }
    }

    public function testgetCombined() {
        $comarr = Site_Command::getCombined(newTestGen());
        $combined = iterator_to_array($comarr);
        $a = $combined[0];
        $this->assertEquals('INSERT INTO `wp_bp_user_blogs` VALUES("2", "2", "1") , ("3", "4", "1");', $a[0]);
        $this->assertEquals(false, $a[1]);
        $a = $combined[1];
        $this->assertEquals("INSERT INTO `testtable` VALUES (3,'C','GHI', 3000) , (4,'D','JKL', 4000);", $a[0]);
        $this->assertEquals(false, $a[1]);
        $a = $combined[2];
        $this->assertEquals("INSERT INTO `testtable` VALUES (5,'E','MNO', 5000);", $a[0]);
        $this->assertEquals(false, $a[1]);
        $a = $combined[3];
        $this->assertEquals("INSERT INTO `testtable` VALUES (6,'F','PQR', 6000); INSERT INTO `testtable` VALUES (7,'G','STU', 7000);", $a[0]);
        $this->assertEquals(true, $a[1]);
        $a = $combined[4];
        $this->assertEquals('INSERT INTO `wp_term_relationships` VALUES("1051", "75", "0") , ("1041", "70", "0");', $a[0]);
        $this->assertEquals(false, $a[1]);

    }
}
