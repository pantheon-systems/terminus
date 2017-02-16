<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Site;

/**
 * Class SiteJoinTrait
 * @package Pantheon\Terminus\Friends
 */
trait SiteJoinTrait
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @inheritdoc
     */
    public function getReferences()
    {
        return array_merge(parent::getReferences(), $this->getSite()->getReferences());
    }

    /**
     * @inheritdoc
     */
    public function getSite()
    {
        if (empty($this->site)) {
            $site = $this->getContainer()->get(Site::class, [$this->get('site'),]);
            $site->memberships = [$this,];
            $this->setSite($site);
        }
        return $this->site;
    }

    /**
     * @inheritdoc
     */
    public function setSite(Site $site)
    {
        $this->site = $site;
    }
}
