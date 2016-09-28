<?php

namespace Terminus\Commands;

use Terminus\Collections\Sites;

/**
 * Base class for Terminus commands that deal with sending SSH commands
 */
abstract class CommandWithSSH extends TerminusCommand
{
  /**
   * @var string Name of the client to run a command on the platform
   */
    protected $client = '';
  /**
   * @var string Name of the command to be run as it will be used on server
   */
    protected $command = '';
  /**
   * @var Environment
   */
    protected $environment;
  /**
   * @var string
   */
    protected $ssh_command;
  /**
   * @var string[] A hash of commands which do not work in Terminus. The key
   *   is the Drush command, and the value is the Terminus equivalent, and
   *   blank if DNE.
   */
    protected $unavailable_commands = [];

  /**
   * Object constructor
   *
   * @param array $options Options to construct the command object
   * @return CommandWithSSH
   */
    public function __construct(array $options = [])
    {
        $options['require_login'] = true;
        parent::__construct($options);
    }

  /**
    * Parent invoke function
    *
    * @param array $args       Parameters from the command line
    * @param array $assoc_args Options from the command line
    * @return void
    */
    public function __invoke($args, $assoc_args)
    {
        $command = array_shift($args);
        $this->ensureCommandIsPermitted($command);

        $sites = new Sites();
        $site = $sites->get($this->input()->siteName(['args' => $assoc_args,]));
        $this->environment = $site->environments->get(
            $this->input()->env(['args' => $assoc_args, 'site' => $site,])
        );
        $this->checkConnectionMode($this->environment);

        $this->ssh_command = "{$this->command} $command";
        $this->log()->info(
            'Running {command} on {site}-{env}',
            [
            'command' => $this->ssh_command,
            'site' => $site->get('name'),
            'env' => $this->environment->id,
            ]
        );
        $this->log()->debug(
            'Command "{command}" is being run.',
            ['command' => escapeshellarg($this->ssh_command),]
        );
    }

  /**
   * Checks to see if the command is not available in Terminus and, if not,
   * it will refer you to an equivalent Terminus command, if such exists.
   *
   * @param string $command The command to be sent to Pantheon via SSH
   * @return void
   */
    protected function ensureCommandIsPermitted($command)
    {
        $command_array = explode(' ', $command);
        foreach ($command_array as $element) {
            if ((strpos($element, '--') === 0) || !isset($this->unavailable_commands[$element])) {
                continue;
            }
            $message = "That command is not available via Terminus. Please run it via {client}";
            if (!empty($alternative = $this->unavailable_commands[$element])) {
                $this->failure(
                    "$message, or you can use `{suggestion}` to the same effect.",
                    ['client' => $this->client, 'suggestion' => "terminus $alternative",]
                );
            } else {
                $this->failure("$message.", ['client' => $this->client,]);
            }
        }
    }

  /**
   * Checks the site's mode and suggests SFTP if it is not set.
   *
   * @param Environment $environment Environment object to check mode of
   * @return void
   */
    protected function checkConnectionMode($environment)
    {
        if (!$environment->get('on_server_development')) {
            $this->log()->warning(
                "Note: This environment is in read-only Git mode. If you want to make changes to the codebase of
                this site (e.g. updating modules or plugins), you will need to toggle into read/write SFTP mode first."
            );
        }
    }
}
