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
     */
    protected function getAliasContents(array $alias_replacements)
    {
        $loader = new FilesystemLoader($this->getConfigValue('root') . DIRECTORY_SEPARATOR . "templates");
        $twig = new Environment($loader, [
            'cache' => false,
        ]);
        $twig->getExtension(\Twig\Extension\EscaperExtension::class)
            ->setDefaultStrategy('url');
        $toReturn = $twig->load('aliases/header.aliases.drushrc.php.twig');

        foreach ($alias_replacements as $name => $replacements) {
            $this->logger->debug("Creating alias: " . print_r($replacements, true));
            $toReturn .= $twig->render('', $replacements) . PHP_EOL;
        }

        return $twig->render('aliases/fragment.aliases.drushrc.php.twig', $alias_replacements);
    }
}
