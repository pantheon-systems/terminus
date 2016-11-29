<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\SSHKeys;
use Pantheon\Terminus\Models\SSHKey;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SSHKeyTest
 * Testing class for Pantheon\Terminus\Models\SSHKey
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SSHKeyTest extends ModelTestCase
{
    public function testDelete()
    {
        $collection = $this->getMockBuilder(SSHKeys::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);
        $sshkey = new SSHKey((object)['id' => '456'], ['collection' => $collection]);

        $this->request->expects($this->at(0))
            ->method('request')
            ->with("users/123/keys/456", ['method' => 'delete'])
            ->willReturn(['status_code' => 200]);

        $sshkey->setRequest($this->request);

        $sshkey->delete();
    }

    public function testDeleteFail()
    {
        $collection = $this->getMockBuilder(SSHKeys::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);
        $sshkey = new SSHKey((object)['id' => '456'], ['collection' => $collection]);

        $this->request->expects($this->at(0))
            ->method('request')
            ->with("users/123/keys/456", ['method' => 'delete'])
            ->willReturn(['status_code' => 404]);

        $sshkey->setRequest($this->request);

        $this->setExpectedException(TerminusException::class);

        $sshkey->delete();
    }

    public function testGetCommentHex()
    {
        $collection = $this->getMockBuilder(SSHKeys::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);
        $sshkey = new SSHKey(
            (object)[
                'id' => '1234567890abcdef',
                'key' => 'ssh-rsa AAAAB3xxx0uj+Q== dev@example.com'
            ],
            ['collection' => $collection]
        );

        $this->assertEquals('12:34:56:78:90:ab:cd:ef', $sshkey->getHex());
        $this->assertEquals('dev@example.com', $sshkey->getComment());
    }
}
