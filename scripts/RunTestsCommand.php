<?php

namespace Pantheon\Terminus\CI;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RunTestsCommand extends CICommandBase
{
    private array $testCommand = [
        "vendor/bin/phpunit",
        "--colors=always",
        "-c ./phpunit.xml",
        "--debug",
        "--group=short",
        "--do-not-cache-result",
        "--verbose",
        "--stop-on-failure",
    ];

    protected function configure()
    {
        $this->setName('run-tests')
            ->setDescription('Run the CI tests')
            ->addArgument('testType', null, 'The type of test to run', 'short')
            ->addOption(
                'bin',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to the terminus binary that is the subject of this CI run',
                $this->getTerminusBinary(),
            )
            ->addOption('jobID', null, InputOption::VALUE_OPTIONAL, 'Unique ID Of the current running CI', uniqid())
            ->addOption('org', null, InputOption::VALUE_OPTIONAL, 'The organization to create the fixtures in', 'transient-ci-sites');

        // Add the CI dispatcher to the application
        // This will create the fixtures before the tests run
    }


    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('Running %s Test cycle', $input->getArgument('testType')));
        $proc = new Process(['composer', 'test:' . $input->getArgument('testType')], $this->getProjectRoot(), [
            'TERMINUS_SITE_DRUPAL' => $input->getArgument('TERMINUS_SITE_DRUPAL'),
            'TERMINUS_SITE_WP' => $input->getArgument('TERMINUS_SITE_WP'),
        ], null, null);
        // Write to the buffer as the tests run
        $test_status = $proc->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });
        if ($test_status !== 0) {
            throw new Exception(sprintf('Tests failed: %s', $proc->getOutput()));
        }
    }
}
