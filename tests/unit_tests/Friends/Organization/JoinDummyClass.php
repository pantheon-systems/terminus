<?php

namespace Pantheon\Terminus\UnitTests\Friends\Organization;

use Pantheon\Terminus\Friends\OrganizationJoinInterface;
use Pantheon\Terminus\Friends\OrganizationJoinTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class JoinDummyClass
 * Testing aid for Pantheon\Terminus\Friends\OrganizationJoinTrait & Pantheon\Terminus\Friends\OrganizationJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Organization
 */
class JoinDummyClass extends TerminusModel implements OrganizationJoinInterface
{
    use OrganizationJoinTrait;
}
