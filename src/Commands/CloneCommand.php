<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/***
 * Class CloneCommand
 * @package Pantheon\Terminus\Commands
 * TODO: Simplify this and its child class
 */
abstract class CloneCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * @var string[]
     */
    protected $operations = ['cloneFiles', 'cloneDatabase',];
    protected $origin_env;
    protected $site_name;
    protected $target_env;

    /**
     * Clone database and/or files from one environment to another.
     *
     * @param $origin Name of the site and environment to clone from
     * @param $target Name of the target environment
     * @param boolean[] $options Options as follow:
     *  option boolean $db-only Set to only clone the database
     *  option boolean $files-only Set to only clone files
     */
    public function invokeClone($origin, $target, array $options = [])
    {

        $this->setSiteName(explode('.', $origin)[0])
             ->setOriginEnv(explode('.', $origin)[1])
             ->setTargetEnv($target)
             ->setOperations($this->filterOperations($options));
        $this->doOperations($this->getOperations());
    }

    /**
     * @param array $operations
     *
     * @return bool
     */
    protected function doOperations(array $operations = [])
    {
        $site = $this->getSite($this->site_name);
        $progress = $this->startProgressBar($operations);

        $results = array_map(
            function ($operation) use ($site, $progress) {
                $workflow = $this->triggerWorkflow($site, $operation);
                while (!$workflow->isFinished()) {
                    $progress->setMessage("Running {$operation}...");
                    $this->doWorkflow($workflow, $progress);
                }
                return $workflow->getMessage();
            },
            $operations
        );

        $progress->setMessage(
            ucfirst(mb_strtolower(implode(", ", $results)))
        );
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
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function triggerWorkflow(Site $site, $operation = '')
    {
        return $site->getEnvironments()
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
        $progress = $this->io()->createProgressBar(100);
        $progress->setMessage('Setting up...');
        $progress->start();
        return $progress;
    }

    /**
     * Refresh Workflow data, advance progress, and hangout.
     *
     * @param \Terminus\Models\Workflow $workflow
     * @param \Symfony\Component\Console\Helper\ProgressBar $progress
     *
     * @return string
     */
    protected function doWorkflow(Workflow $workflow, ProgressBar $progress)
    {
        $workflow->fetch();
        $progress->advance(1);
        return $workflow->getMessage();
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
