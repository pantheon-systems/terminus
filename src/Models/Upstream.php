<?php

namespace Pantheon\Terminus\Models;

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
     * Returns the status of this site's upstream updates
     *
     * @return string $status 'outdated' or 'current'
     */
    public function getStatus()
    {
        if ($this->hasUpdates()) {
            $status = 'outdated';
        } else {
            $status = 'current';
        }
        return $status;
    }

    /**
     * Retrives upstream updates
     *
     * @return \stdClass
     */
    public function getUpdates()
    {
        return $this->request->request("sites/{$this->site->id}/code-upstream-updates");
    }

    /**
     * Determines whether there are any updates to be applied.
     *
     * @return boolean
     */
    public function hasUpdates()
    {
        $updates = $this->getUpdates();
        return ($updates->behind > 0);
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
