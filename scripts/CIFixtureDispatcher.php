<?php

namespace Pantheon\Terminus\CI;

use Exception;
use Kint\Kint;
use Pantheon\Terminus\CI\Traits\TerminusBinaryTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Process\Process;
use Symfony\Contracts\EventDispatcher\Event;

/**
 *
 * Class CIDispatcher
 * @package Pantheon\Terminus\CI
 * @mixin CIApplication
 * @mixin CICommandBase
 * @mixin TerminusBinaryTrait
 */
class CIFixtureDispatcher extends EventDispatcher
{
    /**
     * @var string[]
     */
    protected static $distributionList = [
        'drupal-composer-managed',
        'wordpress',
    ];

    /**
     * To add another fixture, add another item to the array. It will be created and deleted
     * in the order it appears in the array.
     *
     * @var array
     */
    static string $createFixtureCommand = '{{BIN}} site:create {{SITENAME}} {{SITENAME}} drupal-composer-managed --org={{TRANSIENT_CI_SITES_ORG}}';

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->addListener(ConsoleEvents::COMMAND, [$this, 'startupEventResponder']);
        $this->addListener(ConsoleEvents::TERMINATE, [$this, 'shutdownEventsResponder']);
    }


    /**
     * @param ConsoleCommandEvent $event
     * @return void
     * @throws Exception
     */
    public function startupEventResponder(Event $event)
    {
        $input = $event->getInput();
        $output = $event->getOutput();
        $output->writeln('Creating Fixtures for CI test run');
        foreach (self::$distributionList as $distro) {
            $distroShort = explode('-', $distro)[0];
            $siteName = CIFixtureDispatcher::getFixtureName($distroShort, $input->getOption('jobID'));
            $output->writeln('Creating site ' . $siteName . ' for fixture ' . $distroShort);
            $command = str_replace('{{BIN}}', $input->getOption('bin'), self::$createFixtureCommand);
            $command = str_replace('{{SITENAME}}', $siteName, $command);
            $command = str_replace('{{TRANSIENT_CI_SITES_ORG}}', $input->getOption('org'), $command);
            $output->writeln('Command ' . $command);
            $proc = new Process(explode(' ', $command), null, [], null, null);
            $output->writeln('Running ' . Kint::dump($proc));
            // Write to the buffer as the tests run
            $status = $proc->run(function ($type, $buffer) use ($output) {
                $output->writeln($buffer);
            });
            if ($status !== 0) {
                throw new Exception('Failed to create site for fixture: ' . implode(' ', $command) . ' with output ' . $proc->getOutput());
            }
            $event->getCommand()->addArgument(
                'TERMINUS_SITE_' . strtoupper($distroShort),
                InputOption::VALUE_OPTIONAL,
                null,
                $siteName,
            );
        }
    }

    /**
     * @param ConsoleCommandEvent $event
     * @return void
     */
    public function shutdownEventsResponder(Event $event)
    {
        $input = $event->getInput();
        $output = $event->getOutput();
        $output->writeln('Cleaning up after CI test run');
        foreach (self::$distributionList as $distro) {
            $distroShort = explode('-', $distro)[0];
            $delete_fixture = [
                $input->getOption('bin'),
                'site:delete',
                CIFixtureDispatcher::getFixtureName($distroShort, $input->getOption('jobID')),
                '--yes',
            ];
            $output->writeln(implode(' ', $delete_fixture));
            $proc = new Process($delete_fixture, null, [], null, null);
            // This might fail if the site was never created. Don't sweat it.
            $proc->run(function ($type, $buffer) use ($output) {
                $output->writeln($buffer);
            });
        }
    }


    /**
     * @param string $distroShort
     * @param string $jobId
     * @return string
     */
    public static function getFixtureName(string $distroShort, string $jobId): string
    {
        return sprintf('terminus-%s-%s', $distroShort, $jobId);
    }
}
