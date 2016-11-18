<?php

namespace Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Commands\ArtCommand;
use Pantheon\Terminus\Caches\FileCache;
use Pantheon\Terminus\Helpers\UpdateHelper;
use Pantheon\Terminus\UnitTests\TerminusTest;

/**
 * Testing class for Terminus\Helpers\UpdateHelper
 */
class UpdateHelperTest extends TerminusTest
{

  /**
   * @var UpdateHelper
   */
    private $update_helper;

    public function setUp()
    {
        parent::setUp();
        $command = new ArtCommand(['runner' => $this->runner,]);
        $this->update_helper = new UpdateHelper(compact('command'));
    }

  /**
   * @vcr utils#checkCurrentVersion
   */
    public function testCheckCurrentVersion()
    {
        $current_version = $this->update_helper->getCurrentVersion();
        preg_match("/\d+\.\d+\.\d+/", $current_version, $matches);
        $this->assertEquals(count($matches), 1);
    }

  /**
   * @vcr utils#checkCurrentVersion
   */
    public function testCheckForUpdate()
    {
        $log_file = $this->log_file_name;
        $this->setOutputDestination($log_file);
        $cache = new FileCache();
        $cache->putData(
            'latest_release',
            ['check_date' => strtotime('8 days ago')]
        );
        $this->update_helper->checkForUpdate();
        $file_contents = explode("\n", file_get_contents($log_file));
        $this->assertFalse(
            strpos(array_pop($file_contents), 'An update to Terminus is available.')
        );
        $this->resetOutputDestination($log_file);
    }
}
