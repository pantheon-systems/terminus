<?php

namespace Pantheon\Terminus\UnitTests\Session;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Session\Session;

/**
 * Testing class for Pantheon\Terminus\Session\Session
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{

    protected $session;
    protected $filestore;


    protected function setUp()
    {
        $this->filestore = $this->getMockBuilder(FileStore::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->session = new Session($this->filestore);
    }

  /**
   * Test getting and setting data
   */
    public function testSetGet()
    {
        $this->session->set('foo', 'bar');
        $this->session->set('abc', 123);
        $this->session->set('foo', 'baz');

        $this->assertEquals('baz', $this->session->get('foo'));
        $this->assertEquals(123, $this->session->get('abc'));
    }

    /**
     * Test getting and setting data
     */
    public function testWrite()
    {
        $data = [
            'foo' => 'bar',
            'abc' => 123
        ];

        $this->filestore->expects($this->once())
            ->method('set')
            ->with('session', $data);

        foreach ($data as $key => $val) {
            $this->session->set($key, $val);
        }
        $this->session->write();
    }

    /**
     * Test getting and setting data
     */
    public function testSetData()
    {
        $data = [
        'foo' => 'bar',
        'abc' => 123
        ];

        $this->filestore->expects($this->once())
            ->method('set')
            ->with('session', $data);

        $this->session->setData($data);

        foreach ($data as $key => $val) {
            $this->assertEquals($val, $this->session->get($key));
        }
    }

  /**
   * Test getting and setting data
   */
    public function testGetUser()
    {
        $this->filestore->expects($this->once())
            ->method('get')
            ->with('session')
            ->willReturn(['user_id' => '123']);

        $this->session = new Session($this->filestore);
        $this->session->setConfig(new Config());

        // @TODO: Test mocking of new user (will require some sort of mockable factory rather than
        // the direct use of new User() in Session)
        $user = $this->session->getUser();
        $this->assertInstanceOf('Terminus\Models\User', $user);
        $this->assertEquals('123', $user->get('id'));
    }

  /**
   * Test destroying the session
   */
    public function testDestroy()
    {
        $this->filestore->expects($this->once())
            ->method('remove')
            ->with('session');

        $this->session->destroy();
    }

    public function testEnsureLogin()
    {
    }

    public function testLoggedIn()
    {
    }

    public function testLogInViaSavedEmailMachineToken()
    {
    }

    public function testLogInViaMachineToken()
    {
    }

    public function testLogOut()
    {
    }

    public function testGetAllSavedTokenEmails()
    {
    }

    public function testGetMachineTokenCreationUrl()
    {
    }


}
