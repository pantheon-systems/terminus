<?php


namespace Pantheon\Terminus\FunctionalTests;


/**
 * Class EnvFunctionalTest
 *
 * @package Pantheon\Terminus\FunctionalTests
 */
class EnvFunctionalTest extends FunctionalTestBase {


    /**
     * @test
     * @testdox Test basic env:info command.
     */
    public function testEnvInfo() {
        $site = getenv('TERMINUS_SITE') ?: 'ci-wordpress-core';
        $output = $this->terminus("env:info {$site}.dev --format=yaml");`
        $this->assertTrue($output->isSuccess(), 
          "Command Should return clean status");
    }
    
    /**
     * @test
     */
     public function testEnvCreate() {
        $this->fail();
     }

    /**
     * @test
     */
     public function testEnvUpdate() {
        $this->fail();
     }
     
     /**
      * @test
      */
     public function testEnvDelete() {
        $this->fail();
     }
     
}
