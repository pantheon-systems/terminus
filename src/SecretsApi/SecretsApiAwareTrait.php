<?php

namespace Pantheon\Terminus\SecretsApi;

/**
 * Class SecretsApiAwareTrait.
 *
 * @package Pantheon\Terminus\SecretsApi
 */
trait SecretsApiAwareTrait
{
    /**
     * @var \Pantheon\Terminus\SecretsApi\SecretsApi
     */
    protected $secretsApi;

    /**
     * Inject a pre-configured SecretsApi object.
     *
     * @param \Pantheon\Terminus\SecretsApi\SecretsApi $secretsApi
     */
    public function setSecretsApi(SecretsApi $secretsApi): void
    {
        $this->secretsApi = $secretsApi;
    }

    /**
     * Return the SecretsApi object.
     *
     * @return \Pantheon\Terminus\SecretsApi\SecretsApi
     */
    public function secretsApi(): SecretsApi
    {
        return $this->secretsApi;
    }
}
