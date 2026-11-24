<?php

declare(strict_types=1);

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
            // If we have individual environments (custom domains enabled), render each one
            if (isset($replacements['environments']) && !empty($replacements['environments'])) {
                foreach ($replacements['environments'] as $env_data) {
                    $this->logger->debug('Creating alias: ' . print_r($env_data, true));
                    $output .= Template::process('fragment.aliases.drushrc.php.twig', $env_data) . PHP_EOL;
                }

                // Add wildcard pattern at the end for multidev environments
                $wildcard_data = [
                    'site_name' => $replacements['site_name'],
                    'env_name' => '*',
                    'env_label' => '${env-name}',
                    'site_id' => $replacements['site_id'],
                ];
                $this->logger->debug('Creating wildcard alias: ' . print_r($wildcard_data, true));
                $output .= Template::process('fragment.aliases.drushrc.php.twig', $wildcard_data) . PHP_EOL;
            } else {
                // Otherwise, use the wildcard pattern only (backward compatible)
                $this->logger->debug('Creating alias: ' . print_r($replacements, true));
                $output .= Template::process('fragment.aliases.drushrc.php.twig', $replacements) . PHP_EOL;
            }
        }

        return $output;
    }
}
