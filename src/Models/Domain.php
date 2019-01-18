<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\DNSRecords;
use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;

/**
 * Class Domain
 * @package Pantheon\Terminus\Models
 */
class Domain extends TerminusModel implements ContainerAwareInterface, EnvironmentInterface
{
    use ContainerAwareTrait;
    use EnvironmentTrait;

    /**
     * @var DNSRecords
     */
    private $dns_records;

    const PRETTY_NAME = 'domain';
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
     * @return DNSRecords
     */
    public function getDNSRecords()
    {
        if (empty($this->dns_records)) {
            $this->dns_records = $this->getContainer()->get(
                DNSRecords::class,
                [['data' => $this->get('dns_status_details')->dns_records, 'domain' => $this,],]
            );
        }
        return $this->dns_records;
    }

    /**
     * Formats Domain object into an associative array for output
     *
     * @return array $data associative array of data for output
     */
    public function serialize()
    {
        return [
            'id' => $this->id,
            'type' => $this->get('type'),
            'status' => in_array($this->get('status'), ['ok', 'okay',]) ? 'OK' : $this->get('status'),
            'status_message' => $this->get('status_message'),
            'deletable' => (boolean)$this->get('deletable'),
        ];
    }
}
