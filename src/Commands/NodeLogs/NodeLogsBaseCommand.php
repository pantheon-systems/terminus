<?php

namespace Pantheon\Terminus\Commands\NodeLogs;

use Pantheon\Terminus\Commands\Import\SiteCommand;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Base command for Node.js logs functionality.
 */
class NodeLogsBaseCommand extends SiteCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use RequestAwareTrait;

    /**
     * Get data from a given url.
     *
     * @param string $url Url to get data from.
     *
     * @return array|string|null
     */
    public function getFromUrl(string $url)
    {
        $protocol = $this->getConfig()->get('protocol');
        $host = $this->getConfig()->get('host');

        $url = sprintf('%s://%s%s', $protocol, $host, $url);

        $options = [
            'headers' => [
                'X-Pantheon-Session' => $this->request->session()->get('session'),
            ],
        ];
        $result = $this->request()->request($url, $options);
        $status_code = $result->getStatusCode();

        if ($status_code != 200) {
            return null;
        }

        $data = $result->getData();
        if (empty($data)) {
            return null;
        }

        return $data;
    }
}
