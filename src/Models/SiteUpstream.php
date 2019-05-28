<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;

/**
 * Class SiteUpstream
 * @package Pantheon\Terminus\Models
 */
class SiteUpstream extends TerminusModel implements ContainerAwareInterface, SiteInterface
{
    use ContainerAwareTrait;
    use SiteTrait;

    const PRETTY_NAME = 'upstream';

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->id}: {$this->get('url')}";
    }

    /**
     * Clears a site's code cache
     *
     * @return Workflow
     */
    public function clearCache()
    {
        return $this->getSite()->getWorkflows()->create('clear_code_cache');
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        if (empty($this->repository)) {
            $this->repository = $this->getContainer()->get(Repository::class, [null, ['upstream' => $this,],]);
        }
        return $this->repository;
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
        if (!property_exists($data, 'id') && property_exists($data, 'product_id')) {
            $data->id = $data->product_id;
        }
        return $data;
    }
}
