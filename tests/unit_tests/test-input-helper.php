<?php

use Terminus\Helpers\Input;

/**
 * Testing class for Terminus\Helpers\Input
 */
class InputHelperTest extends PHPUnit_Framework_TestCase {

  /**
   * @vcr input_helper_org_helpers
   */
  function testOrgHelpers() {
     $orglist = Input::orglist();
     $this->assertInternalType('array', $orglist);
     $this->assertArrayHasKey('-', $orglist);
     $this->assertArrayHasKey('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $orglist);

     // test normal usage
     $args = array('org' => 'Terminus Testing');
     $org = Input::orgname($args, 'org');
     $this->assertEquals('Terminus Testing', $org);

     // test case where an orgid is sent and a name should be returned
     $args = array('org' => 'd59379eb-0c23-429c-a7bc-ff51e0a960c2');
     $org = Input::orgname($args, 'org');
     $this->assertEquals('Terminus Testing', $org);

     // test case where an orgid is sent and a name should be returned
     $args = array('org' => 'd59379eb-0c23-429c-a7bc-ff51e0a960c2');
     $org = Input::orgid($args, 'org');
     $this->assertEquals('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $org);

     $args = array('org' => 'Terminus Testing');
     $org = Input::orgid($args, 'org');
     $this->assertEquals('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $org);
  }

}
