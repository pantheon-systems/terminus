<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Models\SshKey;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SshKeysTest
 * Testing class for Pantheon\Terminus\Collections\SshKeys
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class SshKeysTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/keys';
    protected $class = 'Pantheon\Terminus\Collections\SshKeys';

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
            $model = $models[$i] = new SshKey($model_data, $options);
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(SshKey::class, [$model_data, $options])
                ->willReturn($model);
        }

        $this->collection->fetch();
        $this->assertEquals($models, $this->collection->all());
    }
}
