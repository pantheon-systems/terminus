<?php

namespace Pantheon\Terminus\UnitTests\Session;

use League\Container\Container;
use Pantheon\Terminus\Session\Session;
use Terminus\Caches\FileCache;
use Terminus\Models\User;

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
        //$this->assertEquals('baz', $this->session->get('foo'));
        //$this->assertEquals(123, $this->session->get('abc'));
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


        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User((object)array('id' => '123'));
        $container->expects($this->once())
            ->method('get')
            ->with(User::class, [(object)array('id' => '123')])
            ->willReturn($user);
        
        $this->filecache->expects($this->once())
        ->method('getData')
        ->with('session')
        ->willReturn(['user_id' => '123']);

        $this->session = new Session($this->filecache);
        $this->session->setContainer($container);

        $out = $this->session->getUser();
        $this->assertEquals($user, $out);
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
