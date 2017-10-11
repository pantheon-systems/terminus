<?php

namespace Pantheon\Terminus\UnitTests\Friends\Domain;

use Pantheon\Terminus\Friends\DomainInterface;
use Pantheon\Terminus\Friends\DomainTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class SingularDummyClass
 * Testing aid for Pantheon\Terminus\Friends\DomainTrait & Pantheon\Terminus\Friends\EnvironmentInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Domain
 */
class SingularDummyClass extends TerminusModel implements DomainInterface
{
    use DomainTrait;
}
