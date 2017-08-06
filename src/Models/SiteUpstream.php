<?php

namespace Pantheon\Terminus\Models;

/**
 * Class SiteUpstream
 * @package Pantheon\Terminus\Models
 */
class SiteUpstream extends TerminusModel
{
    public static $pretty_name = 'upstream';

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->id}: {$this->get('url')}";
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return [
            'url' => $this->get('url'),
            'product_id' => $this->get('product_id'),
            'branch' => $this->get('branch'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function parseAttributes($data)
    {
        $data->id = $data->product_id;
        return $data;
    }
}
