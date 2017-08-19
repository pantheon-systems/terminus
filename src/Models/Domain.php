<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;

/**
 * Class Domain
 * @package Pantheon\Terminus\Models
 */
class Domain extends TerminusModel implements EnvironmentInterface
{
    use EnvironmentTrait;

    public static $pretty_name = 'domain';
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{env_id}/domains/{id}';

    /**
     * Delete a domain from an environment
     *
     * @return array
     */
    public function delete()
    {
        return $this->request->request($this->getUrl(), ['method' => 'delete',])['data'];
    }

    /**
     * Formats Domain object into an associative array for output
     *
     * @return array $data associative array of data for output
     */
    public function serialize()
    {
        $data = [
            'domain' => $this->id,
            'dns_zone_name' => $this->get('dns_zone_name'),
            'environment' => $this->get('environment'),
            'site_id' => $this->get('site_id'),
            'key' => $this->get('key'),
            'deletable' => $this->get('deletable'),
        ];
        return $data;
    }
}
