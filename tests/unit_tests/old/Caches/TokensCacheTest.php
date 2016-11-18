<?php

namespace Terminus\UnitTests\Caches;

use Pantheon\Terminus\Caches\TokensCache;
use Pantheon\Terminus\Config;
use Pantheon\Terminus\UnitTests\TerminusTest;

/**
 * Testing class for Terminus\Caches\TokensCache
 */
class TokensCacheTest extends TerminusTest
{
  /**
   * @var TokensCache
   */
    private $tokens_cache;
  /**
   * @var string
   */
    private $tokens_path;

    public function setUp()
    {
        parent::setUp();
        $this->tokens_cache = new TokensCache();
        $this->tokens_path = Config::get('tokens_dir');
    }

    public function testAdd()
    {
        $email     = 'fake@email.address';
        $file_name = $this->tokens_path . "/$email";
        exec("rm $file_name");
        $this->tokens_cache->add(compact('email'));
        $contents = $this->retrieveOutput($file_name);
        exec("rm $file_name");
        $this->assertTrue(strpos($contents, $email) !== false);
    }

    public function testFindByEmail()
    {
        $email  = 'fake@email.address';
        $this->tokens_cache->add(compact('email'));
        $contents = $this->tokens_cache->findByEmail($email);
        $this->assertEquals($contents['email'], $email);

        $file_name = $this->tokens_path . "/$email";
        exec("rm $file_name");
        try {
            $contents = $this->tokens_cache->findByEmail($email);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        $this->assertTrue(isset($message));
    }

    public function testGetAllSavedTokenEmails()
    {
        $email     = 'fake@email.address';
        $file_name = $this->tokens_path . "/$email";
        exec("rm $file_name");
        $emails = $this->tokens_cache->getAllSavedTokenEmails();
        $this->assertFalse(in_array($email, $emails));

        $this->tokens_cache->add(compact('email'));
        $emails = $this->tokens_cache->getAllSavedTokenEmails();
        $this->assertTrue(in_array($email, $emails));
    }

    public function testTokenExistsForEmail()
    {
        $email     = 'fake@email.address';
        $file_name = $this->tokens_path . "/$email";
        exec("rm $file_name");
        $this->assertFalse($this->tokens_cache->tokenExistsForEmail($email));

        $this->tokens_cache->add(compact('email'));
        $this->assertTrue($this->tokens_cache->tokenExistsForEmail($email));
        exec("rm $file_name");
    }
}
