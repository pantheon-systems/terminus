<?php

namespace Pantheon\Terminus\Models;

class Solr extends AddOnModel
{
    const PRETTY_NAME = 'Solr';

    /**
     * Disables Solr indexing
     */
    public function disable()
    {
        $this->setStatus(false);
    }

    /**
     * Enables Solr indexing
     */
    public function enable()
    {
        $this->setStatus(true);
    }

    /**
     * Sets the site's allow_indexserver setting to this value
     *
     * @param boolean $status True to enable Solr, false to disable
     */
    private function setStatus($status)
    {
        $this->request()->request(
            "sites/{$this->getSite()->id}/settings",
            ['method' => 'put', 'form_params' => ['allow_indexserver' => $status,],]
        );
    }
}
