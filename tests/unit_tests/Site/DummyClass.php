<?php

namespace Pantheon\Terminus\UnitTests\Site;

use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DummyClass implements SiteAwareInterface
{
    use SiteAwareTrait;
}
