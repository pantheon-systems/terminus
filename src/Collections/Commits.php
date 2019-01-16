<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Commit;

/**
 * Class Commits
 * @package Pantheon\Terminus\Collections
 */
class Commits extends EnvironmentOwnedCollection
{
    const PRETTY_NAME = 'commits';
    /**
     * @var string
     */
    protected $collected_class = Commit::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/code-log';
}
