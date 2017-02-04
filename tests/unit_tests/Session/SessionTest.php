<?php

namespace Pantheon\Terminus\UnitTests\Session;

use Behat\Gherkin\Cache\FileCache;
use League\Container\Container;
use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Models\SavedToken;
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
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var FileStore
     */
    protected $filecache;
    /**
     * @var Session
     */
    protected $session;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filecache = $this->getMockBuilder(FileStore::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test destroying the session
     */
    public function testDestroy()
    {
        $this->filecache->expects($this->once())
            ->method('remove')
            ->with($this->equalTo('session'));

        $this->session = new Session($this->filecache);
        $out = $this->session->destroy();
        $this->assertNull($out);
    }

    /**
     * Test destroying the session
     */
    public function testIsActive()
    {
        $data = [
            'user_id' => '1234',
            'session' => '12345',
            'expires_at' => time() + 100,
        ];

        $this->filecache->expects($this->any())
            ->method('get')
            ->with($this->equalTo('session'))
            ->willReturn($data);

        $this->session = new Session($this->filecache);
        $this->assertTrue($this->session->isActive());
    }

    /**
     * Test destroying the session
     */
    public function testIsActiveTestMode()
    {
        $data = [
            'user_id' => '1234',
            'session' => '12345',
            'expires_at' => time() - 100,
        ];

        $this->filecache->expects($this->once())
            ->method('get')
            ->with($this->equalTo('session'))
            ->willReturn($data);
        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test_mode'))
            ->willReturn(true);

        $this->session = new Session($this->filecache);
        $this->session->setConfig($this->config);
        $this->assertTrue($this->session->isActive());
    }

    /**
     * Test destroying the session
     */
    public function testIsNotActive()
    {
        $data = [
            'user_id' => '1234',
            'session' => '12345',
            'expires_at' => time() - 100,
        ];

        $this->filecache->expects($this->once())
            ->method('get')
            ->with($this->equalTo('session'))
            ->willReturn($data);
        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test_mode'))
            ->willReturn(false);

        $this->session = new Session($this->filecache);
        $this->session->setConfig($this->config);
        $this->assertFalse($this->session->isActive());
    }

    /**
     * Test getting and Tokens object
     */
    public function testGetTokens()
    {
        $tokens = $this->getMockBuilder(SavedTokens::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = new Session($this->filecache);

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(SavedTokens::class),
                $this->equalTo([['session' => $this->session,],])
            )
            ->willReturn($tokens);

        $this->session->setContainer($this->container);
        $out = $this->session->getTokens();
        $this->assertEquals($tokens, $out);
    }

    /**
     * Test getting and setting data
     */
    public function testGetUser()
    {
        $params = (object)['id' => '123',];
        $user = new User($params);

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(User::class),
                $this->equalTo([$params,])
            )
            ->willReturn($user);
        $this->filecache->expects($this->once())
            ->method('get')
            ->with($this->equalTo('session'))
            ->willReturn(['user_id' => $params->id,]);

        $this->session = new Session($this->filecache);
        $this->session->setContainer($this->container);
        $out = $this->session->getUser();
        $this->assertEquals($user, $out);
    }

    /**
     * Test getting and setting data
     */
    public function testSetGet()
    {
        $data = [
            'foo' => 'bar',
            'abc' => 123,
        ];

        $this->filecache->expects($this->once())
            ->method('get')
            ->with($this->equalTo('session'))
            ->willReturn($data);

        $this->session = new Session($this->filecache);
        $this->assertEquals($data['foo'], $this->session->get('foo'));
        $this->assertEquals($data['abc'], $this->session->get('abc'));
        $this->assertNull($this->session->get('invalid'));
    }

    /**
     * Test getting and setting data
     */
    public function testSetData()
    {
        $data = [
            'foo' => 'bar',
            'abc' => 123,
        ];

        $this->filecache->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo('session'),
                $this->equalTo($data)
            );

        $this->session = new Session($this->filecache);
        $this->session->setData($data);

        foreach ($data as $key => $val) {
            $this->assertEquals($val, $this->session->get($key));
        }
    }
}
