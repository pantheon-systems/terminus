<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AliasesDrushRcBase implements
    AliasEmitterInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Generate the contents for an aliases.drushrc.php file.
     *
     * @param array $alias_replacements
     *
     * @return string
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getAliasContents(array $alias_replacements): string
    {
        $output = Template::process('header.aliases.drushrc.php.twig');
        foreach ($alias_replacements as $replacements) {
            $this->logger->debug('Creating alias: ' . print_r($replacements, true));
            $output .= Template::process('fragment.aliases.drushrc.php.twig', $replacements) . PHP_EOL;
        }

        return $output;
    }
}
