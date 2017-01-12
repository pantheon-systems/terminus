<?php

namespace Pantheon\Terminus\UnitTests\Update;

use Consolidation\Log\Logger;
use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Update\LatestRelease;
use Pantheon\Terminus\Update\UpdateChecker;

class LatestReleaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var DataStoreInterface
     */
    protected $data_store;
    /**
     * @var LatestRelease
     */
    protected $latest_release;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var Request
     */
    protected $request;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data_store = $this->getMockBuilder(DataStoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->latest_release = new LatestRelease($this->data_store);
        $this->latest_release->setContainer($this->container);
        $this->latest_release->setLogger($this->logger);
        $this->latest_release->setRequest($this->request);
    }

    /**
     * Tests running get($string) when there isn't a saved version file
     */
    public function testFirstTime()
    {
        $version = '1.0.0-beta.2';

        $this->data_store->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LatestRelease::SAVE_FILE))
            ->willReturn(null);
        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo(LatestRelease::UPDATE_URL))
            ->willReturn(['data' => (object)['name' => $version,],]);
        $this->data_store->expects($this->once())
            ->method('set');
        $this->logger->expects($this->never())
            ->method('debug');

        $out = $this->latest_release->get('version');
        $this->assertEquals($out, $version);
    }

    /**
     * Tests running get($string) when unable to check for new versions
     */
    public function testCannotCheckGithub()
    {
        $version = '1.0.0-beta.2';
        $check_date = strtotime('-' . LatestRelease::TIME_BETWEEN_CHECKS) - 999999;
        $data = (object)['version' => $version, 'check_date' => $check_date,];
        $message = 'exception message';

        $this->data_store->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LatestRelease::SAVE_FILE))
            ->willReturn($data);
        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo(LatestRelease::UPDATE_URL))
            ->will($this->throwException(new \Exception($message)));
        $this->data_store->expects($this->never())
            ->method('set');
        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo("Terminus was unable to check the latest release version number.\n{message}"),
                $this->equalTo(compact('message'))
            );

        $out = $this->latest_release->get('version');
        $this->assertEquals($out, $version);
    }

    /**
     * Tests running get($string) when the version was already checked recently
     */
    public function testCheckedRecently()
    {
        $version = '1.0.0-beta.2';
        $check_date = time();
        $data = (object)['version' => $version, 'check_date' => $check_date,];

        $this->data_store->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LatestRelease::SAVE_FILE))
            ->willReturn($data);
        $this->request->expects($this->never())
            ->method('request');
        $this->data_store->expects($this->never())
            ->method('set');
        $this->logger->expects($this->never())
            ->method('debug');

        $out = $this->latest_release->get('version');
        $this->assertEquals($out, $version);
    }

    /**
     * Tests when trying to retrieve an attribute that doesn't exist
     */
    public function testGetInvalidAttribute()
    {
        $version = '1.0.0-beta.2';
        $check_date = time();
        $data = (object)['version' => $version, 'check_date' => $check_date,];

        $this->data_store->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LatestRelease::SAVE_FILE))
            ->willReturn($data);
        $this->request->expects($this->never())
            ->method('request');
        $this->data_store->expects($this->never())
            ->method('set');
        $this->logger->expects($this->never())
            ->method('debug');

        $this->setExpectedException(TerminusNotFoundException::class, 'There is no attribute called invalid.');

        $out = $this->latest_release->get('invalid');
        $this->assertNull($out);
    }
}
