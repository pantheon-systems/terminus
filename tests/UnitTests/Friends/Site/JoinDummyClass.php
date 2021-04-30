<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\SiteJoinInterface;
use Pantheon\Terminus\Friends\SiteJoinTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class JoinDummyClass
 * Testing aid for Pantheon\Terminus\Friends\SiteJoinTrait & Pantheon\Terminus\Friends\SiteJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class JoinDummyClass extends TerminusModel implements ContainerAwareInterface, SiteJoinInterface
{
    use ContainerAwareTrait;
    use SiteJoinTrait;
}
