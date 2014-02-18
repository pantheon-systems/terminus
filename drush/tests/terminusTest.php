<?php
/**
 * @file
 * PHPUnit Tests for terminus helper functions using Drush's test framework.
 */

class terminusTest extends Drush_UnitTestCase {

  public function __construct() {
    parent::__construct();
    // Load Terminus.
    require_once __DIR__ . '/../terminus.drush.inc';

    $this->testData = array(
      'valid_env_name' => '123-env',
      'invalid_env_characters' => '#$%^',
      'invalid_env_long' => '123-enviornment',
      'reserved_branch_names' => array(
        'dev',
        'test',
        'live',
        'master',
      ),
      'valid_branch_name' => 'branch',
      'valid_uuid' => 'a1b2c3d4-a1b2-a1b2-a1b2-a1b2c3d4e5f6',
      'invalid_uuid' => 'invalid-uuid'
    );
  }

  public function testTerminusValidateEnvironment() {
    // Test valid environment name
    $this->assertTrue(
      terminus_validate_environment($this->testData['valid_env_name'])
    );

    // Test failure on invalid characters in environment name
    $this->assertFalse(
      terminus_validate_environment($this->testData['invalid_env_characters'])
    );

    // Test failure on environment name longer than 11 characters
    $this->assertFalse(
      terminus_validate_environment($this->testData['invalid_env_long'])
    );
  }

  public function testTerminusReservedBranch() {
    // Test non reserved branch name
    $this->assertFalse(
      terminus_is_reserved_branch_name($this->testData['valid_branch_name'])
    );

    // Test against reserved names
    foreach ($this->testData['reserved_branch_names'] as $reserved_name) {
      $this->assertTrue(
        terminus_is_reserved_branch_name($reserved_name)
      );
    }
  }

  public function testTerminusValidateBranch() {
    // Test valid branch name
    $this->assertTrue(
      terminus_validate_new_branch_name($this->testData['valid_branch_name'])
    );

    // Test invalid branch names
    $this->assertFalse(
      terminus_validate_new_branch_name($this->testData['invalid_env_characters'])
    );
    $this->assertFalse(
      terminus_validate_new_branch_name($this->testData['invalid_env_long'])
    );

    // Test invalid environment names
    $this->assertTrue(
      terminus_validate_new_branch_name($this->testData['valid_env_name'])
    );
    $this->assertFalse(
      terminus_validate_new_branch_name($this->testData['invalid_env_characters'])
    );
    $this->assertFalse(
      terminus_validate_new_branch_name($this->testData['invalid_env_long'])
    );
  }

  public function testTerminusValidateUUID() {
    // Test valid UUID
    $this->assertTrue(
      terminus_validate_uuid($this->testData['valid_uuid'])
    );

    // Test invalid UUID
    $this->assertFalse(
      terminus_validate_uuid($this->testData['invalid_uuid'])
    );
  }

}

