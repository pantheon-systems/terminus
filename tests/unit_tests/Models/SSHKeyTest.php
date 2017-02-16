<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\SSHKeys;
use Pantheon\Terminus\Models\SSHKey;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\User;

/**
 * Class SSHKeyTest
 * Testing class for Pantheon\Terminus\Models\SSHKey
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SSHKeyTest extends ModelTestCase
{
    /**
     * @var SSHKeys
     */
    protected $collection;
    /**
     * @var object
     */
    protected $key_data;
    /**
     * @var SSHKey
     */
    protected $model;
    /**
     * @var User
     */
    protected $user;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(SSHKeys::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->id = 'user id';
        $this->key_data = (object)[
            'id' => '1234567890abcdef',
            'key' => 'ssh-rsa AAAAB3xxx0uj+Q== dev@example.com'
        ];

        $this->collection->method('getUser')->willReturn($this->user);

        $this->model = new SSHKey($this->key_data, ['collection' => $this->collection,]);
        $this->model->setRequest($this->request);
    }

    public function testDelete()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with("users/{$this->user->id}/keys/{$this->model->id}", ['method' => 'delete',])
            ->willReturn(['status_code' => 200,]);

        $out = $this->model->delete();
        $this->assertNull($out);
    }

    public function testDeleteFail()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with("users/{$this->user->id}/keys/{$this->model->id}", ['method' => 'delete',])
            ->willReturn(['status_code' => 404,]);

        $this->setExpectedException(TerminusException::class);

        $out = $this->model->delete();
        $this->assertNull($out);
    }

    public function testGetCommentHex()
    {
        $this->assertEquals(substr($this->key_data->id, 0, 2), substr($this->model->getHex(), 0, 2));
        $key_split = explode(' ', $this->key_data->key);
        $this->assertEquals(array_pop($key_split), $this->model->getComment());
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

        foreach ($keys as $i => $key_data) {
            $sshkey = new SSHKey(
                (object)$key_data,
                ['collection' => $this->collection]
            );
            $this->assertEquals($excpected[$i], $sshkey->serialize());
        }
    }
}
