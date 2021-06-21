<?php

require_once  __DIR__ . '/../vendor/autoload.php';

$composerFilePath = realpath(dirname(\Composer\Factory::getComposerFile()));

$composerContents = new \Pantheon\Terminus\Helpers\Composer\ComposerFile(
    $composerFilePath . DIRECTORY_SEPARATOR . "composer.json"
);
$outputPath = $composerFilePath . DIRECTORY_SEPARATOR . "package";

// Create a config object.
$config = new \Pantheon\Terminus\Config\DefaultsConfig();
$config->extend(new \Pantheon\Terminus\Config\YamlConfig($config->get('root') . '/config/constants.yml'));
$config->extend(new \Pantheon\Terminus\Config\YamlConfig($config->get('user_home') . '/.terminus/config.yml'));
$config->extend(new \Pantheon\Terminus\Config\DotEnvConfig(getcwd()));
$config->extend(new \Pantheon\Terminus\Config\EnvConfig());

$control = new \wdm\debian\control\StandardFile();
$control
    ->setPackageName($composerContents->get('name'))
    ->setVersion($config->get('version'))
    ->setDepends(array("php7.4", "php7.4-cli"))
    ->setInstalledSize(27648)
    ->setMaintainer("Terminus 3", "terminus3@pantheon.io")
    ->setProvides("pantheon-systems-terminus")
    ->setDescription($composerContents->get('description'));
;

$packager = new \wdm\debian\Packager();
mkdir($composerFilePath . DIRECTORY_SEPARATOR . "package");
$packager->setOutputPath($outputPath);
$packager->setControl($control);
$packager->addMount("{$composerFilePath}/t3", "/usr/bin/t3");

//Creates folders using mount points
$packager->run();

//Get the Debian package command
echo $packager->build();
