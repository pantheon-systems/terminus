<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class AuthTest
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
abstract class AuthTest extends CommandTestCase
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
