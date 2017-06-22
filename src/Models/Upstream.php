<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Upstream
 * @package Pantheon\Terminus\Models
 */
class Upstream extends TerminusModel
{
    public static $pretty_name = 'upstream';
    /**
     * @var string
     */
    protected $url = 'upstreams/{id}';

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->id}: {$this->get('repository_url')}";
    }

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return [$this->id, $this->get('label'), $this->get('machine_name'),];
    }

    /**
     * Modify response data from Site between fetch and assignment
     *
     * @param object $data attributes received from API response
     * @return object $data
     */
    protected function parseAttributes($data)
    {
        if (property_exists($data, 'product_id')) {
            $data->id = $data->product_id;
        }
        if (property_exists($data, 'url')) {
            $data->repository_url = $data->url;
        }
        if (property_exists($data, 'branch')) {
            $data->repository_branch = $data->branch;
        }
        return $data;
    }
}
