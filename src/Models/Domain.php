<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Domain
 * @package Pantheon\Terminus\Models
 */
class Domain extends TerminusModel
{
    /**
     * @var Environment
     */
    public $environment;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct($attributes, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->environment = $options['collection']->environment;
    }

    /**
     * Delete a domain from an environment
     *
     * @return array
     */
    public function delete()
    {
        $url = sprintf(
            'sites/%s/environments/%s/hostnames/%s',
            $this->environment->site->id,
            $this->environment->id,
            rawurlencode($this->id)
        );
        $response = $this->request->request($url, ['method' => 'delete']);
        return $response['data'];
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
