<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Contract\IOAwareInterface;
use Robo\Contract\ConfigAwareInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Common\IO;
use Terminus\Models\Auth;

abstract class TerminusCommand implements
    IOAwareInterface,
    LoggerAwareInterface,
    ConfigAwareInterface,
    SessionAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use IO;
    use SessionAwareTrait;

    /**
     * TerminusCommand constructor
     */
    public function __construct()
    {
    }

    /**
     * Returns a logger object for use
     *
     * @return LoggerInterface
     */
    protected function log()
    {
        return $this->logger;
    }

    /**
     * Outputs basic workflow success/failure messages
     *
     * @param Workflow $workflow Workflow to output message about
     * @param array    $messages Messages to override workflow's defaults:
     *  string success Success message to override workflow default
     *  string failure Failure message to override workflow default
     * @return void
     */
    protected function workflowOutput($workflow, array $messages = [])
    {
        if ($workflow->get('result') == 'succeeded') {
            $message = $workflow->get('active_description');
            if (isset($messages['success'])) {
                $message = $messages['success'];
            }
            $this->log()->info($message);
        } else {
            $message = 'Workflow failed.';
            if (isset($messages['failure'])) {
                $message = $messages['failure'];
            } elseif (!is_null($final_task = $workflow->get('final_task'))) {
                $message = $final_task->reason;
            }
            $this->log()->error($message);
        }
    }
}
