<?php

namespace Pantheon\Terminus\UnitTests\Session;

use Pantheon\Terminus\Session\Session;
use Terminus\Caches\FileCache;

/**
 * Testing class for Pantheon\Terminus\Session\Session
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{

    protected $session;
    protected $filecache;

    protected function setUp()
    {
        $this->filecache = $this->getMockBuilder(FileCache::class)
        ->disableOriginalConstructor()
        ->getMock();

        $this->session = new Session($this->filecache);
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
    public function testSetData()
    {
        $data = [
        'foo' => 'bar',
        'abc' => 123
        ];

        $this->filecache->expects($this->once())
        ->method('putData')
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
        $data = [
        'foo' => 'bar',
        'abc' => 123
        ];

        $this->filecache->expects($this->once())
        ->method('getData')
        ->with('session')
        ->willReturn(['user_uuid' => '123']);

        $this->session = new Session($this->filecache);

        // @TODO: Test mocking of new user (will require some sort of mockable factory rather than
        // the direct use of new User() in Session)
        $user = $this->session->getUser();
        $this->assertInstanceOf('Terminus\Models\User', $user);
    }

  /**
   * Test destroying the session
   */
    public function testDestroy()
    {
        $this->filecache->expects($this->once())
        ->method('remove')
        ->with('session');

        $this->session->destroy();
    }
}
