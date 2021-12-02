#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';


$composerFilePath = realpath(dirname(\Composer\Factory::getComposerFile()));

$composerContents = new \Pantheon\Terminus\Helpers\Composer\ComposerFile(
    $composerFilePath . DIRECTORY_SEPARATOR . "composer.json"
);
$outputPath = $composerFilePath . DIRECTORY_SEPARATOR . "package";
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
    ->setInstalledSize(27648)
    ->setArchitecture('noarch')
    ->setMaintainer("Terminus 3", "terminus3@pantheon.io")
    ->setProvides($package)
    ->setDescription($composerContents->get('description'));

$packager = new \wdm\debian\Packager();

$packager->setOutputPath($outputPath);
$packager->setControl($control);
$packager->addMount("{$composerFilePath}/terminus", "/usr/bin/terminus");

//Creates folders using mount points
$packager->run();

//Get the Debian package command
echo $packager->build();
exec($packager->build(), $result, $status);

if ($status !== 0) {
    throw new \Exception(join(PHP_EOL, $result));
}
