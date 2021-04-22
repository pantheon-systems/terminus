<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SavedTokenTest
 * Testing class for Pantheon\Terminus\Models\SavedToken
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SavedTokenTest extends ModelTestCase
{
    /**
     * @var FileStore
     */
    protected $data_store;
    /**
     * @var Token
     */
    protected $token;
    /**
     * @var array
     */
    protected $token_data;

    public function setUp()
    {
        parent::setUp();

        $this->data_store = $this->getMockBuilder(FileStore::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->token_data = (object)['email' => 'dev@example.com', 'token' => '123',];

        $this->token = new SavedToken($this->token_data);
        $this->token ->setDataStore($this->data_store);
    }

    public function testConstruct()
    {
        $this->assertEquals('dev@example.com', $this->token->id);
    }

    public function testLogIn()
    {
        $session_data = ['session' => '123', 'expires_at' => 12345];
        $this->request->expects($this->once())
            ->method('request')
            ->with('authorize/machine-token', [
                'form_params' => ['machine_token' => $this->token_data->token, 'client' => 'terminus',],
                'method' => 'post',
            ])
            ->willReturn(['data' => (object)$session_data,]);

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('setData')
            ->with($session_data);

        $user = new User();
        $session->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->token->setRequest($this->request);
        $this->token->setSession($session);
        $out = $this->token->logIn();
        $this->assertEquals($user, $out);
    }

    public function testSaveToDir()
    {
        $this->data_store->expects($this->once())
            ->method('set')
            ->with(
                $this->token_data->email,
                (object)[
                    'email' => $this->token_data->email,
                    'id' => $this->token_data->email,
                    'token' => $this->token_data->token,
                    'date' => time()
                ]
            );

        $this->token->saveToDir();
    }

    public function testDelete()
    {
        $this->data_store->expects($this->once())
            ->method('remove')
            ->with($this->token_data->email);

        $this->token->delete();
    }

    public function testGetReferences()
    {
        $this->assertEquals([$this->token_data->email, $this->token_data->token,], $this->token->getReferences());
    }

    public function testInvalidID()
    {
        $this->token = new SavedToken((object)['email' => '', 'token' => $this->token_data->token,]);
        $this->token ->setDataStore($this->data_store);

        $this->data_store->expects($this->never())
            ->method('remove');

        $this->setExpectedException(
            TerminusException::class,
            'Could not save the machine token because it is missing an ID'
        );

        $this->token->saveToDir();
    }
}
