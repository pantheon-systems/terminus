<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\SSHKeys;
use Pantheon\Terminus\Models\SSHKey;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SSHKeysTest
 * Testing class for Pantheon\Terminus\Collections\SSHKeys
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class SSHKeysTest extends UserOwnedCollectionTest
{
    /**
     * @var string
     */
    protected $class = SSHKeys::class;
    /**
     * @var string
     */
    protected $url = 'users/USERID/keys';

    public function testAddKey()
    {
        $key = 'ssh-rsa AAAAB3xxx0uj+Q== dev@example.com';
        $file = tempnam(sys_get_temp_dir(), 'sshkey_');
        file_put_contents($file, $key . "\n");

        $this->request->expects($this->at(0))
            ->method('request')
            ->with(
                $this->url,
                [
                    'form_params' => $key,
                    'method' => 'post',
                ]
            );
        $this->collection->addKey($file);
        unlink($file);

        $this->setExpectedException(TerminusException::class);
        $this->collection->addKey($file);
    }

    public function testDeleteAll()
    {
        $this->request->expects($this->at(0))
            ->method('request')
            ->with($this->url, ['method' => 'delete']);

        $this->collection->deleteAll();
    }
    
    public function testFetch()
    {
        $data = [
            'a' => 'ssh-rsa AAAAB3xxx0uj+Q== dev@example.com',
            'b' => 'ssh-rsa AAAAB3xxx000+Q== dev2@example.com',
        ];
        $this->request->expects($this->once())
            ->method('request')
            ->with($this->url, ['options' => ['method' => 'get']])
            ->willReturn(['data' => $data]);


        $i = 0;
        $models = [];
        $options = ['collection' => $this->collection];
        foreach ($data as $id => $key) {
            $options['id'] = $id;
            $model_data = (object)['id' => $id, 'key' => $key];
            $model = $models[$id] = new SSHKey($model_data, $options);
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(SSHKey::class, [$model_data, $options])
                ->willReturn($model);
        }

        $this->collection->fetch();
        $this->assertEquals($models, $this->collection->all());
    }
}
