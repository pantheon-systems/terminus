<?php


namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\SshKeys;
use Pantheon\Terminus\Models\SshKey;
use Terminus\Exceptions\TerminusException;

class SshKeyTest extends ModelTestCase
{
    public function testDelete()
    {
        $collection = $this->getMockBuilder(SshKeys::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);
        $sshkey = new SshKey((object)['id' => '456'], ['collection' => $collection]);

        $this->request->expects($this->at(0))
            ->method('request')
            ->with("users/123/keys/456", ['method' => 'delete'])
            ->willReturn(['status_code' => 200]);

        $sshkey->setRequest($this->request);

        $sshkey->delete();
    }

    public function testDeleteFail()
    {
        $collection = $this->getMockBuilder(SshKeys::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);
        $sshkey = new SshKey((object)['id' => '456'], ['collection' => $collection]);

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
        $collection = $this->getMockBuilder(SshKeys::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('getUser')
            ->willReturn((object)['id' => '123']);
        $sshkey = new SshKey(
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
