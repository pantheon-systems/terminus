<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use League\Container\Container;
use Pantheon\Terminus\Commands\ArtCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ArtCommandTest
 * Testing class for Pantheon\Terminus\Commands\ArtCommand
 * @package Pantheon\Terminus\UnitTests\Commands
 */
class ArtCommandTest extends CommandTestCase
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
     * @var string
     */
    protected $filepath = 'file_path';
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var LocalMachineHelper
     */
    protected $local_machine_helper;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->local_machine_helper = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->output = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('assets_dir'))
            ->willReturn($this->filepath);
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LocalMachineHelper::class))
            ->willReturn($this->local_machine_helper);
        $this->local_machine_helper->expects($this->once())
            ->method('getFilesystem')
            ->with()
            ->willReturn($this->filesystem);

        $this->command = new ArtCommand();
        $this->command->setConfig($this->config);
        $this->command->setOutput($this->output);
        $this->command->setContainer($this->container);
    }

    /**
     * Tests the art command
     */
    public function testArt()
    {
        $artwork = 'some art';
        $name = 'hello';
        $path = "{$this->filepath}/$name.txt";

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($this->equalTo($path))
            ->willReturn(true);
        $this->local_machine_helper->expects($this->once())
            ->method('readFile')
            ->with($this->equalTo($path))
            ->willReturn($artwork);

        $out = $this->command->art($name);
        $this->assertInternalType('string', $out);
    }

    /**
     * Tests the art command when displaying a random artwork
     */
    public function testArtRandom()
    {
        $artwork = 'some art';

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($this->stringContains($this->filepath))
            ->willReturn(true);
        $this->local_machine_helper->expects($this->once())
            ->method('readFile')
            ->with($this->stringContains($this->filepath))
            ->willReturn($artwork);

        $out = $this->command->art();
        $this->assertInternalType('string', $out);
    }

    /**
     * Tests the art command when the requested artwork DNE
     */
    public function testArtDNE()
    {
        $name = 'invalid';

        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($this->stringContains($this->filepath))
            ->willReturn(false);
        $this->local_machine_helper->expects($this->never())
            ->method('readFile');

        $this->setExpectedException(
            TerminusNotFoundException::class,
            "There is no source for the requested $name artwork."
        );

        $out = $this->command->art($name);
        $this->assertNull($out);
    }
}
