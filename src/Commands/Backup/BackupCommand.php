<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

abstract class BackupCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Returns the API-ready element name for the given string
     *
     * @param string $element
     * @return null|string
     */
    protected function getElement($element)
    {
        if ($element === 'db') {
            return 'database';
        }
        if ($element === 'all') {
            return null;
        }
        return $element;
    }
}
