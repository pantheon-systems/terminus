<?php


namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;

class PrimaryDomain implements EnvironmentInterface
{
    use EnvironmentTrait;

    public function __construct(Environment $environment)
    {
        $this->setEnvironment($environment);
    }

    /**
     * Builds a Workflow to set the primary domain for this environment.
     *
     * @param string $domain A domain name attached to this environment.
     *
     * @return Workflow
     */
    public function setPrimaryDomain($domain)
    {
        return $this->workflowFactory($domain);
    }

    /**
     * Builds a workflow to remove the primary domain from this environment.
     *
     * @return Workflow
     */
    public function removePrimaryDomain()
    {
        return $this->workflowFactory(null);
    }

    /**
     * @param string $domain
     * @return Workflow
     */
    protected function workflowFactory($domain)
    {
        return $this->getEnvironment()->getWorkflows()->create(
            'set_primary_domain',
            [
                'environment' => $this->getEnvironment()->id,
                'params'      => ['primary_domain' => $domain]
            ]
        );
    }
}
