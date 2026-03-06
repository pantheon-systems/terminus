<?php

namespace Pantheon\Terminus\Commands\NodeLogs;

/**
 * Fetch the run time logs for a site.
 */
class NodeLogsRuntimeGetCommand extends NodeLogsBaseCommand
{

    /**
     * Print the run time log for last 24 hours.
     *
     * @authorize
     *
     * @command node:logs:runtime:get
     * @alias nlrg, node:logs:runtime
     *
     * @param string $site_env Site & environment in the format `site-name.env` (only Test or Live environment)
     * @option string log-name Filter by log name. Valid values: requests, stdout, stderr, system.
     * @option string severity Filter by log severity. Valid values: default, debug, info, notice, warning, error, critical, alert, emergency.
     * @option bool exclude-requests Exclude requests logs from the output. Default: false.
     */
    public function runtimeGet($site_env, $options = [
        'log-name' => '',
        'severity' => '',
        'exclude-requests' => '',
    ])
    {
        $this->requireSiteIsNotFrozen($site_env);

        $site = $this->getSiteById($site_env);
        $env = explode('.', $site_env)[1];

        $url = sprintf('/api/sites/%s/environment/%s/run/log', $site->id, $env);

        $query = [];
        if (!empty($options['log-name'])) {
            $query['logName'] = ($options['log-name'] == "system") ? "varlog/system" : $options['log-name'];
        }

        if (!empty($options['severity'])) {
            $query['severity'] = $options['severity'];
        }

        if (!empty($options['exclude-requests'])) {
            $query['exclude-requests'] = "true";
        }

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $logs_str = $this->getFromUrl($url);

        if ($logs_str == "") {
            $this->output()->writeln(
                sprintf('<error>No run time logs found for %s in the last 24 hours.</error>', $site_env)
            );
            return;
        }

        $this->output()->writeln($logs_str);
    }
}
