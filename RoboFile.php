<?php


use Consolidation\AnnotatedCommand\CommandFileDiscovery;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Helpers\CommandCoverageReport;
use Pantheon\Terminus\Helpers\Composer\ComposerFile;
use Pantheon\Terminus\Terminus;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use wdm\debian\control\StandardFile;

/**
 * Housekeeping tasks for Terminus.
 *
 * Class RoboFile
 */
class RoboFile extends \Robo\Tasks
{
    use ConfigAwareTrait;

    /**
     * @var Terminus
     */
    protected Terminus $terminus;


    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        $this->setTerminus(Terminus::factory());
        $this->setConfig($this->terminus->getConfig());
    }

    /**
     * @param string $file
     * @return \Robo\Result
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function doc($file = null)
    {
        //TODO: change this to real documentation building from phpdoc
        $readme = (string) CommandCoverageReport::factory();
        if ($file) {
            file_put_contents($file, $readme);
            $readme = "./README.md regenerated.";
        }
        $this->output()->writeln($readme);
    }

    /**
     * @param string $file
     * @return \Robo\Result
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function coverage($file = null)
    {
        $readme = CommandCoverageReport::factory();
        if ($file) {
            file_put_contents($file, $readme);
            $readme = './README.md regenerated.';
        }
        $this->output()->writeln($readme);
    }

    /**
     * Updates $terminusPluginsDependenciesVersion variable.
     */
    public function updateDependenciesversion()
    {
        $this->say('Updating terminus dependencies version.');
        $composerLockContents = file_get_contents($this->getProjectPath() . DIRECTORY_SEPARATOR . 'composer.lock');
        $composerLockJson = json_decode($composerLockContents, true, 10);
        $hash = substr($composerLockJson['content-hash'], 0, 7);
        $binFileContents = file_get_contents('bin/terminus');
        $newBinFileContents = preg_replace("/(terminusPluginsDependenciesVersion\s?=)(.*)/m", "$1 '${hash}';", $binFileContents);
        if ($newBinFileContents && $newBinFileContents !== $binFileContents) {
            file_put_contents('bin/terminus', $newBinFileContents);
        }
    }

    /**
     * @return mixed|null
     * @throws Exception
     */
    public function bundleLinux()
    {
        $this->say('Building DEBIAN/UBUNTU package.');

        $terminus_binary = sprintf('%s/terminus', $this->getProjectPath());
        $dpkg_installed_size = ceil(filesize($terminus_binary) / 1024);

        $outputPath = $this->getProjectPath() . DIRECTORY_SEPARATOR . 'package';
        // We need the output path empty.
        if (is_dir($outputPath)) {
            exec(sprintf('rm -Rf %s', $outputPath));
            mkdir($outputPath);
        }

        $composerJson = json_decode(
            file_get_contents($this->getProjectPath() . DIRECTORY_SEPARATOR . 'composer.json'),
            true
        );

        [$vendor, $package] = explode('/', $composerJson['name']);
        // Create a config object.
        $config = $this->getConfig();

        $control = new StandardFile();
        $control
            ->setPackageName($package)
            ->setVersion($config->get('version'))
            ->setDepends(['php7.4', 'php7.4-cli', 'php7.4-xml'])
            ->setInstalledSize($dpkg_installed_size)
            ->setArchitecture('all')
            ->setMaintainer('Terminus', 'terminus@pantheon.io')
            ->setProvides($package)
            ->setDescription($composerJson['description']);

        $packager = new \wdm\debian\Packager();

        $packager->setOutputPath($outputPath);
        $packager->setControl($control);
        $packager->addMount($terminus_binary, '/usr/bin/terminus');

        //Creates folders using mount points
        $packager->run();

        // Get the Debian package command
        // Expectation is that this is a command line invocation for dpkg
        $packageCommand = $packager->build();
        $this->say($packageCommand);

        // OS Check... if running on OS that is not linux,
        // run the build in Docker.
        $status = null;
        exec($packageCommand, $result, $status);

        if ($status !== 0) {
            throw new \Exception(join(PHP_EOL, $result));
        }
        if (!is_array($result)) {
            $result = [$result];
        }
        // Package should be last line of output from command
        $packageFile = array_shift($result);
        $this->say('Package created: ' . $packageFile);
        return $packageFile;
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function bundleMac()
    {
        $context = [];
        // Create a config object.
        $config = $this->getConfig();

        $context['version'] = $config->get('version');
        $context['download_url'] = '***TBD***';
        $context['sha256'] = '***TBD***';
        $loader = new FilesystemLoader($config->get('root') . DIRECTORY_SEPARATOR . 'templates');
        $twig = new Environment($loader, [
            'cache' => false
        ]);
        $twig->getExtension(\Twig\Extension\EscaperExtension::class)
            ->setDefaultStrategy('url');
        $formulaFolder = $config->get('root') . DIRECTORY_SEPARATOR . 'Formula';
        if (is_dir($formulaFolder)) {
            exec("rm -rf $formulaFolder");
        }
        mkdir($formulaFolder);
        file_put_contents(
            $formulaFolder . DIRECTORY_SEPARATOR . 'terminus.rb',
            $twig->render('homebrew-receipt.twig', $context)
        );
        $this->say("Mac Formula Created");
    }

    /**
     * @return Terminus
     */
    public function getTerminus(): Terminus
    {
        return $this->terminus;
    }

    /**
     * @param Terminus $terminus
     */
    public function setTerminus(Terminus $terminus): void
    {
        $this->terminus = $terminus;
    }

    /**
     * Returns the absolute path to the project.
     *
     * @return string
     */
    private function getProjectPath(): string
    {
        return dirname(__FILE__);
    }
}
