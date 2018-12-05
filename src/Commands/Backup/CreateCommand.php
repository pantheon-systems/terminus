<?php

namespace Pantheon\Terminus\Commands\Backup;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;

/**
 * Class CreateCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class CreateCommand extends BackupCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Creates a backup of a specific site and environment.
     *
     * @authorize
     *
     * @command backup:create
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $element [all|code|files|database|db] Element to be backed up
     * @option integer $keep-for Retention period, in days, to retain backup
     *
     * @usage <site>.<env> Creates a backup of <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Creates a backup of <site>'s <env> environment's <element>.
     * @usage <site>.<env> --keep-for=<days> Creates a backup of <site>'s <env> environment and retains it for <days> days.
     * @usage <site>.<env> --keep-for=<days> Creates a backup of <site>'s <env> environment's <element> and retains it for <days> days.
     */
    public function create($site_env, $options = ['element' => 'all', 'keep-for' => 365,])
    {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env);
        $options['element'] = isset($options['element']) ? $this->getElement($options['element']) : null;
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $env->getBackups()->create($options),])->cycle();
        $this->log()->notice('Created a backup of the {env} environment.', ['env' => $env->id,]);
    }
}
