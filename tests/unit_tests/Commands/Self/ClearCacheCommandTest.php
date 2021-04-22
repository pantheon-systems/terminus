<?php

namespace Pantheon\Terminus\UnitTests\Commands\Self;

use League\Container\Container;
use Pantheon\Terminus\Commands\Self\ClearCacheCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Tests\Iterator\Iterator;

/**
 * Class ClearCacheCommandTest
 * Testing class for Pantheon\Terminus\Commands\Self\ClearCacheCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Self
 */
class ClearCacheCommandTest extends CommandTestCase
{
    /**
     * @var TerminusConfig
     */
    protected $config;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $local_machine = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->finder = $this->getMockBuilder(Finder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LocalMachineHelper::class))
            ->willReturn($local_machine);
        $local_machine->expects($this->once())
            ->method('getFilesystem')
            ->with()
            ->willReturn($this->filesystem);
        $local_machine->expects($this->once())
            ->method('getFinder')
            ->with()
            ->willReturn($this->finder);
        $this->finder->expects($this->once())
            ->method('files')
            ->with()
            ->willReturn($this->finder);

        $this->command = new ClearCacheCommand();
        $this->command->setLogger($this->logger);
        $this->command->setContainer($container);
        $this->command->setConfig($this->config);
    }

    /**
     * Tests the self:clear-cache command
     */
    public function testClearCache()
    {
        $dir_name = 'some dir';
        $dirs = ['dir1', 'dir2', 'dir3', 'dir4',];
        $iterator = new Iterator($dirs);

        $this->config->expects(($this->once()))
            ->method('get')
            ->with($this->equalTo('command_cache_dir'))
            ->willReturn($dir_name);
        $this->finder->expects($this->once())
            ->method('in')
            ->with($this->equalTo($dir_name))
            ->willReturn($this->finder);
        $this->finder->expects($this->once())
            ->method('getIterator')
            ->with()
            ->willReturn($iterator);

        for ($i = 0; $i < count($dirs); $i++) {
            $this->filesystem->expects($this->at($i))
                ->method('remove')
                ->with($this->equalTo($dirs[$i]));
        }

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('The local Terminus cache has been cleared.')
            );

        $out = $this->command->clearCache();
        $this->assertNull($out);
    }
}
