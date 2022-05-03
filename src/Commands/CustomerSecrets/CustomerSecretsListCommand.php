<?php

namespace Pantheon\Terminus\Commands\CustomerSecrets;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\SecretsApi\SecretsApiAwareTrait;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class CustomerSecretsListCommand
 * List secrets for a given site.
 *
 * @package Pantheon\Terminus\Commands\CustomerSecrets
 */
class CustomerSecretsListCommand extends CustomerSecretsBaseCommand implements SiteAwareInterface
{
    use StructuredListTrait;
    use SiteAwareTrait;

    /**
     * Lists secrets for a specific site.
     *
     * @authorize
     * @filter-output
     *
     * @command customer-secrets:list
     * @aliases customer-secrets
     *
     * @field-labels
     *   name: Secret name
     *   value: Secret value
     *
     * @option boolean $debug Run command in debug mode
     * @param string $site_id The name or UUID of a site to retrieve information on
     * @param array $options
     * @return RowsOfFields
     *
     * @usage <site> Lists all secrets for current site.
     * @usage <site> --debug List all secrets for current site (debug mode).
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function listSecrets($site_id, array $options = ['debug' => false,])
    {
        if ($this->getSite($site_id)) {
            $secrets = $this->secretsApi->listSecrets($site_id, $options['debug']);

            return $this->getRowsOfFieldsFromSerializedData($secrets, 'customer secrets');
        }
    }
}
