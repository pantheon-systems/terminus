<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Site;

class SiteOwnedCollection extends TerminusCollection
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * Object constructor
     *
     * @param array $options Options to set as $this->key
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setSite($options['site']);
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param Site $site
     */
    public function setSite($site)
    {
        $this->site = $site;
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return str_replace('{site_id}', $this->getSite()->id, parent::getUrl());
    }
}
