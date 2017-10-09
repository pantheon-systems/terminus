<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Branch;

/**
 * Class Branches
 * @package Pantheon\Terminus\Collections
 */
class Branches extends SiteOwnedCollection
{
    public static $pretty_name = 'branches';
    /**
     * @var string
     */
    protected $collected_class = Branch::class;
    /**
     * @var Site
     */
    public $site;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/code-tips';

    /**
     * Fetches from API and instantiates its model instances with data it assembled from the request
     *
     * @return Branches $this
     */
    public function fetch()
    {
        foreach ($this->getData() as $id => $sha) {
            $this->add((object)['id' => $id, 'sha' => $sha,]);
        }
        return $this;
    }
}
