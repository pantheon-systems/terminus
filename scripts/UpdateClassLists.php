<?php

namespace Terminus;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\ConsoleOutput;


class UpdateClassLists
{
    public static function update()
    {
        $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        $commands = static::getCommands(
            $base . 'Commands',
            'Pantheon\Terminus\Commands'
        );
        $hooks = static::getHooks(
            $base . 'Hooks',
            'Pantheon\Terminus\Hooks'
        );
        sort($hooks);
        sort($commands);
        $all = array_merge($hooks, $commands);
        $all = array_map(
            function ($item) {
                return str_replace('\\', '\\\\', var_export($item, true));
            },
            $all
        );

        $lineBreak = "\n            ";
        $commandList = $lineBreak . implode(",$lineBreak", $all);

        $srcPath = $base . 'Terminus.php';
        $contents = file_get_contents($srcPath);

        $header = "// List of all hooks and commands. Update via 'composer update-class-lists'";
        $replacement = $header . "\n" . <<<__EOT__
        \$this->commands = [$commandList
        ];
__EOT__;

        $contents = preg_replace("#{$header}[^;]*;#ms", $replacement, $contents);

        $models = static::getClassesInDir($base . 'Models');
        $collections = static::getClassesInDir($base . 'Collections');

        $header = "// List of all Models and Collections. Update via 'composer update-class-lists'";
        $replacement = $header . "\n" .
            static::generateAddToContainerCode('Models', $models) .
            "\n" .
            static::generateAddToContainerCode('Collections', $collections);

        $contents = preg_replace("#{$header}[^}]*;#ms", $replacement, $contents);

        file_put_contents($srcPath, $contents);
    }

    public static function generateAddToContainerCode($what, $classList)
    {
        sort($classList);

        $lineBreak = "\n        ";
        return
            "{$lineBreak}// {$what}{$lineBreak}" .
            implode(
                $lineBreak,
                array_map(
                    function ($item) {
                        return "\$container->add({$item}::class);";
                    },
                    $classList
                )
            );
    }

    /**
     * Discovers command classes using CommandFileDiscovery
     *
     * @param string[] $options Elements as follow
     *        string path      The full path to the directory to search for commands
     *        string namespace The full namespace associated with given the command directory
     * @return TerminusCommand[] An array of TerminusCommand instances
     */
    public static function getCommands($path, $baseNamespace)
    {
        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php')->setSearchLocations([]);
        return $discovery->discover($path, $baseNamespace);
    }

    /**
     * Discovers hook classes using CommandFileDiscovery
     *
     * @param string[] $options Elements as follow
     *        string path      The full path to the directory to search for hooks
     *        string namespace The full namespace associated with given the hooks directory
     * @return array An array of hook instances
     */
    public static function getHooks($path, $baseNamespace)
    {
        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*.php')->setSearchLocations([]);
        return $discovery->discover($path, $baseNamespace);
    }

    /**
     * Adds every non-abstract class in a directory to the container
     *
     * @param string $path
     */
    public static function getClassesInDir($path)
    {
        $result = [];
        $files = Finder::create()->files()->in($path)->name('*.php');
        foreach ($files as $file) {
            $file = str_replace(PHP_EOL, ' ', file_get_contents($file->getRealpath()));
            if (strpos($file, 'abstract class') === false) {
                preg_match('/namespace (.*?);/', $file, $namespace);
                preg_match('/class (.*?) /', $file, $class);
                $result[] = '\\' . $namespace[1] . '\\' . $class[1];
            }
        }
        return $result;
    }

    public static function getCommandModelsAndCollections()
    {
        $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        $commands = static::getCommands(
            $base . 'Commands',
            'Pantheon\Terminus\Commands'
        );
        $hooks = static::getHooks(
            $base . 'Hooks',
            'Pantheon\Terminus\Hooks'
        );
        $models = static::getClassesInDir($base . 'Models');
        $collections = static::getClassesInDir($base . 'Collections');

        $output = new ConsoleOutput();
        $output->setDecorated(true);
        $commandTable = new Table($output);
        $commandTable->setHeaders(["Commands"]);
        $commandTable->setRows(array_map(
            function ($item) {
                return [ str_replace("Pantheon\Terminus\Commands", "", $item) ];
            },
            $commands
        ));
        $modelsTable = new Table($output);
        $modelsTable->setHeaders(["Models"]);
        $modelsTable->setRows(array_map(
            function ($item) {
                return [str_replace("\Pantheon\Terminus\Models", "", $item)];
            },
            $models
        ));
        $collectionsTable = new Table($output);
        $collectionsTable->setHeaders(["Collections"]);
        $collectionsTable->setRows(array_map(
            function ($item) {
                return [str_replace("\Pantheon\Terminus\Collections", "", $item)];
            },
            $collections
        ));
        $commandTable->render();
        $modelsTable->render();
        $collectionsTable->render();
    }

}
