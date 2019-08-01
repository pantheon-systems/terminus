<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Symfony\Component\Filesystem\Filesystem;

class PrintingEmitter extends AliasesDrushRcBase
{
    protected $output;

    /**
     * PrintingEmitter constructor
     *
     * @param OutputInterface $output
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function notificationMessage()
    {
        return 'Displaying Drush 8 alias file contents.';
    }

    /**
     * {@inheritdoc}
     */
    public function write(AliasCollection $collection)
    {
        $alias_file_contents = $this->getAliasContents($collection);
        $this->output->writeln($alias_file_contents);
    }
}
