<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\DNSRecords;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;
use Pantheon\Terminus\Request\RequestOperationResult;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class Domain
 *
 * @package Pantheon\Terminus\Models
 */
class Domain extends TerminusModel implements
    ContainerAwareInterface,
    EnvironmentInterface,
    SiteAwareInterface
{
    use ContainerAwareTrait;
    use EnvironmentTrait;
    use SiteAwareTrait;

    /**
     * @var DNSRecords
     */
    private $dns_records;

    public const PRETTY_NAME = 'domain';

    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{env_id}/domains/{id}';

    /**
     * Delete a domain from an environment
     *
     * @return array
     */
    public function delete(): RequestOperationResult
    {
        $action = $this->request->request(
            $this->getUrl(),
            ['method' => 'delete']
        );
        if ($action->isError()) {
            throw new TerminusProcessException(
                "Domain remove failed. {site}.{env} => {domain}: {error}",
                [
                    "site" => $this->environment->getSite()->getName(),
                    "env" => $this->environment->id,
                    "domain" => $this->id,
                    "error" => $action->getStatusCodeReason(),
                ]
            );
        }
        return $action;
    }

    /**
     * @return DNSRecords
     */
    public function getDNSRecords()
    {
        if (empty($this->dns_records)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, DNSRecords::class)
                ->addArgument([
                    'data' => $this->get('dns_status_details')->dns_records,
                    'domain' => $this,
                ]);
            $this->dns_records = $this->getContainer()->get($nickname);
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
            'deletable' => (bool)$this->get('deletable'),
            'primary' => (bool)$this->get('primary'),
        ];
    }
}
