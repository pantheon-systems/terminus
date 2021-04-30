<?php

namespace Pantheon\Terminus\UnitTests\Friends\User;

use Pantheon\Terminus\Friends\UserJoinInterface;
use Pantheon\Terminus\Friends\UserJoinTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class JoinDummyClass
 * Testing aid for Pantheon\Terminus\Friends\UserJoinTrait & Pantheon\Terminus\Friends\UserJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\User
 */
class JoinDummyClass extends TerminusModel implements UserJoinInterface
{
    use UserJoinTrait;
}
