<?php

namespace Pantheon\Terminus\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Symfony\Component\Console\Style\SymfonyStyle;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Consolidation\AnnotatedCommand\CommandError;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;

class SecretsHandlingCommand implements SiteAwareInterface, LoggerAwareInterface, ContainerAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use LoggerAwareTrait;
    use ContainerAwareTrait;
    use VcsClientAwareTrait;

  /**
   * @hook post-command secret:site:set
   *
   * @option $rebuild Trigger rebuild for application after setting secret (only applicable to Node sites)
   * @default $rebuild false
   */
    public function postCommand($result, CommandData $commandData)
    {
        if ($result instanceof CommandError) {
            // Nothing to do for errors.
            return;
        }

        $input = $commandData->input();

        $siteenv = $input->getArgument('siteenv');

        if (strpos($siteenv, '.') !== false) {
            list($site_id, $env_name) = explode('.', $siteenv);
        } else {
            $site_id = $siteenv;
            $env_name = null;
        }

        $site = $this->getSiteById($site_id);
        if ($site->get('framework') !== 'nodejs') {
            // Nothing to do as this only applies to node sites.
            return;
        }

        $output = $commandData->output();
        $rebuild = $input->getOption('rebuild') ?? false;
        $interactive = $input->isInteractive();
        if (!$rebuild && $interactive) {
            $io = new SymfonyStyle($input, $output);
            // It is hard to know whether user does not want to rebuild or just did not provide the option.
            // Ask the user if they want to rebuild
            $rebuild = $io->confirm("Do you want to rebuild the application?", false);
        }

        if (!$rebuild) {
            // Nothing to do if rebuild is not requested.
            return;
        }

        $env = $env_name ?? 'dev';

        $this->logger->info('Rebuilding application for environment "{env}"...', ['env' => $env]);

        $this->getVcsClient()->rebuild($site->get('id'), $env);

        $this->logger->notice('Application rebuild triggered for environment "{env}".', ['env' => $env]);
        if (!$env_name) {
            $this->logger->notice('You may want to rebuild a different environment using "{command}"', ['command' => 'terminus node:build:rebuild <site>.<env>.']);
        }
    }
}
