<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use Pantheon\Terminus\Friends\SiteJoinInterface;
use Pantheon\Terminus\Friends\SiteJoinTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class JoinDummyClass
 * Testing aid for Pantheon\Terminus\Friends\SiteJoinTrait & Pantheon\Terminus\Friends\SiteJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class JoinDummyClass extends TerminusModel implements SiteJoinInterface
{
    use SiteJoinTrait;
}
