<?php

namespace Pantheon\Terminus\UnitTests\Friends\Profile;

use Pantheon\Terminus\Friends\ProfileInterface;
use Pantheon\Terminus\Friends\ProfileTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class DummyClass
 * Testing aid for Pantheon\Terminus\Friends\ProfileTrait & Pantheon\Terminus\Friends\UserInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Profile
 */
class DummyClass extends TerminusModel implements ProfileInterface
{
    use ProfileTrait;
}
