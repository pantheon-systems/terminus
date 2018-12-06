<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\OrganizationJoinInterface;
use Pantheon\Terminus\Friends\OrganizationJoinTrait;
use Pantheon\Terminus\Friends\UserInterface;
use Pantheon\Terminus\Friends\UserTrait;

/**
 * Class UserOrganizationMembership
 * @package Pantheon\Terminus\Models
 */
class UserOrganizationMembership extends TerminusModel implements ContainerAwareInterface, OrganizationJoinInterface, UserInterface
{
    use ContainerAwareTrait;
    use OrganizationJoinTrait;
    use UserTrait;

    const PRETTY_NAME = 'user-organization membership';
}
