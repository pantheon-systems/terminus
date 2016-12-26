<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use League\Container\Container;
use Pantheon\Terminus\Commands\AliasesCommand;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Robo\Config;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;

/**
 * Class AliasesCommandTest
 * Testing class for Pantheon\Terminus\Commands\AliasesCommand
 * @package Pantheon\Terminus\UnitTests\Commands
 */
class AliasesCommandTest extends CommandTestCase
{
    /**
     * @var string
     */
    protected $aliases;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var LocalMachineHelper
     */
    protected $local_machine_helper;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var User
     */
    protected $user;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->aliases = '//Aliases';
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->local_machine_helper = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getAliases')
            ->with()
            ->willReturn($this->aliases);

        $this->command = new AliasesCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
        $this->command->setContainer($this->container);
    }

    /**
     * Tests the aliases command when writing to a the default file
     */
    public function testAliases()
    {
        $default_location = '~/.drush/pantheon.aliases.drushrc.php';

        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LocalMachineHelper::class))
            ->willReturn($this->local_machine_helper);
        $this->local_machine_helper->expects($this->once())
            ->method('writeFile')
            ->with(
                $this->equalTo($default_location),
                $this->equalTo($this->aliases)
            );
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Aliases file written to {location}.'),
                $this->equalTo(['location' => $default_location,])
            );

        $out = $this->command->aliases();
        $this->assertNull($out);
    }

    /**
     * Tests the aliases command when writing to a named file
     */
    public function testAliasesWithLocation()
    {
        $location = '~/.terminus/behatcache/aliases.php';

        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LocalMachineHelper::class))
            ->willReturn($this->local_machine_helper);
        $this->local_machine_helper->expects($this->once())
            ->method('writeFile')
            ->with(
                $this->equalTo($location),
                $this->equalTo($this->aliases)
            );
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Aliases file written to {location}.'),
                $this->equalTo(compact('location'))
            );

        $out = $this->command->aliases(compact('location'));
        $this->assertNull($out);
    }

    /**
     * Tests the aliases command when it is outputting to the screen
     */
    public function testAliasesPrint()
    {
        $this->container->expects($this->never())
            ->method('get');
        $this->local_machine_helper->expects($this->never())
            ->method('writeFile');
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->aliases(['print' => true,]);
        $this->assertEquals($out, $this->aliases);
    }
}
