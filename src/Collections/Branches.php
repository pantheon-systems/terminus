<?php

namespace Pantheon\Terminus\Collections;

class Branches extends TerminusCollection
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
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->site = $options['site'];
        $this->url = "sites/{$this->site->id}/code-tips";
    }

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
