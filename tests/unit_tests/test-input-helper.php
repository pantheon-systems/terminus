<?php

use Terminus\Helpers\Input;

/**
 * Testing class for Terminus\Helpers\Input
 */
class InputHelperTest extends PHPUnit_Framework_TestCase {

  function testMenuSingleOptionReturningValue() {
    $only_option = Input::menu(
      array('choices' => array(5), 'return_value' => true)
    );
    $this->assertInternalType('integer', $only_option);
    $this->assertEquals(5, $only_option);
  }

  function testMenuSingleOptionReturningIndex() {
    $only_option_index = Input::menu(array('choices' => array('Pick me!')));
    $this->assertInternalType('integer', $only_option_index);
    $this->assertEquals(0, $only_option_index);
  }

  function testOptionalFindsKey() {
    $option = Input::optional(
      array(
        'key'     => 'key',
        'choices' => array('key' => 'value', 'not' => 'me'),
        'default' => true,
      )
    );
    $this->assertInternalType('string', $option);
    $this->assertEquals('value', $option);
  }

  function testOptionalReturnsDefault() {
    $default = Input::optional(
      array(
        'key'     => 'key',
        'choices' => array('not' => 'me'),
        'default' => true,
      )
    );
    $this->assertInternalType('bool', $default);
    $this->assertEquals(true, $default);
  }

  function testOptionalReturnsDefaultFromFunction() {
    $default_null = Input::optional(
      array(
        'key'     => 'key',
        'choices' => array('not' => 'me'),
      )
    );
    $this->assertEquals(null, $default_null);
  }

  /**
   * @vcr input_helper_org_helpers
   */
  function testOrgList() {
    $org_list = Input::orgList();
    $this->assertInternalType('array', $org_list);
    $this->assertArrayHasKey('-', $org_list);
    $this->assertArrayHasKey('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $org_list);
  }

  /**
   * @vcr input_helper_org_helpers
   */
  function testOrgNameAcceptsName() {
    $args = array('org' => 'Terminus Testing');
    $org  = Input::orgName($args, 'org');
    $this->assertEquals('Terminus Testing', $org);
  }

  /**
   * @vcr input_helper_org_helpers
   */
  function testOrgNameAcceptsUuid() {
    $args = array('org' => 'd59379eb-0c23-429c-a7bc-ff51e0a960c2');
    $org  = Input::orgName($args, 'org');
    $this->assertEquals('Terminus Testing', $org);
  }

  /**
   * @vcr input_helper_org_helpers
   */
  function testOrgIdAcceptsUuid() {
    $args = array('org' => 'd59379eb-0c23-429c-a7bc-ff51e0a960c2');
    $org  = Input::orgId(compact('args'));
    $this->assertEquals('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $org);
  }

  /**
   * @vcr input_helper_org_helpers
   */
  function testOrgIdAcceptsName() {
    $args = array('org' => 'Terminus Testing');
    $org  = Input::orgId(compact('args'));
    $this->assertEquals('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $org);
  }

}
