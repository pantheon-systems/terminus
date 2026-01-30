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
 * Class LocalGenerateCommand.
 *
 * Generate local secrets file for usage of secrets in local environment.
 *
 * @package Pantheon\Terminus\Commands\Secret\Site
 */
class LocalGenerateCommand extends SecretBaseCommand implements SiteAwareInterface
{
    use StructuredListTrait;
    use SiteAwareTrait;

    /**
     * Generate json file for usage in local environment.
     *
     * @authorize
     *
     * @command secret:site:local-generate
     * @aliases secret:local-generate
     *
     * @option string $filepath Output to given file path. Default is to output to ./secrets.json
     *
     * @usage <site> Generate json file with secrets for local usage.
     * @usage <site> --filepath=/my/path/filename.json Generate json file in given location with secrets for local usage.
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
    public function localGenerateSecrets($site_id, array $options = ['filepath' => 'secrets.json',])
    {
        $env_name = '';
        if (strpos($site_id, '.') !== false) {
            list($site_id, $env_name) = explode('.', $site_id, 2);
        }
        $site = $this->getSiteById($site_id);
        $this->setupRequest();
        $result = $this->secretsApi->fetchSecrets($site->id);
        if ($result->getStatusCode() != 200) {
            throw new TerminusException($result->getData());
        }
        $data = $result->getData();
        $json_contents = json_encode($data, JSON_PRETTY_PRINT);
        $filepath = $options['filepath'];
        $ret = file_put_contents($filepath, $json_contents);
        if ($ret === false) {
            throw new TerminusException('Unable to write to file: ' . $filepath);
        }
        $message = "Secrets file written to: %s. Please review this file and adjust accordingly for your local usage.";
        $this->log()->notice(sprintf($message, $filepath));
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
