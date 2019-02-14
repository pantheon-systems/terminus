<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\ProgressBars\ProcessProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;

class LocalMachineHelperTest extends \PHPUnit_Framework_TestCase
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
     * @var InputInterface
     */
    protected $input;
    /**
     * @var LocalMachineHelper
     */
    protected $local_machine;
    /**
     * @var ProcessProgressBar
     */
    protected $process_progress_bar;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->input = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->process_progress_bar = $this->getMockBuilder(ProcessProgressBar::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->method('get')
            ->with(ProcessProgressBar::class)
            ->willReturn($this->process_progress_bar);

        $this->local_machine = new LocalMachineHelper();
        $this->local_machine->setConfig($this->config);
        $this->local_machine->setInput($this->input);
        $this->local_machine->setContainer($this->container);
    }

    /**
     * Tests the LocalMachineHelper::exec($command) function
     */
    public function testExec()
    {
        $out = $this->local_machine->exec('ls');
        $this->assertEquals(0, $out['exit_code']);
    }

    /**
     * Tests the LocalMachineHelper::execute($command, $callback, $progress) function
     */
    public function testExecute()
    {
        $out = $this->local_machine->execute('ls');
        $this->assertEquals(0, $out['exit_code']);
    }

    /**
     * Tests the LocalMachineHelper::getFilesystem() function
     */
    public function testGetFilesystem()
    {
        $this->assertInstanceOf(Filesystem::class, $this->local_machine->getFilesystem());
    }

    /**
     * Tests the LocalMachineHelper::readFile() function
     */
    public function testReadFile()
    {
        $file_name = $this->getTestFileName();
        $content = 'file content';
        $this->expectFileOperations($file_name);
        file_put_contents($file_name, $content);
        $this->assertEquals($content, $this->local_machine->readFile($file_name));
    }

    /**
     * Tests the LocalMachineHelper::useTty() function when in interactive mode
     */
    public function testUseTtyInteractive()
    {
        $this->input->expects($this->once())
            ->method('isInteractive')
            ->with()
            ->willReturn(true);
        $useTty = $this->local_machine->useTty();
        $this->assertTrue(in_array($useTty, [false, null,]));
    }

    /**
     * Tests the LocalMachineHelper::useTty() function when not in interactive mode
     */
    public function testUseTtyNoninteractive()
    {
        $this->input->expects($this->once())
            ->method('isInteractive')
            ->with()
            ->willReturn(false);
        $this->assertFalse($this->local_machine->useTty());
    }

    /**
     * Tests the LocalMachineHelper::readFile() function
     */
    public function testWriteFile()
    {
        $file_name = $this->getTestFileName();
        $content = 'other file content';
        $this->expectFileOperations($file_name);
        $this->assertNull($this->local_machine->writeFile($file_name, $content));
        $this->assertEquals($content, file_get_contents($file_name));
    }

    protected function expectFileOperations($file_name)
    {
        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('user_home'))
            ->willReturn('~');
        $this->config->expects($this->once())
            ->method('fixDirectorySeparators')
            ->with($this->equalTo($file_name))
            ->willReturn($file_name);
    }

    /**
     * Returns a temporary file name
     *
     * @return string
     */
    protected function getTestFileName()
    {
        return tempnam(sys_get_temp_dir(), 'lmhtest_');
    }
}
