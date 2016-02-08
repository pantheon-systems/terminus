<?php

use Terminus\Caches\TokensCache;

/**
 * Testing class for Terminus\Caches\TokensCache
 */
class TokensCacheTest extends PHPUnit_Framework_TestCase {

  /**
   * @var TokensCache
   */
  private $tokens_cache;

  public function __construct() {
    $this->tokens_cache = new TokensCache();
  }

  public function testAdd() {
    $email     = 'fake@email.address';
    $file_name = getenv('HOME') . "/.terminus/tokens/$email";
    exec("rm $file_name");
    $this->tokens_cache->add(compact('email'));
    $contents = retrieveOutput($file_name);
    exec("rm $file_name");
    $this->assertTrue(strpos($contents, $email) !== false);
  }

  public function testFindByEmail() {
    $email  = 'fake@email.address';
    $this->tokens_cache->add(compact('email'));
    $contents = $this->tokens_cache->findByEmail($email);
    $this->assertEquals($contents['email'], $email);

    $file_name = getenv('HOME') . "/.terminus/tokens/$email";
    exec("rm $file_name");
    try {
      $this->tokens_cache->findByEmail($email);
    } catch (\Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertTrue(isset($message));
  }

  public function testGetAllSavedTokenEmails() {
    $email     = 'fake@email.address';
    $file_name = getenv('HOME') . "/.terminus/tokens/$email";
    exec("rm $file_name");
    $emails = $this->tokens_cache->getAllSavedTokenEmails();
    $this->assertFalse(in_array($email, $emails));

    $this->tokens_cache->add(compact('email'));
    $emails = $this->tokens_cache->getAllSavedTokenEmails();
    $this->assertTrue(in_array($email, $emails));
  }

  public function testTokenExistsForEmail() {
    $email     = 'fake@email.address';
    $file_name = getenv('HOME') . "/.terminus/tokens/$email";
    exec("rm $file_name");
    $this->assertFalse($this->tokens_cache->tokenExistsForEmail($email));

    $this->tokens_cache->add(compact('email'));
    $this->assertTrue($this->tokens_cache->tokenExistsForEmail($email));
    exec("rm $file_name");
  }

}
