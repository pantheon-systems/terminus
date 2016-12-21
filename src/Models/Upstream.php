<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Upstream
 * @package Pantheon\Terminus\Models
 */
class Upstream extends TerminusModel
{
    /**
     * @var Site
     */
    public $site;

    /**
     * @inheritdoc
     */
    public function __construct($attributes, array $options = [])
    {
        parent::__construct($attributes, $options);
        if (isset($options['site'])) {
            $this->site = $options['site'];
        }
    }

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
        if (!empty($this->site)) {
            return [
                'url' => $this->get('url'),
                'product_id' => $this->get('product_id'),
                'branch' => $this->get('branch'),
                'status' => $this->getStatus(),
            ];
        }
        return (array)$this->attributes;
    }

    /**
     * @inheritdoc
     */
    protected function parseAttributes($data)
    {
        if (property_exists($data, 'attributes')) {
            $data = $data->attributes;
        }
        if (property_exists($data, 'product_id')) {
            $data->id = $data->product_id;
        }
        return $data;
    }
}
