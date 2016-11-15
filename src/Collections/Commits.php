<?php

namespace Pantheon\Terminus\Collections;

class Commits extends EnvironmentOwnedCollection
{

    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\Commit';

    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/code-log';
}
