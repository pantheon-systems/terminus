<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class SingularDummyClass
 * Testing aid for Pantheon\Terminus\Friends\SiteTrait & Pantheon\Terminus\Friends\SiteInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class SingularDummyClass extends TerminusModel implements SiteInterface
{
    use SiteTrait;
}
