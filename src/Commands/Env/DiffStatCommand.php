<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DiffStatCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Show a statistical summary of the uncommitted code on an environment.
     *
     * @command env:diffstat
     *
     * @param string $site_env Site and environment to show diff statistics for.
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   file: File
     *   status: Status
     *   deletions: Deletions
     *   additions: Additions
     *
     * @usage terminus env:diffstat my-site.dev
     *   Show diff statistics for the `dev` environment for site `my-site`.
     */
    public function diffstat($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $diff = (array)$env->diffstat();
        $data = [];
        if (empty($diff)) {
            $this->log()->notice('No changes on server.');
        } else {
            foreach ($diff as $file => $stats) {
                $data[] = array_merge(compact('file'), (array)$stats);
            }
        }
        return new RowsOfFields($data);
    }
}
