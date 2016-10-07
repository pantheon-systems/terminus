<?php
namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Commands\MachineToken\ListCommand;
use Pantheon\Terminus\Config;
use Terminus\Collections\MachineTokens;
use Terminus\Models\MachineToken;

/**
 * Testing class for Pantheon\Terminus\Commands\Auth\LoginCommand
 */
class MachineTokensListCommandTest extends MachineTokenCommandTest
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new ListCommand(new Config());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the machine-token:list command when there are no tokens.
     *
     * @return void
     */
    public function testMachineTokenListEmpty()
    {
        $this->machine_tokens->method('all')
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('log')
            ->with($this->equalTo('warning'), $this->equalTo('You have no machine tokens.'));

        $out = $this->command->listTokens();
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([], $out->getArrayCopy());
    }

    /**
     * Tests the machine-token:list command when there are tokens.
     *
     * @return void
     */
    public function testMachineTokenListNotEmpty()
    {
        $tokens = [
            ['id' => '1', 'device_name' => 'Foo'],
            ['id' => '2', 'device_name' => 'Bar']
        ];
        $collection = new MachineTokens(['user' => $this->user]);
        $this->machine_tokens->method('all')
            ->willReturn([
                new MachineToken((object)$tokens[0], ['collection' => $collection]),
                new MachineToken((object)$tokens[1], ['collection' => $collection])
            ]);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $out = $this->command->listTokens();
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals($tokens, $out->getArrayCopy());
    }
}
