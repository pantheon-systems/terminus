<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\MachineTokens;
use Pantheon\Terminus\Models\MachineToken;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\User;

/**
 * Class MachineTokenTest
 * Testing class for Pantheon\Terminus\Models\MachineToken
 * @package Pantheon\Terminus\UnitTests\Models
 */
class MachineTokenTest extends ModelTestCase
{
    /**
     * @var MachineTokens
     */
    protected $collection;
    /**
     * @var MachineToken
     */
    protected $model;

    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(MachineTokens::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new MachineToken((object)['id' => 'token_id',], ['collection' => $this->collection,]);
        $this->model->setRequest($this->request);
    }

    public function testDelete()
    {
        $user = $this->expectGetUser();
        $this->request->expects($this->once())
            ->method('request')
            ->with("users/{$user->id}/machine_tokens/{$this->model->id}", ['method' => 'delete',])
            ->willReturn(['status_code' => 200,]);

        $out = $this->model->delete();
        $this->assertNull($out);
    }

    public function testDeleteFail()
    {
        $user = $this->expectGetUser();
        $this->request->expects($this->once())
            ->method('request')
            ->with("users/{$user->id}/machine_tokens/{$this->model->id}", ['method' => 'delete',])
            ->willReturn(['status_code' => 404,]);

        $this->setExpectedException(TerminusException::class);

        $out = $this->model->delete();
        $this->assertNull($out);
    }

    /**
     * Prepares the test case for the getUser() function.
     *
     * @return User The user object getUser() will return
     */
    protected function expectGetUser()
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->id = 'user ID';
        $this->collection->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);
        return $user;
    }
}
