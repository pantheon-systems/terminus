<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Symfony\Component\Filesystem\Filesystem;

abstract class AliasesDrushRcBase implements AliasEmitterInterface
{
    /**
     * Generate the contents for an aliases.drushrc.php file.
     *
     * @param array $alias_replacements
     * @return string
     */
    protected function getAliasContents(array $alias_replacements)
    {
        $alias_file_contents = $this->getAliasHeader();

        foreach ($alias_replacements as $name => $replacements) {
            $alias_fragment = $this->getAliasFragment($replacements);
            $alias_file_contents .= $alias_fragment . "\n";
        }

        return $alias_file_contents;
    }

    /**
     * Get the header that goes at the beginning of each alias file
     *
     * @return string
     */
    protected function getAliasHeader()
    {
        return Template::load('header.aliases.drushrc.php.tmpl');
    }

    /**
     * Get the template for just one alias record and run the replacements
     *
     * @return string
     */
    protected function getAliasFragment($replacements)
    {
        return Template::process('fragment.aliases.drushrc.php.tmpl', $replacements);
    }
}
