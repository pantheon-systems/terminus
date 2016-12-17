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

    public function testSerialize()
    {
        $keys = [
            '79e7e210bdf335bb8651a46b9a8417ab' => [
                'id' => '79e7e210bdf335bb8651a46b9a8417ab',
                'key' => 'ssh-rsa xxxxxxx dev@foo.bar',
            ],
            '27a7a11ab9d2acbf91063410546ef980' => [
                'id' => '27a7a11ab9d2acbf91063410546ef980',
                'key' => 'ssh-rsa yyyyyyy dev@baz.bar',
            ]
        ];
        $excpected = [
            '79e7e210bdf335bb8651a46b9a8417ab' => [
                'id' => '79e7e210bdf335bb8651a46b9a8417ab',
                'hex' => '79:e7:e2:10:bd:f3:35:bb:86:51:a4:6b:9a:84:17:ab',
                'comment' => 'dev@foo.bar'
            ],
            '27a7a11ab9d2acbf91063410546ef980' => [
                'id' => '27a7a11ab9d2acbf91063410546ef980',
                'hex' => '27:a7:a1:1a:b9:d2:ac:bf:91:06:34:10:54:6e:f9:80',
                'comment' => 'dev@baz.bar'
            ]
        ];
        $collection = $this->getMockBuilder(SSHKeys::class)
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($keys as $i => $key_data) {
            $sshkey = new SSHKey(
                (object)$key_data,
                ['collection' => $collection]
            );
            $this->assertEquals($excpected[$i], $sshkey->serialize());
        }
    }
}
