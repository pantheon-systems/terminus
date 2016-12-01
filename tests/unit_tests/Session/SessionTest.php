<?php

namespace Pantheon\Terminus\UnitTests\Session;

use League\Container\Container;
use Robo\Config;
use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Models\User;

/**
 * Class SessionTest
 * Testing class for Pantheon\Terminus\Session\Session
 * @package Pantheon\Terminus\UnitTests\Session
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{

    protected $session;
    protected $filecache;

    protected function setUp()
    {
        $this->filecache = $this->getMockBuilder(FileStore::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = new Session($this->filecache);
    }

    /**
     * Test getting and setting data
     */
    public function testSetGet()
    {
        $data = [
            'foo' => 'bar',
            'abc' => 123
        ];
        $this->filecache->expects($this->once())
            ->method('get')
            ->with('session')
            ->willReturn($data);

        $this->session = new Session($this->filecache);

        $this->assertEquals('bar', $this->session->get('foo'));
        $this->assertEquals(123, $this->session->get('abc'));
        $this->assertEquals(null, $this->session->get('invalid'));
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
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User((object)array('id' => '123'));
        $container->expects($this->once())
            ->method('get')
            ->with(User::class, [(object)array('id' => '123')])
            ->willReturn($user);

        $this->filecache->expects($this->once())
            ->method('get')
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

    /**
     * Test destroying the session
     */
    public function testIsActive()
    {
        $data = [
            'user_id' => '1234',
            'session' => '12345',
            'expires_at' => time() + 100
        ];
        $this->filecache->expects($this->any())
            ->method('get')
            ->with('session')
            ->willReturn($data);

        $this->session = new Session($this->filecache);

        $this->assertEquals(true, $this->session->isActive());
    }

    /**
     * Test destroying the session
     */
    public function testIsNotActive()
    {
        $data = [
            'user_id' => '1234',
            'session' => '12345',
            'expires_at' => time() - 100
        ];
        $this->filecache->expects($this->once())
            ->method('get')
            ->with('session')
            ->willReturn($data);

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('test_mode')
            ->willReturn(false);
        $this->session = new Session($this->filecache);
        $this->session->setConfig($config);
        $this->assertEquals(false, $this->session->isActive());
    }

    /**
     * Test destroying the session
     */
    public function testIsActiveTestMode()
    {
        $data = [
            'user_id' => '1234',
            'session' => '12345',
            'expires_at' => time() - 100
        ];
        $this->filecache->expects($this->once())
            ->method('get')
            ->with('session')
            ->willReturn($data);

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('test_mode')
            ->willReturn(true);
        $this->session = new Session($this->filecache);
        $this->session->setConfig($config);
        $this->assertEquals(true, $this->session->isActive());
    }
}
