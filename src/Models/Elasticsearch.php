<?php

namespace Pantheon\Terminus\Models;

class Elasticsearch extends AddOnModel
{
    public const PRETTY_NAME = 'Elasticsearch';

    /**
     * Disables Elasticsearch indexing
     *
     * @return Workflow
     */
    public function disable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('disable_addon', [
            'params' => [
                'addon' => 'elasticsearch',
            ],
        ]);
    }

    /**
     * Enables Elasticsearch indexing
     *
     * @return Workflow
     */
    public function enable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('enable_addon', [
            'params' => [
                'addon' => 'elasticsearch',
            ],
        ]);
    }
}
