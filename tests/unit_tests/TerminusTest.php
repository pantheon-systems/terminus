<?php

namespace Pantheon\Terminus;

use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use League\Container\ContainerInterface;
use Robo\Config;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TerminusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Robo\Config
     */
    protected $config;
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var Terminus
     */
    protected $terminus;

    const CURRENT_VERSION = '1.2.3';

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->input = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->output = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config = $this->getMockBuilder(Config::class)
            ->setMethods(['get',])
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('version'))
            ->willReturn(self::CURRENT_VERSION);
        $config->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('cache_dir'))
            ->willReturn(sys_get_temp_dir());
        $config->expects($this->at(2))
            ->method('get')
            ->with($this->equalTo('tokens_dir'))
            ->willReturn(sys_get_temp_dir());
        $config->expects($this->at(3))
            ->method('get')
            ->with($this->equalTo('plugins_dir'))
            ->willReturn(sys_get_temp_dir());
        $config->expects($this->at(4))
            ->method('get')
            ->with($this->equalTo('command_cache_dir'))
            ->willReturn(sys_get_temp_dir());
        $config->expects($this->at(5))
            ->method('get')
            ->with($this->equalTo('version'))
            ->willReturn(self::CURRENT_VERSION);
        $config->expects($this->at(6))
            ->method('get')
            ->with($this->equalTo('time_zone'))
            ->willReturn('UTC');
/*
        $this->terminus = new Terminus($config, $this->input, $this->output);

        // Setting a new config mock object so the counts won't start at 7
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->terminus->setConfig($this->config);
*/
    }

    /**
     * Tests Terminus::run($input, $output) when not using VCR
     */
    public function testRun()
    {
        $this->markTestIncomplete("Mocks need to be updated.");

        $this->config->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('vcr_cassette'))
            ->willReturn(null);
        $this->config->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('cache_dir'))
            ->willReturn(sys_get_temp_dir());

        $out = $this->terminus->run($this->input, $this->output);
        $this->equalTo(0, $out);
    }

    /**
     * Tests Terminus::run($input, $output) when using VCR
     */
    public function testRunWithVCR()
    {
        $this->markTestIncomplete("Mocks need to be updated.");

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete("Windows CI doesn't have the necessary extensions.");
        }

        $this->config->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('vcr_cassette'))
            ->willReturn('some_cassette');
        $this->config->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('vcr_mode'))
            ->willReturn('new_episodes');

        $out = $this->terminus->run($this->input, $this->output);
        $this->equalTo(0, $out);
    }
}
