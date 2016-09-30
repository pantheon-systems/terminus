<?php

namespace Pantheon\Terminus\Commands\Remote;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

/**
 * Base class for Terminus commands that deal with sending SSH commands
 */
abstract class SSHBaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * @var string Name of the command to be run as it will be used on server
     */
    protected $command = '';

    /**
     * @var string[] A hash of commands which do not work using Terminus.
     *               The key is the native command, and the value is the Terminus equivalent which is optional.
     */
    protected $unavailable_commands = [];

    protected $valid_frameworks = [];

    private $site;
    private $environment;

    protected function prepareEnvironment($site_env_id)
    {
        list($this->site, $this->environment) = $this->getSiteEnv($site_env_id);
    }

    protected function executeCommand(array $command_args)
    {
        $output = '';

        $this->validateEnvironment($this->site, $this->environment);

        if ($this->validateCommand($command_args)) {
            $command_line = $this->getCommandLine($command_args);

            $result = $this->environment->sendCommandViaSsh($command_line);
            $output = $result['output'];
            $exit   = $result['exit_code'];

            $this->log()->info('Command: {site}.{env} -- {command} [Exit: {exit}]', [
                'site'    => $this->site->get('name'),
                'env'     => $this->environment->id,
                'command' => escapeshellarg($command_line),
                'exit'    => $exit,
            ]);

            if ($exit != 0) {
                throw new TerminusException($output);
            }
        }

        return rtrim($output);
    }

    protected function validateCommand(array $command)
    {
        $is_valid = true;
        foreach ($command as $element) {
            if (isset($this->unavailable_commands[$element])) {
                $is_valid       = false;
                $message        = "That command is not available via Terminus. ";
                $message        .= "Please use the native {command} command.";
                $interpolations = ['command' => $this->command];
                if (!empty($alternative = $this->unavailable_commands[$element])) {
                    $message .= " Hint: You may want to try `{suggestion}`.";
                    $interpolations['suggestion'] = "terminus $alternative";
                }
                $this->log()->error($message, $interpolations);
            }
        }

        return $is_valid;
    }

    protected function validateEnvironment($site, $environment)
    {
        $this->validateConnectionMode($environment->get('connection_mode'));
        $this->validateFramework($site->get('framework'));
    }

    protected function validateConnectionMode($mode)
    {
        if ($mode == 'git') {
            $this->log()->notice(
                "This environment is in read-only Git mode. "
                . "If you want to make changes to the codebase of this site "
                . "(e.g. updating modules or plugins), "
                . "you will need to toggle into read/write SFTP mode first."
            );
        }
    }

    protected function validateFramework($framework)
    {
        // print_r([$framework,$this->valid_frameworks]);
        if (!in_array($framework, $this->valid_frameworks)) {
            throw new TerminusException(
                "The {command} command is only available on a sites running {frameworks}. "
                ."The framework for this site is {framework}.",
                [
                    'command'    => $this->command,
                    'frameworks' => implode(", ", $this->valid_frameworks),
                    'framework'  => $framework,
                ]
            );
        }
    }

    private function getCommandLine($command_args)
    {
        array_unshift($command_args, $this->command);

        return implode(" ", $command_args);
    }
}
