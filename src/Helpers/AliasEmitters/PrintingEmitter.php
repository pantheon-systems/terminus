<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Consolidation\Config\ConfigInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrintingEmitter extends AliasesDrushRcBase
{
    protected $output;

    /**
     * PrintingEmitter constructor
     *
     * @param OutputInterface $output
     * @param ConfigInterface $config
     */
    public function __construct($output, $config)
    {
        parent::__construct($config);

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
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function write(array $alias_replacements)
    {
        $alias_file_contents = $this->getAliasContents($alias_replacements);
        $this->output->writeln($alias_file_contents);
    }
}
