<?php

namespace Pantheon\Terminus\Commands\Remote;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\ProcessUtils;

/**
 * Class SSHBaseCommand
 * Base class for Terminus commands that deal with sending SSH commands
 * @package Pantheon\Terminus\Commands\Remote
 */
abstract class SSHBaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * @var string Name of the command to be run as it will be used on server
     */
    protected $command = '';
    /**
     * @var Site
     */
    private $site;
    /**
     * @var Environment
     */
    private $environment;

    /**
     * Define the environment and site properties
     *
     * @param string $site_env_id The site/env to retrieve in <site>.<env> format
     */
    protected function prepareEnvironment($site_env_id)
    {
        list($this->site, $this->environment) = $this->getSiteEnv($site_env_id);
    }

    /**
     * Execute the command remotely
     *
     * @param array $command_args
     * @return string
     * @throws TerminusProcessException
     */
    protected function executeCommand(array $command_args)
    {
        $this->validateEnvironment($this->site, $this->environment);

        $command_summary = $this->getCommandSummary($command_args);
        $command_line = $this->getCommandLine($command_args);

        $useTty = $this->useTty($this->input());

        $output = $this->output();
        $echoOutputFn = function ($type, $buffer) {
        };
        if ($useTty === false) {
            $echoOutputFn = function ($type, $buffer) use ($output) {
                $output->write($buffer);
            };
        }

        $result = $this->environment->sendCommandViaSsh($command_line, $echoOutputFn, $useTty);
        $output = $result['output'];
        $exit = $result['exit_code'];

        $this->log()->notice('Command: {site}.{env} -- {command} [Exit: {exit}]', [
            'site'    => $this->site->get('name'),
            'env'     => $this->environment->id,
            'command' => $command_summary,
            'exit'    => $exit,
        ]);

        if ($exit != 0) {
            throw new TerminusProcessException($output);
        }
    }

    /**
     * Determine whether the use of a tty is appropriate for the current command.
     *
     * @param InputInterface $input
     * @return bool|null
     */
    protected function useTty($input)
    {
        // If we are not in interactive mode, then never use a tty.
        if (!$input->isInteractive()) {
            return false;
        }
        // If we are in interactive mode (or at least the user did not
        // specify -n / --no-interaction), then also prevent the use
        // of a tty if stdout is redirected.
        // Otherwise, let the local machine helper decide whether to use a tty.
        return (function_exists('posix_isatty') && !posix_isatty(STDOUT)) ? false : null;
    }

    /**
     * Validates that the environment's connection mode is appropriately set
     *
     * @param Site $site
     * @param Environment $environment
     */
    protected function validateEnvironment($site, $environment)
    {
        // Only warn in dev / multidev
        if ($environment->isDevelopment()) {
            $this->validateConnectionMode($environment->get('connection_mode'));
        }
    }

    /**
     * Validates that the environment is using the correct connection mode
     *
     * @param string $mode
     */
    protected function validateConnectionMode($mode)
    {
        if ((!$this->getConfig()->get('hide_git_mode_warning')) && ($mode == 'git')) {
            $this->log()->warning(
                'This environment is in read-only Git mode. If you want to make changes to the codebase of this site '
                . '(e.g. updating modules or plugins), you will need to toggle into read/write SFTP mode first.'
            );
        }
    }

    /**
     * Gets the command-line args
     *
     * @param string[] $command_args
     * @return string
     */
    private function getCommandLine($command_args)
    {
        array_unshift($command_args, $this->command);
        return implode(" ", $this->escapeArguments($command_args));
    }

    /**
     * Return a summary of the command that does not include the
     * arguments. This avoids potential information disclosure in
     * CI scripts.
     *
     * @param array $command_args
     * @return string
     */
    private function getCommandSummary($command_args)
    {
        return $this->command . $this->firstArguments($command_args);
    }

    /**
     * Return the first item of the $command_args that is not an option.
     *
     * @param array $command_args
     * @return string
     */
    private function firstArguments($command_args)
    {
        $result = '';
        while (!empty($command_args)) {
            $first = array_shift($command_args);
            if ($first[0] == '-') {
                return $result;
            }
            $result .= " $first";
        }
        return $result;
    }

    /**
     * Escape the command-line args
     *
     * @param string[] $args All of the arguments to escape
     * @param string[]
     */
    private function escapeArguments($args)
    {
        return array_map(
            function ($arg) {
                return $this->escapeArgument($arg);
            },
            $args
        );
    }

    /**
     * Escape one command-line arg
     *
     * @param string $arg The argument to escape
     * @return string
     */
    private function escapeArgument($arg)
    {
        // Omit escaping for simple args.
        if (preg_match('/^[a-zA-Z0-9_-]*$/', $arg)) {
            return $arg;
        }
        return ProcessUtils::escapeArgument($arg);
    }
}
