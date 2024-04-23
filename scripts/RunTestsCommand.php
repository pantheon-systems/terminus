<?php

namespace Pantheon\Terminus\CI;

use DirectoryIterator;
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
                InputOption::VALUE_REQUIRED,
                'The path to the terminus binary that is the subject of this CI run',
                $this->getTerminusBinary(),
            )
            ->addOption(
                'jobID',
                null,
                InputOption::VALUE_REQUIRED,
                'Unique ID Of the current running CI',
                uniqid()
            )
            ->addArgument(
                'terminusOrg',
                InputOption::VALUE_REQUIRED,
                'The organization to create the fixtures in',
                'transient-ci-sites'
            )
            ->addArgument(
                'terminusSiteDrupal',
                InputOption::VALUE_REQUIRED,
                'The site to run the Drupal tests on',
                'terminus-test-site'
            )
            ->addArgument(
                'terminusSiteWordpress',
                InputOption::VALUE_REQUIRED,
                'The site to run the WP tests on',
                'terminus-test-site-wp'
            )
            ->addArgument(
                'terminusHost',
                InputOption::VALUE_REQUIRED,
                'The terminus host to run the tests on',
                getenv('TERMINUS_HOST') ?? 'terminus.pantheon.io'
            )
            ->addArgument(
                'terminusToken',
                InputOption::VALUE_REQUIRED,
                'The terminus token to run the tests on',
                $this->getTokenDefaultValue()
            )
            ->addArgument(
                'terminusVerifyHostCert',
                InputOption::VALUE_REQUIRED,
                'The terminus host to run the tests on',
                getenv('TERMINUS_VERIFY_HOST_CERT') ?? 'true'
            )
            ->addArgument(
                'terminusPort',
                InputOption::VALUE_REQUIRED,
                'Port to use for terminus calls',
                getenv('TERMINUS_PORT') ?? '443'
            );

        // Add the CI dispatcher to the application
        // This will create the fixtures before the tests run
    }


    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            sprintf(
                'Running tests...arguments %s ...options %s',
                print_r($input->getArguments(), true),
                print_r($input->getOptions(), true)
            )
        );
        $output->writeln('Running tests: %s', print_r($input->getArguments(), true));
        $command = array_merge($this->testCommand, [
            '--group=' . $input->getArgument('testType'),
        ]);
        $output->writeln(sprintf('Running tests command: %s', implode(' ', $command)));
        $proc = new Process($command, $this->getProjectRoot(), [
            'TERMINUS_HOST' => $input->getArgument('terminusHost'),
            'TERMINUS_ORG' => $input->getArgument('terminusOrg'),
            'TERMINUS_PORT' => $input->getArgument('terminusPort'),
            'TERMINUS_SITE_DRUPAL' => $input->getArgument('terminusSiteDrupal'),
            'TERMINUS_SITE_WP' => $input->getArgument('terminusSiteWordpress'),
            'TERMINUS_VERIFY_HOST_CERT' => $input->getArgument('terminusVerifyHostCert'),
        ], $input, null);
        $output->writeln(
            sprintf('Running tests command: %s', implode(' ', $proc->getCommandLine()))
        );
        // Write to the buffer as the tests run
        $test_status = $proc->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });
        if ($test_status !== 0) {
            throw new Exception(sprintf('Tests failed: %s', $proc->getOutput()));
        }
    }


    private function getTokenDefaultValue()
    {
        if (!empty(getenv('TERMINUS_TOKEN'))) {
            return getenv('TERMINUS_TOKEN');
        }
        if (is_dir(getenv('HOME') . '/.terminus/cache/tokens')) {
            $dir = new DirectoryIterator(getenv('HOME')  . '/.terminus/cache/tokens');
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot()) {
                    $userInfo = json_decode(file_get_contents($fileinfo->getPathname()));
                    return $userInfo->token ?? '';
                }
            }
        }
        return '';
    }
}
