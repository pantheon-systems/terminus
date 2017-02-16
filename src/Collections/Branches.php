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
     * @inheritdoc
     */
    public function fetch(array $options = [])
    {
        $data = isset($options['data']) ? $options['data'] : $this->getCollectionData($options);
        foreach ($data as $id => $sha) {
            $this->add((object)['id' => $id, 'sha' => $sha,]);
        }
        return $this;
    }
}
