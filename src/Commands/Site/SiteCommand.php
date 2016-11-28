<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SiteCommand
 * @package Pantheon\Terminus\Commands\Site
 */
abstract class SiteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
}
