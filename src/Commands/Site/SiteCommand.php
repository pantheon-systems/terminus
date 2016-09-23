<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

abstract class SiteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
}
