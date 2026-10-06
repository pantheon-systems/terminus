<?php

namespace Pantheon\Terminus\Hooks;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Process;

/**
 * Class PluginReloader
 *
 * Automatically reloads plugins after self:update command completes successfully.
 *
 * @package Pantheon\Terminus\Hooks
 */
class PluginReloader implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'reloadPluginsAfterUpdate'];
    }

    /**
     * Reload plugins after self:update completes successfully.
     *
     * @param ConsoleTerminateEvent $event
     */
    public function reloadPluginsAfterUpdate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        $exitCode = $event->getExitCode();

        // Only proceed if self:update command completed successfully
        if ($command && $command->getName() === 'self:update' && $exitCode === 0) {
            $this->logger->notice('Running plugin reload after successful update...');

            try {
                // Run the plugin reload command as a separate process
                $process = new Process(['terminus', 'self:plugin:reload']);
                $process->setTimeout(300); // 5 minutes timeout
                $process->run();

                if ($process->isSuccessful()) {
                    $this->logger->notice('Plugins reloaded successfully.');
                } else {
                    $this->logger->warning(
                        'Plugin reload failed: {error}',
                        ['error' => $process->getErrorOutput()]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->warning(
                    'Could not reload plugins after update: {message}',
                    ['message' => $e->getMessage()]
                );
            }
        }
    }
}
