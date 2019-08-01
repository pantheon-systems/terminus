<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Symfony\Component\Filesystem\Filesystem;

abstract class AliasesDrushRcBase implements AliasEmitterInterface
{
    /**
     * Generate the contents for an aliases.drushrc.php file.
     *
     * @param AliasCollection $collection
     *
     * @return string
     */
    protected function getAliasContents(AliasCollection $collection)
    {
        $alias_file_contents = $this->getAliasHeader();

        foreach ($collection->all() as $name => $envs) {
            foreach ($envs->all() as $alias) {
                $alias_fragment = $this->getAliasFragment($alias);
                $alias_file_contents .= $alias_fragment . "\n";
            }
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
    protected function getAliasFragment($alias)
    {
        return Template::process('fragment.aliases.drushrc.php.tmpl', $alias->replacements());
    }
}
