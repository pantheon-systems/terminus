<?php


namespace Pantheon\Terminus\Commands\Rename\Me;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;

/**
 * Class Cleverly
 * @package Pantheon\Terminus\Commands\Rename\Me
 */
class Cleverly extends TerminusCommand
{
    /**
     * @var string
     *   The path to git.
     */
    protected $gitExecutable;

    /**
     * @var string
     *   The branch where we'll make all the changes.
     */
    protected $branch;

    /**
     * @var LocalMachineHelper
     */
    protected $cmdExecutor;

    protected $initialized = false;

    /**
     * Initializes things we might initialize in a constructor.
     *
     * A constructor is not used because it is run before the application container is available.
     */
    protected function init()
    {
        if ($this->initialized)
        {
            return;
        }

        $this->gitExecutable = 'git';
        $this->cmdExecutor = $this->getContainer()->get(LocalMachineHelper::class);
        $this->branch = 'convert';
        $this->initialized = true;
    }

    /**
     * Converts a site built with drops-8 to use Integrated Composer and the drupal-project upstream.
     *
     * @command rename:me:cleverly
     *
     * @usage rename:me:cleverly
     */
    public function cleverly()
    {
        $this->init();

        if (! $this->input()->isInteractive())
        {
            $this->log()->error("This command does not support running with no interaction.");
            return 1;
        }

        try {
            $this->sanityCheckGitInstallation();
            list($step, $ok) = $this->getStateWithOk('step');
            if (! $ok) {
                // Give the state system a test so if for some reason it doesn't work, we fail fast with a decent error.
                $this->persistState('step', 'beginning');
                $step = $this->mustGetState('step');
            }

            switch ($step)
            {
                case 'beginning':
                    $this->sanityCheckWorkingDirectory();
                    $this->checkWorkingTreeClean();

                    $this->log()->notice("You're about to make massive changes to your site's codebase.");
                    $this->log()->notice(
                        "This command will start from {branch} on the local repository and make the changes in a new branch called {newbranch}.",
                        ['branch' => $this->getCurrentRefName(), 'newbranch' => $this->branch]
                    );

                    if (!$this->io()->confirm("Would you like to proceed?", false))
                    {
                        return 1;
                    }

                    $this->persistState('srcRef', $this->mustGit('rev-parse HEAD'));
                    $this->persistState('step', 'fetch-upstream');
                case 'fetch-upstream':
                    $this->checkoutIcUpstream($this->branch);
                    $this->persistState('step', 'copy-drupal-configuration');
                case 'copy-drupal-configuration':
                    $this->copyConfiguration();
                    $this->persistState('step', 'create-pantheon-configuration');
                case 'create-pantheon-configuration':
                    
            }
        } catch (TerminusException $e) {
            return 1;
        }

        return 0;
    }

    /**
     * Superficially verifies that the user is in a working directory that we can convert.
     *
     * @return bool
     */
    protected function sanityCheckWorkingDirectory()
    {
        $looksGood = true;

        if (!file_exists('pantheon.upstream.yml') && !file_exists('pantheon.yml'))
        {
            $looksGood = false;
        }

        // If we are already on the conversion branch, assume user is re-running after a failed partial attempt.
        // On the conversion branch, we expect no index.php as it is a scaffolded artifact.
        $indexphp = 'index.php';
        if($this->getCurrentRefName() != $this->branch)
        {
            if (! file_exists($indexphp))
            {
                $looksGood = false;
            } else {
                if (! strstr(file_get_contents($indexphp), 'DrupalKernel'))
                {
                    $looksGood = false;
                }
            }
        }

        if (! is_dir('.git'))
        {
            $looksGood = false;
        }

        if (! $looksGood)
        {
            $this->log()->error("Site not found in current directory.");
            $this->log()->error("Please run this command from a git clone of an existing drops-8 based site.");
        }

        if (!$looksGood) {
            throw new TerminusException();
        }
    }

    /**
     * Makes sure that we can invoke git.
     */
    protected function sanityCheckGitInstallation()
    {
        $result = $this->git('config -l');
        if (
            $result['exit_code'] !== 0
            || !strstr($result['output'], 'core.')
        )
        {
            $this->log()->error("Unable to find git on your system.");
            $this->log()->error("This procedure requires git. Please be sure it is in your PATH and is operational.");
            throw new TerminusException();
        }
    }

    protected function checkWorkingTreeClean()
    {
        $result = $this->git('status --porcelain');
        if ($result['exit_code'] !== 0)
        {
            $this->log()->error("Error checking local repository status");
            $this->log()->notice($result['output']);
            throw new TerminusException();
        }

        if (strlen(trim($result['output'])))
        {
            $this->log()->error("Local repository has uncommitted changes");
            $this->log()->error("Please commit or stash your work before proceeding.");
            $result = $this->git('status', true);
            if (strlen($result['output']))
            {
                $this->io()->write($result['output'], true);
            }
            throw new TerminusException();
        }
    }

    /**
     * Adds the official Integrated Composer upstream as a remote, and checks it out in a new branch.
     */
    protected function checkoutIcUpstream()
    {
        // idempotently add the ic remote
        $icRemote = $this->git('remote get-url ic');
        if (strlen($icRemote['output']))
        {
            if (trim($icRemote['output']) != 'git@github.com:pantheon-upstreams/drupal-project.git')
            {
                $this->log()->error("Remote {remote} exists but is not pointing to the Integrated Composer upstream.",
                    ['remote' => 'ic']
                );
                $this->log()->error('Please remove this remote and try again.');
                throw new TerminusException();
            }
        } else {
            $this->mustGit('remote add ic git@github.com:pantheon-upstreams/drupal-project.git');
        }

        $this->log()->notice("Fetching the upstream for Integrated Composer...");
        $this->mustGit('fetch ic main', true);
        $this->mustGit(sprintf('checkout -b %s ic/main', escapeshellarg($this->branch)));
    }

    protected function copyConfiguration()
    {
        $this->log()->notice('Checking for drupal configuration files from sites/default/config to keep...');
        if (! is_dir('sites/default/config'))
        {
            // There may be no such thing in the source, which is not an error.
            $this->git(sprintf(
                'checkout %s sites/default/config',
                escapeshellarg($this->mustGetState('srcRef'))
            ));
        }

        if (! is_dir('sites/default/config')) {
            $this->log()->info('No drupal configuration files found.');
            return;
        }

        $this->mustGit('mv sites/default/config/* config');
        // In case of dotfiles that the glob did not move, such as .htaccess is commonly in here
        $this->git('rm -rf sites/default/config');
        $this->mustGit('commit -m "Pull in drupal configuration from source branch"');
        $this->log()->notice('Configuration files found and committed.');
    }

    /**
     * Gets the nicest human-readable name for the current HEAD.
     *
     * @return string
     */
    protected function getCurrentRefName()
    {
        $result = $this->git('rev-parse --abbrev-ref HEAD');
        return trim($result['output']);
    }


    /**
     * Persists a small amount of state by abusing git repository configuration.
     *
     * @param string $name
     * @param string $value
     */
    protected function persistState($name, $value)
    {
        $this->mustGit(sprintf(
            'config --local terminus.icconvert.%s %s',
            escapeshellarg($name),
            escapeshellarg($value)
        ));
    }

    /**
     * Gets a small amount of state by name or throws an exception if no value for the name can be retrieved.
     *
     * @param string $name
     * @throws TerminusException
     */
    protected function mustGetState($name)
    {
        list($value, $ok) = $this->getStateWithOk($name);
        if (! $ok) {
            $this->log()->error("Error accessing state {s} from custom git config attributes.", ['s' => $name]);
            throw new TerminusException();
        }
        return $value;
    }

    /**
     * Gets a small amount of state by abusing git repository configuration.
     *
     * @param string $name
     * @return array
     *   The state value, and whether any value was able to be retrieved.
     */
    protected function getStateWithOk($name)
    {
        $result = $this->git(sprintf(
            'config --local --get terminus.icconvert.%s',
            $name
        ));
        return [trim($result['output']), $result['exit_code'] === 0];
    }

    /**
     * Cleans our state from the git config.
     */
    protected function cleanupState()
    {
        $this->git('config --local --remove-section terminus.icconvert');
    }

    /**
     * Executes git with the provided arguments and returns the command output and exit code.
     *
     * @param string $args
     *   The arguments to git, subject to command interpretation so should be escaped.
     * @return array The command output and exit code
     */
    protected function git($args, $progressIndicatorAllowed = false)
    {
        // execute() does not reliably return command output, so is only used for long-running executions.
        if ($progressIndicatorAllowed)
        {
            return $this->cmdExecutor->execute("$this->gitExecutable $args", null, $progressIndicatorAllowed);
        }

        return $this->cmdExecutor->exec("$this->gitExecutable $args", null);
    }

    /**
     * Executes git with the provided arguments and returns the command output.
     * If the command exit code is not 0, it writes to the log and throws.
     *
     * @param $args
     * @param bool $progressIndicatorAllowed
     * @return string
     * @throws TerminusException
     */
    protected function mustGit($args, $progressIndicatorAllowed = false)
    {
        $result = $this->git($args, $progressIndicatorAllowed);
        if ($result['exit_code'] !== 0) {
            $this->log()->error("Failed running git $args");
            if (strlen($result['output'] . $result['stderr']))
            {
                $this->io->write("{$result['output']}\n{$result['stderr']}", true);
            }
            throw new TerminusException();
        }
        return rtrim($result['output'], "\n");
    }
}
