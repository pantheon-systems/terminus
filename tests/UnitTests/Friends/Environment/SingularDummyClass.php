<?php

namespace Pantheon\Terminus\UnitTests\Friends\Environment;

use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class SingularDummyClass
 * Testing aid for Pantheon\Terminus\Friends\EnvironmentTrait & Pantheon\Terminus\Friends\EnvironmentInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Environment
 */
class SingularDummyClass extends TerminusModel implements EnvironmentInterface
{
    use EnvironmentTrait;
}
