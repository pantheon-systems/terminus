<?php

namespace Pantheon\Terminus\Bundlers;

use Composer\Script\Event;
use Robo\Common\InputAwareTrait;
use Robo\Common\IO;
use Robo\Common\OutputAwareTrait;
use Robo\Common\TaskIO;

/**
 * Class LinuxBundler
 *
 * @package Pantheon\Terminus\Bundlers
 */
class LinuxBundler implements BundlerInterface
{

    use IO;

    /**
     * LinuxBundler constructor.
     *
     * @param \Composer\Script\Event $event
     */
    public function __construct(Event $event)
    {
        $this->io = $event->getIO();
    }

    /**
     * @param \Composer\Script\Event $event
     *
     * @return string|null
     * @throws \Exception
     */
    public static function bundle(Event $event): ?string
    {
        $runner = new static($event);
        return $runner->run();
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function run(): ?string
    {
        $this->say("Building DEBIAN/UBUNTU package.");
        $composerFilePath = realpath(dirname(\Composer\Factory::getComposerFile()));

        $composerContents = new \Pantheon\Terminus\Helpers\Composer\ComposerFile(
            $composerFilePath . DIRECTORY_SEPARATOR . "composer.json"
        );
        $outputPath = $composerFilePath . DIRECTORY_SEPARATOR . "package";
        // We need the output path empty.
        if (is_dir($outputPath)) {
            exec(sprintf("rm -Rf %s", $outputPath));
            mkdir($outputPath);
        }

        $name = $composerContents->getName();

        [$vendor, $package] = explode("/", $name);
        // Create a config object.
        $config = new \Pantheon\Terminus\Config\DefaultsConfig();
        $config->extend(new \Pantheon\Terminus\Config\YamlConfig($config->get('root') . '/config/constants.yml'));
        $config->extend(new \Pantheon\Terminus\Config\YamlConfig($config->get('user_home') . '/.terminus/config.yml'));
        $config->extend(new \Pantheon\Terminus\Config\DotEnvConfig(getcwd()));
        $config->extend(new \Pantheon\Terminus\Config\EnvConfig());

        $control = new \wdm\debian\control\StandardFile();
        $control
            ->setPackageName($package)
            ->setVersion($config->get('version'))
            ->setDepends(["php7.4", "php7.4-cli"])
            ->setInstalledSize(27648)
            ->setArchitecture('noarch')
            ->setMaintainer("Terminus 3", "terminus3@pantheon.io")
            ->setProvides($package)
            ->setDescription($composerContents->get('description'));

        $packager = new \wdm\debian\Packager();

        $packager->setOutputPath($outputPath);
        $packager->setControl($control);
        $packager->addMount("{$composerFilePath}/t3", "/usr/bin/t3");

        //Creates folders using mount points
        $packager->run();

        // Get the Debian package command
        // Expectation is that this is a command line invocation for dpkg
        $packageCommand = $packager->build();
        $this->say($packageCommand);

        // OS Check... if running on OS that is not linux,
        // run the build in Docker.
        $status = null;
        switch (strtolower(PHP_OS)) {
            case "linux":
                exec($packageCommand, $result, $status);
                break;


            case "darwin":
            default:
                $command = sprintf("docker exec -it php:7.4-cli '%s'", $packageCommand);
                exec($command, $result, $status);
        }
        if ($status !== 0) {
            throw new \Exception(join(PHP_EOL, $result));
        }
        if (!is_array($result)) {
            $result = [$result];
        }
        // Package should be last line of output from command
        $packageFile = array_shift($result);
        $this->say("Package created: " . $packageFile);
        return $packageFile;
    }
}
