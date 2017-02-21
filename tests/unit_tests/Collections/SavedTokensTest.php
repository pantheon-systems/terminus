<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use League\Container\Container;
use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Models\User;

class SavedTokensTest extends CollectionTestCase
{
    /**
     * @var TerminusConfig
     */
    protected $config;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var FileStore
     */
    protected $data_store;
    /**
     * @var SavedToken
     */
    protected $token;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data_store = $this->getMockBuilder(FileStore::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->token = $this->getMockBuilder(SavedToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = new SavedTokens();
        $this->collection->setConfig($this->config);
        $this->collection->setContainer($this->container);
        $this->collection->setDataStore($this->data_store);
    }

    public function testAdd()
    {
        $user_id = 'user id';
        $model_data = (object)['email' => 'some@email.ext', 'id' => $user_id,];

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(SavedToken::class),
                $this->equalTo([$model_data, ['id' => $user_id, 'collection' => $this->collection,],])
            )
            ->willReturn($this->token);
        $this->token->expects($this->once())
            ->method('setDataStore')
            ->with($this->equalTo($this->data_store));

        $out = $this->collection->add($model_data);
        $this->assertEquals($this->token, $out);
    }

    public function testCreate()
    {
        $token_string = 'token string';
        $email = 'some@email.ext';
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->token->id = $email;

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(SavedToken::class),
                $this->equalTo([(object)['token' => $token_string,], ['collection' => $this->collection,],])
            )
            ->willReturn($this->token);
        $this->token->expects($this->once())
            ->method('setDataStore')
            ->with($this->equalTo($this->data_store));
        $this->token->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn([$this->token->id, $token_string,]);
        $this->token->expects($this->once())
            ->method('logIn')
            ->with()
            ->willReturn($user);
        $user->expects($this->once())
            ->method('fetch')
            ->with();
        $user->expects($this->once())
            ->method('get')
            ->with($this->equalTo('email'))
            ->willReturn($email);
        $this->token->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo('email'),
                $this->equalTo($email)
            );
        $this->token->expects($this->once())
            ->method('saveToDir')
            ->with();

        $out = $this->collection->create($token_string);
        $this->assertNull($out);
        $this->assertNotNull($this->collection->get($email));
    }

    public function testDeleteAll()
    {
        $this->makeTokensFetchable();

        $this->token->expects($this->once())
            ->method('delete')
            ->with();

        $out = $this->collection->deleteAll();
        $this->assertNull($out);
    }

    protected function makeTokensFetchable()
    {
        $email = 'some@email.ext';
        $token = '111111111111111111111111111111111111111111111';
        $model_data = (object)['email' => $email, 'token' => $token, 'date' => time(),];

        $this->data_store->expects($this->once())
            ->method('keys')
            ->with()
            ->willReturn([$email,]);
        $this->data_store->expects($this->once())
            ->method('get')
            ->with($email)
            ->willReturn($model_data);
        $this->container->expects($this->once())
            ->method('get')
            ->willReturn($this->token);
    }
}
