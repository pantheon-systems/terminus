<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\API\Site\SiteAwareInterface;
use Pantheon\Terminus\API\Site\SiteAwareTrait;

/**
 * Class SiteCommand
 * @package Pantheon\Terminus\Commands\Site
 */
abstract class SiteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
}
