<?php
/**
 * @file
 * Contains Pantheon\Terminus\Commands\Backup\CreateCommand
 */

namespace Pantheon\Terminus\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Collections\Sites;
use Terminus\Collections\Backups;
use Terminus\Exceptions\TerminusNotFoundException;
use Terminus\Models\Environment;

class CreateCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Create a new backup of a Site's Environment
     *
     * @authorized
     *
     * @command backup:create
     *
     * @param string $site_env Site & environment to deploy to, in the form `site-name.env`.
     * @param string $element [all|code|files|database|db] Backup type
     * @param array $options [keep-for-days=<integer>]
     *
     * @return string
     *
     * @example terminus backup:create awesome-site.dev
     * @example terminus backup:create awesome-site.dev code --keep-for-days=90
     *
     */
    public function createBackup(
        $site_env,
        $element = 'all',
        $options = ['keep-for-days' => 30]
    ) {
        list($site, $env) = $this->getSiteEnv($site_env, 'dev');

        if ($element == 'db') {
            $backup_element = 'database';
        } else {
            $backup_element = $element;
        }

        if (isset($options['keep-for-days'])) {
            $keep_for_days = (integer) $options['keep-for-days'];
        } else {
            $keep_for_days = 30;
        }

        $args = [
            'element' => $backup_element,
            'keep-for' => $keep_for_days
        ];

        $workflow = $env->backups->create($args);
        $workflow->wait();
        $this->workflowOutput($workflow, ['failure' => 'Deployment failed.',]);
    }
}
