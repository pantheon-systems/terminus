<?php


namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Env\ListCommand;
use Psr\Log\LoggerInterface;

/**
 * Class ListCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Env\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class ListCommandTest extends EnvCommandTest
{
    /**
     * @var array
     */
    protected $data;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = [
            'env_id' => ['id' => 'env_id', 'foo' => 'bar', 'baz' => 'bop',],
            'env_id_2' => ['id' => 'env_id_2', 'foo' => 'abc', 'baz' => 'def',],
        ];
        $this->environments->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($this->data);

        $this->command = new ListCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the env:list command
     */
    public function testListEnvs()
    {
        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(false);
        $this->logger->expects($this->never())
            ->method('warning');

        $out = $this->command->listEnvs('mysite');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($this->data, $out->getArrayCopy());
    }

    /**
     * Tests the env:list command when the site is frozen
     */
    public function testListFrozenEnvs()
    {
        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(true);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->equalTo('This site is frozen. Its test and live environments are unavailable.')
            );

        $out = $this->command->listEnvs('mysite');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($this->data, $out->getArrayCopy());
    }
}
