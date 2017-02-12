<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Commit;

/**
 * Class Commits
 * @package Pantheon\Terminus\Collections
 */
class Commits extends EnvironmentOwnedCollection
{
    public static $pretty_name = 'commits';
    /**
     * @var string
     */
    protected $collected_class = Commit::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/code-log';

    /**
     * Filters the models by which are available to be copied from the upstream
     *
     * @return $this
     */
    public function getReadyToCopy()
    {
        if (!is_object($from_env = $this->getEnvironment()->getParentEnvironment())) {
            return [];
        }
        $commits = array_filter(
            $from_env->getCommits()->all(),
            function ($commit) {
                return !in_array($this->getEnvironment()->id, $commit->get('labels'));
            }
        );
        return $commits;
    }
}
