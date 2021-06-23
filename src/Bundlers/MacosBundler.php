<?php

namespace Pantheon\Terminus\Bundlers;

use Composer\Script\Event;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Config\DefaultsConfig;
use Pantheon\Terminus\Config\DotEnvConfig;
use Pantheon\Terminus\Config\EnvConfig;
use Pantheon\Terminus\Config\YamlConfig;
use Robo\Common\IO;
use Robo\Contract\ConfigAwareInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class MacosBundler
 *
 * @package Pantheon\Terminus\Bundlers
 */
class MacosBundler implements BundlerInterface
{
    use IO;

    /**
     * MacosBundler constructor.
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
     * @throws \Exception
     */
    public static function bundle(Event $event): ?string
    {
        $runner = new static($event);
        return $runner->run();
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        $context = [];
        // Create a config object.
        $config = new DefaultsConfig();
        $config->extend(new YamlConfig($config->get('root') . '/config/constants.yml'));
        $config->extend(new YamlConfig($config->get('user_home') . '/.terminus/config.yml'));
        $config->extend(new DotEnvConfig(getcwd()));
        $config->extend(new EnvConfig());

        $context['version'] = $config->get('version');
        $context['download_url'] = "***TBD***";
        $context['sha256'] = "***TBD***";
        $loader = new FilesystemLoader($config->get('root') . DIRECTORY_SEPARATOR . "templates");
        $twig = new Environment($loader, [
            'cache' => false
        ]);
        $twig->getExtension(\Twig\Extension\EscaperExtension::class)
            ->setDefaultStrategy('url');
        $formulaFolder = $config->get('root') . DIRECTORY_SEPARATOR . "Formula";
        if (is_dir($formulaFolder)) {
            exec("rm -rf $formulaFolder");
        }
        mkdir($formulaFolder);
        file_put_contents(
            $formulaFolder . DIRECTORY_SEPARATOR . "terminus.rb",
            $twig->render('homebrew-receipt.twig', $context)
        );
    }
}
