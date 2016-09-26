<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

class CodeLogCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Show an environment's code log.
     *
     * @command env:code-log
     *
     * @param string $site_env Site & environment to show log for.
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   time: Timestamp
     *   author: Author
     *   labels: Labels
     *   hash: Commit ID
     *   message: Message
     *
     * @usage terminus env:code-log my-site.dev
     *   Show code log for the `dev` environment for site `my-site`.
     */
    public function codeLog($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');
        $logs = $env->commits->all();
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
