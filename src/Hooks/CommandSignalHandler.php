<?php

namespace Pantheon\Terminus\Hooks;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
use Pantheon\Terminus\Helpers\Utility\TraceId;
use Pantheon\Terminus\Helpers\Utility\Timing;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CommandSignalHandler implements EventSubscriberInterface, RequestAwareInterface, LoggerAwareInterface
{
    use RequestAwareTrait;
    use LoggerAwareTrait;

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::SIGNAL => 'handleSignal'];
    }

    /**
     * Terminate Command hook
     */
    public function handleSignal(ConsoleSignalEvent $event)
    {
        $this->logger->debug('Received signal: {signal}', ['signal' => $event->getHandlingSignal()]);
        $this->logger->debug('Exit code: {exit_code}', ['exit_code' => $event->getExitCode()]);
        $this->logger->notice('Requested command to be terminated; closing things up...');
        $this->trackCommand($event);
        $this->logger->notice('Command terminated.');
    }

    /**
     * Track command.
     */
    public function trackCommand(ConsoleSignalEvent $event)
    {
        $startTime = Timing::getStartTime();
        $exitCode = $event->getExitCode();
        if ($exitCode === 0) {
            // We really want to track this as an error as the command got interrupted
            $exitCode = -100;
        }
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
