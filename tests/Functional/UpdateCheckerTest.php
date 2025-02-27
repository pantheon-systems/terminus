<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Update\UpdateChecker;

/**
 * Class UpdateCheckerTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class UpdateCheckerTest extends TerminusTestBase
{
    protected const CACHE_DIR = '/tmp/terminus-test-cache';
    
    /**
     * @test
     * @group short
     */
    public function testUpdateCheckerNotifiesWhenOutdated()
    {
        // Create a temporary cache directory for testing
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0777, true);
        }
        
        // Setup environment to control update check behavior
        $env = $this->env;
        $env['TERMINUS_CACHE_DIR'] = self::CACHE_DIR;
        $env['TERMINUS_TEST_OUTDATED_VERSION'] = '1';
        
        // Run the whoami command which triggers the update check
        [$output, $exitCode, $stderr] = static::callTerminus('auth:whoami', null, $env);
        
        // Verify update message appears in output
        $this->assertStringContainsString('A new Terminus version', $stderr);
        
        // Clean up
        $this->cleanUpTestCache();
    }
    
    /**
     * @test
     * @group short
     */
    public function testUpdateCheckerSilentWhenUpToDate()
    {
        // Create a temporary cache directory for testing
        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0777, true);
        }
        
        // Setup environment without the test flag
        $env = $this->env;
        $env['TERMINUS_CACHE_DIR'] = self::CACHE_DIR;
        
        // Run the whoami command which triggers the update check
        [$output, $exitCode, $stderr] = static::callTerminus('auth:whoami', null, $env);
        
        // Verify update message doesn't appear
        $this->assertStringNotContainsString('A new Terminus version', $stderr);
        
        // Clean up
        $this->cleanUpTestCache();
    }
    
    protected function cleanUpTestCache()
    {
        // Delete test cache directory
        if (is_dir(self::CACHE_DIR)) {
            $files = array_diff(scandir(self::CACHE_DIR), ['.', '..']);
            foreach ($files as $file) {
                unlink(self::CACHE_DIR . '/' . $file);
            }
            rmdir(self::CACHE_DIR);
        }
    }
}