<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Symfony\Component\Filesystem\Filesystem;

class LocalMachineHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TerminusConfig
     */
    /**
     * @var LocalMachineHelper
     */
    protected $local_machine;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->local_machine = new LocalMachineHelper();
        $this->local_machine->setConfig($this->config);
    }

    /**
     * Tests the LocalMachineHelper::exec($command) function
     */
    public function testExec()
    {
        $out = $this->local_machine->exec('ls');
        $this->assertEquals('0', $out['exit_code']);
    }

    /**
     * Tests the LocalMachineHelper::execInteractive($command) function
     */
    public function testExecInteractive()
    {
        $out = $this->local_machine->execInteractive('ls');
        $this->assertEquals('0', $out['exit_code']);
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
