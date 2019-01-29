<?php

namespace Pantheon\Terminus\UnitTests\Update;

use Consolidation\Log\Logger;
use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Update\LatestRelease;
use Pantheon\Terminus\Update\UpdateChecker;

/**
 * Class UpdateCheckerTest
 * Testing class for Pantheon\Terminus\Update\UpdateChecker
 * @package Pantheon\Terminus\UnitTests\Update
 */
class UpdateCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TerminusConfig
     */
    protected $config;
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
     * @var UpdateChecker
     */
    protected $update_checker;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data_store = $this->getMockBuilder(DataStoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->latest_release = $this->getMockBuilder(LatestRelease::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->update_checker = new UpdateChecker($this->data_store);
        $this->update_checker->setConfig($this->config);
        $this->update_checker->setContainer($this->container);
        $this->update_checker->setLogger($this->logger);
    }

    /**
     * Tests the run function when the client is up-to-date
     */
    public function testClientIsUpToDate()
    {
        $running_version_num = '1.0.0-beta.2';
        $latest_version_num = '1.0.0-beta.2';
        $hide_update_message = null;

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['version'], ['hide_update_message'])
            ->willReturnOnConsecutiveCalls($running_version_num, $hide_update_message);
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(LatestRelease::class),
                $this->equalTo([$this->data_store,])
            )
            ->willReturn($this->latest_release);
        $this->latest_release->expects($this->once())
            ->method('get')
            ->with($this->equalTo('version'))
            ->willReturn($latest_version_num);
        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->never())
            ->method('debug');

        $this->update_checker->setCheckForUpdates(true);
        $out = $this->update_checker->run();
        $this->assertNull($out);
    }

    /**
     * Tests the run function when the client is out-of-date
     */
    public function testClientIsOutOfDate()
    {
        $running_version_num = '1.0.0-beta.1';
        $latest_version_num = '1.0.0-beta.2';
        $hide_update_message = null;

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['version'], ['hide_update_message'])
            ->willReturnOnConsecutiveCalls($running_version_num, $hide_update_message);
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(LatestRelease::class),
                $this->equalTo([$this->data_store,])
            )
            ->willReturn($this->latest_release);
        $this->latest_release->expects($this->once())
            ->method('get')
            ->with($this->equalTo('version'))
            ->willReturn($latest_version_num);
        $this->logger->expects($this->once())
            ->method('notice');
        $this->logger->expects($this->never())
            ->method('debug');

        $this->update_checker->setCheckForUpdates(true);
        $out = $this->update_checker->run();
        $this->assertNull($out);
    }

    /**
     * Tests the run function when the client is out-of-date, but the update
     * message is configured to be hidden.
     */
    public function testClientIsOutOfDateButHideMessage()
    {
        $running_version_num = '1.0.0-beta.1';
        $latest_version_num = '1.0.0-beta.2';
        $hide_update_message = '1';

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['version'], ['hide_update_message'])
            ->willReturnOnConsecutiveCalls($running_version_num, $hide_update_message);
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(LatestRelease::class),
                $this->equalTo([$this->data_store,])
            )
           ->willReturn($this->latest_release);
        $this->latest_release->expects($this->once())
            ->method('get')
            ->with($this->equalTo('version'))
            ->willReturn($latest_version_num);
        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->never())
            ->method('debug');

        $this->update_checker->setCheckForUpdates(true);
        $out = $this->update_checker->run();
        $this->assertNull($out);
    }

    /**
     * Tests the run function when Github release data is unavailable
     */
    public function testCannotCheckVersion()
    {
        $running_version_num = '1.0.0-beta.2';

        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('version'))
            ->willReturn($running_version_num);
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(LatestRelease::class),
                $this->equalTo([$this->data_store,])
            )
            ->willReturn($this->latest_release);
        $this->latest_release->expects($this->once())
            ->method('get')
            ->with($this->equalTo('version'))
            ->will($this->throwException(new TerminusNotFoundException()));
        $this->logger->expects($this->never())
            ->method('notice');
        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('Terminus has no saved release information.')
            );

        $this->update_checker->setCheckForUpdates(true);
        $out = $this->update_checker->run();
        $this->assertNull($out);
    }

    /**
     * Ensures that the checker does not run when inappropriate and that the
     * state can be changed by using setCheckForUpdates
     */
    public function testShouldCheckForUpdates()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped("Windows CI doesn't have the necessary extensions.");
        }

        $running_version_num = '1.0.0-beta.2';

        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('version'))
            ->willReturn($running_version_num);
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(LatestRelease::class),
                $this->equalTo([$this->data_store,])
            )
            ->willReturn($this->latest_release);
        $this->latest_release->expects($this->once())
            ->method('get')
            ->with($this->equalTo('version'))
            ->will($this->throwException(new TerminusNotFoundException()));
        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('Terminus has no saved release information.')
            );
        $out = $this->update_checker->run();
        $this->assertNull($out);

        $this->update_checker->setCheckForUpdates(true);
        $out = $this->update_checker->run();
        $this->assertNull($out);
    }
}
