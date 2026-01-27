<?php

namespace Pantheon\Terminus\Commands\Secret\Site;

use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Request\RequestOperationResult;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Commands\Secret\SecretBaseCommand;

/**
 * Class ListCommand.
 *
 * List secrets for a given site.
 *
 * @package Pantheon\Terminus\Commands\Secret\Site
 */
class ListCommand extends SecretBaseCommand implements SiteAwareInterface
{
    use StructuredListTrait;
    use SiteAwareTrait;

    /**
     * Lists secrets for a specific site.
     *
     * @authorize
     * @filter-output
     *
     * @command secret:site:list
     * @aliases secrets, secret:list
     *
     * @field-labels
     *   name: Secret name
     *   type: Secret type
     *   value: Secret value
     *   scopes: Secret scopes
     *   env-values: Environment override values
     *   org-values: Org values
     * @default-table-fields name,type,value,scopes
     *
     * @option boolean $debug Run command in debug mode
     *
     * @usage <site> Lists all secrets for current site.
     * @usage <site> --debug List all secrets for current site (debug mode).
     *
     * @param string $site_id The name or UUID of a site to retrieve information on
     * @param array $options
     *
     * @return RowsOfFields
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function listSecrets($site_id, array $options = ['debug' => false,])
    {
        $env_name = '';
        if (strpos($site_id, '.') !== false) {
            list($site_id, $env_name) = explode('.', $site_id, 2);
        }
        $site = $this->getSiteById($site_id);
        $this->setupRequest();
        $result = $this->secretsApi->listSecrets($site->id, $options['debug']);
        if ($result instanceof RequestOperationResult) {
            throw new TerminusException($result->getData());
        }
        $print_options = [
            'message' => 'You have no Secrets.'
        ];
        // If the user requested secrets from a specific environment, then
        // filter down to just the secret values there.
        if (!empty($env_name)) {
            $secrets = $this->secretsForEnv($result, $env_name);
            $print_options = [
                'message' => "There are no environment overrides in the environment '$env_name'.",
            ];
        }
        return $this->getTableFromData($result, $print_options);
    }

    /**
     * @param array $secrets Complete secret data
     * @param string $env_name Name of environment to pull secrets from
     *
     * @return array Secret data containing only secrets with overrides
     *   in the specified environment.
     */
    protected function secretsForEnv(array $secrets, $env_name)
    {
        $result = [];

        foreach ($secrets as $key => $data) {
            if (array_key_exists($env_name, $data['env-values'] ?? [])) {
                $result[$key] = [
                    'name' => $key,
                    'type' => $data['type'],
                    'value' => $data['env-values'][$env_name],
                    'scopes' => $data['scopes'],
                ];
            }
        }

        return $result;
    }

}
