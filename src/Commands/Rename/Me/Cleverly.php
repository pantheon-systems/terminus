<?php


namespace Pantheon\Terminus\Commands\Rename\Me;

use Pantheon\Terminus\Commands\TerminusCommand;
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

        $this->cmdExecutor = $this->getContainer()->get(LocalMachineHelper::class);
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
        if (
            !$this->sanityCheckWorkingDirectory()
            || !$this->sanityCheckGitInstallation()
            || !$this->checkWorkingTreeClean()
        )
        {
            return 1;
        }

        $this->log()->notice("You're about to make massive changes to your site's codebase.");
        $this->log()->notice("This command will make the changes in a new local git branch called \"convert\".");



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

        if (! file_exists('index.php'))
        {
            $looksGood = false;
        } else {
            if (! strstr(file_get_contents('index.php'), 'DrupalKernel'))
            {
                $looksGood = false;
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
        return $looksGood;
    }

    /**
     * Makes sure that we can invoke git.
     */
    protected function sanityCheckGitInstallation()
    {
        $this->gitExecutable = 'git';
        $result = $this->git('config -l');
        if (
            $result['exit_code'] !== 0
            || !strstr($result['output'], 'core.')
        )
        {
            $this->log()->error("Unable to find git on your system.");
            $this->log()->error("This procedure requires git. Please be sure it is in your PATH and is operational.");
            return false;
        }

        return true;
    }

    protected function checkWorkingTreeClean()
    {
        $result = $this->git('status --porcelain');
        if ($result['exit_code'] !== 0)
        {
            $this->log()->error("Error checking local repository status");
            $this->log()->notice($result['output']);
            return false;
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
            return false;
        }

        return true;
    }

    /**
     * Gets the nicest human-readable name for the current HEAD.
     *
     * @return string
     */
    protected function getCurrentRefName()
    {
        $this->git('rev-parse --abbrev-ref HEAD');
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
}
