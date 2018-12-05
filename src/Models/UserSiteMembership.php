<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteJoinTrait;
use Pantheon\Terminus\Friends\UserInterface;
use Pantheon\Terminus\Friends\UserTrait;

/**
 * Class UserSiteMembership
 * @package Pantheon\Terminus\Models
 */
class UserSiteMembership extends TerminusModel implements ContainerAwareInterface, SiteInterface, UserInterface
{
    use ContainerAwareTrait;
    use SiteJoinTrait;
    use UserTrait;

    const PRETTY_NAME = 'user-site membership';

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->getUser()->id}: Team";
    }
}
