<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Consolidation\Config\ConfigAwareInterface;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class AliasesDrushRcBase implements
    AliasEmitterInterface,
    ConfigAwareInterface,
    LoggerAwareInterface
{

    use ConfigAwareTrait;
    use LoggerAwareTrait;

    /**
     * Generate the contents for an aliases.drushrc.php file.
     *
     * @param array $alias_replacements
     *
     * @return string
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function getAliasContents(array $alias_replacements): string
    {
        $path = implode(DIRECTORY_SEPARATOR, [$this->getConfig()->get('root'), 'templates', 'aliases']);
        $loader = new FilesystemLoader($path);
        $twig = new Environment($loader);
        $output = $twig->render('header.aliases.drushrc.php.twig', []);

        foreach ($alias_replacements as $replacements) {
            $this->logger->debug('Creating alias: ' . print_r($replacements, true));
            $output .= $twig->render('fragment.aliases.drushrc.php.twig', $replacements) . PHP_EOL;
        }

        return $output;
    }
}
