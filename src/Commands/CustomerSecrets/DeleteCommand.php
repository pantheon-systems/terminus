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
 * Class SetCommand
 * Delete secret by name.
 *
 * @package Pantheon\Terminus\Commands\CustomerSecrets
 */
class DeleteCommand extends CustomerSecretsBaseCommand implements SiteAwareInterface
{
    use StructuredListTrait;
    use SiteAwareTrait;

    /**
     * Delete given secret for a specific site.
     *
     * @authorize
     *
     * @command customer-secrets:delete
     * @aliases customer-secrets-delete
     *
     * @option boolean $debug Run command in debug mode
     * @param string $site_id The name or UUID of a site to retrieve information on
     * @param string $name The secret name
     * @param array $options
     *
     * @usage <site> <name> Delete given secret.
     * @usage <site> <name> --debug Delete given secret (debug mode).
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function deleteSecret($site_id, string $name, array $options = ['debug' => false])
    {
        if ($this->getSite($site_id)) {
            if ($this->secretsApi->deleteSecret($site_id, $name, $options['debug'])) {
                $this->log()->notice('Success');
            } else {
                $this->log()->error('An error happened when trying to delete the secret.');
            }
        }
    }
}
