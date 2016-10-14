<?php

namespace Pantheon\Terminus\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class WatchCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    const WORKFLOWS_WATCH_INTERVAL = 5;

    /**
     * Streams new and finished workflows to the console
     *
     * @command workflow:watch
     *
     * @param string $site_id Site name to watch workflows on.
     *
     * @usage terminus workflow:watch <site_name>
     *   Watch workflows on site <site_name>
     */
    public function watch($site_id)
    {
        $site = $this->getSite($site_id);
        $date_format = $this->getConfig()->get('date_format');

        // Keep track of workflows that have been printed.
        // This is necessary because the local clock may drift from
        // the server's clock, causing events to be printed twice.
        $started  = [];
        $finished = [];

        $this->log()->notice('Watching workflows...');
        $site->workflows->fetchWithOperations();
        while (true) {
            $last_created_at  = $site->workflows->lastCreatedAt();
            $last_finished_at = $site->workflows->lastFinishedAt();
            sleep(self::WORKFLOWS_WATCH_INTERVAL);
            $site->workflows->fetchWithOperations();

            $workflows = $site->workflows->all();
            foreach ($workflows as $workflow) {
                if (($workflow->get('created_at') > $last_created_at)
                && !in_array($workflow->id, $started)
                ) {
                    array_push($started, $workflow->id);

                    $started_message = 'Started {id} {description} ({env}) at {time}';
                    $started_context = [
                      'id'          => $workflow->id,
                      'description' => $workflow->get('description'),
                      'env'         => $workflow->get('environment'),
                      'time'        => date(
                          $date_format,
                          $workflow->get('started_at')
                      ),
                    ];
                    $this->log()->notice($started_message, $started_context);
                }

                if (($workflow->get('finished_at') > $last_finished_at)
                && !in_array($workflow->id, $finished)
                ) {
                    array_push($finished, $workflow->id);

                    $finished_message
                      = 'Finished workflow {id} {description} ({env}) at {time}';
                    $finished_context = [
                      'id'          => $workflow->id,
                      'description' => $workflow->get('description'),
                      'env'         => $workflow->get('environment'),
                      'time'        => date(
                          $date_format,
                          $workflow->get('finished_at')
                      ),
                    ];
                    $this->log()->notice($finished_message, $finished_context);

                    if ($workflow->get('has_operation_log_output')) {
                            $workflow->fetchWithLogs();
                            $operations = $workflow->operations();
                        foreach ($operations as $operation) {
                            if ($operation->has('log_output')) {
                                $log_msg = sprintf(
                                    "\n------ %s (%s) ------\n%s",
                                    $operation->description(),
                                    $operation->get('environment'),
                                    $operation->get('log_output')
                                );
                                $this->log()->notice($log_msg);
                            }
                        }
                    }
                }
            }
        }
    }
}
