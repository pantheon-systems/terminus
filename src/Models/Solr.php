<?php

namespace Pantheon\Terminus\Models;

class Solr extends AddOnModel
{
    public const PRETTY_NAME = 'Solr';

    /**
     * Disables Solr indexing
     *
     * @return Workflow
     */
    public function disable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('disable_addon', [
            'params' => [
                'addon' => 'indexserver',
            ],
        ]);
    }

    /**
     * Enables Solr indexing
     *
     * @return Workflow
     */
    public function enable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('enable_addon', [
            'params' => [
                'addon' => 'indexserver',
            ],
        ]);
    }
}
