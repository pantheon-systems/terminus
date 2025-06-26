<?php

namespace Pantheon\Terminus\Hooks;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Pantheon\Terminus\Helpers\Utility\TraceId;
use Pantheon\Terminus\Helpers\Utility\Timing;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

class CommandTracker implements EventSubscriberInterface, RequestAwareInterface
{
    use RequestAwareTrait;

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'terminateCommand'];
    }

    /**
     * Terminate Command hook
     */
    public function terminateCommand(ConsoleTerminateEvent $event)
    {
        $startTime = Timing::getStartTime();
        $exitCode = $event->getExitCode();
        $endTime = new \DateTime();
        $duration = $endTime->diff($startTime)->format('%H:%I:%S');
        $data = [
            'exit_code' => $exitCode,
            'duration' => $duration,
            'time_start' => $startTime->format('Y-m-d H:i:s'),
            'time_end' => $endTime->format('Y-m-d H:i:s'),
        ];

        try {
            $result = $this->request->request('track', ['method' => 'POST', 'json' => $data, 'timeout' => 2]);
        } catch (\Exception $e) {
            // Do nothing
        }
    }
}
