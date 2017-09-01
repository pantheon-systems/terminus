<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Site;

/**
 * Class SiteTrait
 * @package Pantheon\Terminus\Friends
 */
trait SiteTrait
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        if (isset($options['site'])) {
            $this->setSite($options['site']);
        }
        parent::__construct($attributes, $options);
    }

    /**
     * @return Site Returns a Site-type object
     */
    public function getSite()
    {
        if (empty($this->site) && isset($this->collection)) {
            $this->setSite($this->collection->getSite());
        }
        return $this->site;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return str_replace('{site_id}', $this->getSite()->id, parent::getUrl());
    }

    /**
     * @param Site $site
     */
    public function setSite(Site $site)
    {
        $this->site = $site;
    }
}
