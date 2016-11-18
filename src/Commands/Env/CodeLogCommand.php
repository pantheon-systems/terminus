<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class CodeLogCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CodeLogCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Show an environment's code log
     *
     * @authorize
     *
     * @command env:code-log
     *
     * @field-labels
     *   time: Timestamp
     *   author: Author
     *   labels: Labels
     *   hash: Commit ID
     *   message: Message
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment to show log for
     *
     * @usage terminus env:code-log <site>.<env>
     *   Show code log for the <env> environment of <site>
     */
    public function codeLog($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');
        $logs = $env->getCommits()->all();
        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                'time'    => $log->get('datetime'),
                'author'  => $log->get('author'),
                'labels'  => implode(', ', $log->get('labels')),
                'hash'    => $log->get('hash'),
                'message' => trim(
                    str_replace(
                        "\n",
                        '',
                        str_replace("\t", '', substr($log->get('message'), 0, 50))
                    )
                ),
            ];
        }
        return new RowsOfFields($data);
    }
}
