<?php

namespace Pantheon\Terminus\UnitTests\Friends\User;

use Pantheon\Terminus\Friends\UserInterface;
use Pantheon\Terminus\Friends\UserTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class SingularDummyClass
 * Testing aid for Pantheon\Terminus\Friends\UserTrait & Pantheon\Terminus\Friends\UserInterface
 * @package Pantheon\Terminus\UnitTests\Friends\User
 */
class SingularDummyClass extends TerminusModel implements UserInterface
{
    use UserTrait;
}
