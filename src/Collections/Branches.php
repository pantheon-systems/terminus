<?php

namespace Pantheon\Terminus\Collections;

class Branches extends SiteOwnedCollection
{
    /**
     * @var Site
     */
    public $site;
    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\Branch';

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
