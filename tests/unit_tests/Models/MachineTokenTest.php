<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\MachineTokens;
use Pantheon\Terminus\Models\MachineToken;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class MachineTokenTest
 * Testing class for Pantheon\Terminus\Models\MachineToken
 * @package Pantheon\Terminus\UnitTests\Models
 */
class MachineTokenTest extends ModelTestCase
{
    public function testDelete()
    {
        $collection = $this->getMockBuilder(MachineTokens::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);

        $mt = new MachineToken((object)['id' => '456'], ['collection' => $collection]);

        $this->request->expects($this->at(0))
            ->method('request')
            ->with("users/123/machine_tokens/456", ['method' => 'delete'])
            ->willReturn(['status_code' => 200]);

        $mt->setRequest($this->request);

        $mt->delete();
    }

    public function testDeleteFail()
    {
        $collection = $this->getMockBuilder(MachineTokens::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);

        $mt = new MachineToken((object)['id' => '456'], ['collection' => $collection]);

        $this->request->expects($this->at(0))
            ->method('request')
            ->with("users/123/machine_tokens/456", ['method' => 'delete'])
            ->willReturn(['status_code' => 404]);

        $mt->setRequest($this->request);

        $this->setExpectedException(TerminusException::class);
        
        $mt->delete();
    }
}
