<?php

namespace Pantheon\Terminus\Request;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class GraphQLRequest
 *
 * Handles requests made by Terminus to GraphQL
 *
 * @package Pantheon\Terminus\Request
 */
class GraphQLRequest implements ConfigAwareInterface, ContainerAwareInterface, LoggerAwareInterface, SessionAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoggerAwareTrait;
    use SessionAwareTrait;

    const DEBUG_REQUEST_STRING = "#### REQUEST ####\nURI: {uri}\nQuery: {query}\nRequest: {request}";
    const DEBUG_RESPONSE_STRING =  "#### RESPONSE ####\nData: {data}";

    /**
     * Request method for GraphQL
     *
     * @param string $path GraphQL endpoint path (URL)
     * @param string $query Query string
     * @return array
     */
    public function request($path, $query = '')
    {
        $oauth_token = $this->getConfig()->get('oauth_token');
        $escaped_query = str_replace('"', '\"', $query);
        if ($oauth_token === null) {
            throw new TerminusException('Please provide an oauth token via the TERMINUS_OAUTH_TOKEN env var.');
        }
        $query_command = <<<EOT
curl -H "Authorization: bearer $oauth_token" -X POST -d '{"query": "$escaped_query"}' $path
EOT;
    $this->logger->debug(
        self::DEBUG_REQUEST_STRING,
            [
                'url' => $path,
                'query' => $query,
                'request' => json_encode($query_command),
            ]
        );
        ob_start();
        passthru($query_command . ' 2>&1');
        $response = (array)preg_grep('/{.*}/', explode("\n", ob_get_clean()));
        $data = (array)json_decode(array_pop($response));

        $this->logger->debug(
            self::DEBUG_RESPONSE_STRING,
            [
                'data' => json_encode($data),
            ]
        );

      return $data;
    }
}
