<?php

namespace Pantheon\Terminus\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

class LockHandlingCommand implements
    SiteAwareInterface,
    LoggerAwareInterface,
    ContainerAwareInterface,
    RequestAwareInterface
{
    use SiteAwareTrait;
    use LoggerAwareTrait;
    use ContainerAwareTrait;
    use VcsClientAwareTrait;

    /**
     * @hook post-command lock:enable
     *
     * @option $rebuild Trigger rebuild for application after locking (only applicable to Node sites)
     * @default $rebuild false
     */
    public function postEnableCommand($result, CommandData $commandData)
    {
        $this->handlePostLockCommand($result, $commandData);
    }

    /**
     * @hook post-command lock:disable
     *
     * @option $rebuild Trigger rebuild for application after unlocking (only applicable to Node sites)
     * @default $rebuild false
     */
    public function postDisableCommand($result, CommandData $commandData)
    {
        $this->handlePostLockCommand($result, $commandData);
    }

    private function handlePostLockCommand($result, CommandData $commandData)
    {
        if ($result instanceof CommandError) {
            return;
        }

        $input = $commandData->input();

        $site_env = $input->getArgument('site_env');

        list($site_id, $env_name) = explode('.', $site_env);

        $site = $this->getSiteById($site_id);
        if ($site->get('framework') !== 'nodejs') {
            return;
        }

        $output = $commandData->output();
        $rebuild = $input->getOption('rebuild') ?? false;
        $interactive = $input->isInteractive();
        if (!$rebuild && $interactive) {
            $io = new SymfonyStyle($input, $output);
            $rebuild = $io->confirm("Do you want to rebuild the application?", false);
        }

        if (!$rebuild) {
            return;
        }

        $this->logger->info('Rebuilding application for environment "{env}"...', ['env' => $env_name]);

        $this->getVcsClient()->rebuild($site->get('id'), $env_name);

        $this->logger->notice('Application rebuild triggered for environment "{env}".', ['env' => $env_name]);
    }
}
