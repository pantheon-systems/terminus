<?php
namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Terminus\Models\Site;
use Terminus\Models\Workflow;

abstract class CloneCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    const PROGRESS_FORMAT = " <bg=blue;fg=black> %message% </>\n <info>Progress: %bar% <info>%percent:3s%%</info> \n <info>Elapsed: %elapsed:6s% \n Estimated Time Remaining: %estimated:-6s%</info>";
    protected $operations = ['cloneFiles', 'cloneDatabase'];
    protected $origin_env;
    protected $site_name;
    protected $target_env;

    /**
     * Clone database and/or files from one environment to another.
     *
     * @authorized
     *
     * @param $origin
     * @param $target
     *
     * @param array $options
     *
     * @return void
     */
    public function invokeClone($origin, $target, array $options = [])
    {

        $this->setSiteName(explode('.', $origin)[0])
             ->setOriginEnv(explode('.', $origin)[1])
             ->setTargetEnv($target)
             ->setOperations($this->filterOperations($options));
        array_map(
            [$this, 'doOperation'],
            $this->getOperations()
        );
    }

    /**
     * Prepare operation and execute.
     *
     * @param $operation
     *
     * @return bool
     */
    protected function doOperation($operation = '')
    {
        $site = $this->getSite($this->site_name);

        $progress = $this->startProgressBar($operation);
        $workflow = $this->triggerWorkflow($site, $operation);

        while (!$workflow->isFinished()) {
            $this->doWorkflow($workflow, $progress);
        }
        $progress->finish();
        return true;
    }

    /**
     * Configure and trigger the workflow operation.
     *
     * @param \Terminus\Models\Site $site
     *
     * @param string $operation
     *
     * @return \Terminus\Models\Workflow
     * @throws \Terminus\Exceptions\TerminusException
     */
    protected function triggerWorkflow(Site $site, $operation = '')
    {
        return $site->environments
            ->get($this->target_env)
            ->$operation($this->origin_env);
    }

    /**
     * Setup the progress bar.
     *
     * @param string $operation
     *
     * @return \Symfony\Component\Console\Helper\ProgressBar
     */
    protected function startProgressBar($operation = '')
    {
        ProgressBar::setFormatDefinition('minimal', self::PROGRESS_FORMAT);
        $progress = $this->io()->createProgressBar(100);
        $progress->setFormat('minimal');
        $progress->setMessage("Running {$operation}...");
        $progress->start();
        return $progress;
    }

    /**
     * Refresh Workflow data, advance progress, and hangout.
     *
     * @param \Terminus\Models\Workflow $workflow
     * @param \Symfony\Component\Console\Helper\ProgressBar $progress
     *
     * @return bool
     */
    protected function doWorkflow(Workflow $workflow, ProgressBar $progress)
    {
        $workflow->fetch();
        $progress->advance(1);
        sleep(1);
        return true;
    }

    private function setOperations(array $operations = [])
    {
        $this->operations = $operations;
        return $this;
    }

    /**
     * @return array Operations to be performed.
     */
    private function getOperations()
    {
        return $this->operations;
    }

    /**
     * Set the origin environment.
     *
     * @param string $env
     *
     * @return $this
     */
    private function setOriginEnv($env = '')
    {
        $this->origin_env = $env;
        return $this;
    }

    /**
     * Set the target environment.
     *
     * @param string $env
     *
     * @return $this
     */
    private function setTargetEnv($env = '')
    {
        $this->target_env = $env;
        return $this;
    }

    /**
     * Set the site name.
     *
     * @param string $name
     *
     * @return $this
     */
    private function setSiteName($name = '')
    {
        $this->site_name = $name;
        return $this;
    }

    /**
     * Filters out operations a user does not want to perform.
     *
     * @param array $options Options passed from command call.
     *
     * @return array
     */
    private function filterOperations(array $options = [])
    {
        $remove = [];
        if ($options['db-only']) {
            $remove[] = 'cloneFiles';
        } elseif ($options['files-only']) {
            $remove[] = 'cloneDatabase';
        }
        return array_filter(
            $this->getOperations(),
            function ($operation) use ($remove) {
                return !in_array($operation, $remove);
            }
        );
    }
}
