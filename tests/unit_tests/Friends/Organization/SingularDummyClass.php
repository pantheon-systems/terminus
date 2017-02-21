<?php

namespace Pantheon\Terminus\UnitTests\Friends\Organization;

use Pantheon\Terminus\Friends\OrganizationInterface;
use Pantheon\Terminus\Friends\OrganizationTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class SingularDummyClass
 * Testing aid for Pantheon\Terminus\Friends\OrganizationTrait & Pantheon\Terminus\Friends\OrganizationInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Organization
 */
class SingularDummyClass extends TerminusModel implements OrganizationInterface
{
    use OrganizationTrait;
}
