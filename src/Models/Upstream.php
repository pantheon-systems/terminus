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
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->id}: {$this->get('url')}";
    }

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return [$this->id, $this->get('longname'), $this->get('machinename'),];
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
