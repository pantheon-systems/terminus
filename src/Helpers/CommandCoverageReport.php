<?php


namespace Pantheon\Terminus\Helpers;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;

/**
 * Class CommandCoverageReport
 *
 * @package Pantheon\Terminus\Helpers
 */
class CommandCoverageReport
{

    /**
     * @var string
     */
    public static $HELP_TEXT = [
        "********************************************************************************",
        "* Once upon a time, terminus had unit tests and those unit tests passed.       *",
        "* Somewhere in it’s history those unit tests were disabled and no longer       *",
        "* worked. For v2.6 we have a single functional test running and because there  *",
        "* hasn’t been much development in Terminus this has sufficed. With this much   *",
        "* change in Terminus we can’t let that continue. In order to have confidence   *",
        "* in this release and move forward with development of the product, We need a  *",
        "* full suite of functional tests.  I’ve written a bunch of new tests and       *",
        "* committed a coverage page keeping track of what’s been written,              *",
        "* what’s passing and what is lacking. This page should be updated with every   *",
        "* commit.                                                                      *",
        "********************************************************************************",
    ];

    /**
     * @var string[]
     */
    public static $statusIcon = [
        "✅",
        "💩",
        "🤮",
        "❌️️",
        "⚠️",
    ];

    /**
     *
     */
    public static function getReport()
    {
        $root_dir = dirname(\Composer\Factory::getComposerFile());
        $commandsFolder = $root_dir . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "Commands";
        $commands = static::getCommands($commandsFolder, "\\Pantheon\\Terminus\\Commands");
        $parsed = static::getTestResults();
        $report = [];
        foreach ($commands as $file => $className) {
            $groupName = static::classToGroupName($className);
            $commandName = static::classToCommandName($className);
            $report[$groupName][$commandName] = $parsed[$groupName][$commandName] ?? "❓";
        }
        krsort($report);
        $report = array_reverse($report);
        $toWrite = "---" . PHP_EOL . "# Terminus Test Coverage #" . PHP_EOL . PHP_EOL . PHP_EOL;
        $toWrite .= join(PHP_EOL, static::$HELP_TEXT) . PHP_EOL . PHP_EOL;
        $toWrite .= 'Legend: ✅ Pass     💩 Bad test     🤮 Exception     ❌ Fail️️     ⚠️ Warning     ❓ Not Written' .
            PHP_EOL . PHP_EOL;
        foreach ($report as $group => $members) {
            # Group line
            $toWrite .= '| ' . str_pad($group, 40, " ") . ' | ' .
                str_pad(" ", 10, " ") . ' | ' . PHP_EOL;
            $toWrite .= '| ' . str_pad(":--- ", 40, " ") . ' | ' .
                str_pad(" :---: ", 10, " ", STR_PAD_BOTH) . ' | ' . PHP_EOL;
            foreach ($members as $command => $icon) {
                $toWrite .= "| " . str_pad($command, 40, " ") . " | " .
                    str_pad($icon, 10, " ") . " | " . PHP_EOL;
            };
            $toWrite .= '| ' . str_pad(":--- ", 40, " ") . ' | ' .
                str_pad(" :---: ", 10, " ", STR_PAD_BOTH) . ' | ' . PHP_EOL;
        }
        file_put_contents(
            $root_dir . DIRECTORY_SEPARATOR . "docs" . DIRECTORY_SEPARATOR . "TestCoverage.md",
            $toWrite
        );
    }

    /**
     * Discovers command classes using CommandFileDiscovery
     *
     * @param string[] $options Elements as follow
     *        string path      The full path to the directory to search for commands
     *        string namespace The full namespace associated with given the command directory
     *
     * @return TerminusCommand[] An array of TerminusCommand instances
     */
    public static function getCommands($path, $baseNamespace)
    {
        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('*Command.php')->setSearchLocations([]);
        return $discovery->discover($path, $baseNamespace);
    }

    /**
     * @param string $file
     */
    public static function getTestResults(string $file = "reports/logfile.xml")
    {
        $toReturn = [];
        $json_string = json_encode(simplexml_load_file(
            static::getRootDir() . DIRECTORY_SEPARATOR . $file
        ));
        $testReport = json_decode($json_string, true);
        foreach ($testReport['test'] as $test) {
            if (isset($test['covers']['@attributes']) ?? false) {
                $test['covers'] = [$test['covers']];
            }
            foreach ($test['covers'] ?? [] as $coveredClass) {
                $commandName = static::classToCommandName($coveredClass['@attributes']['target']);
                $commandGroup = static::classToGroupName($coveredClass['@attributes']['target']);
                $toReturn[$commandGroup][$commandName] = static::$statusIcon[$test['@attributes']['status']];
            }
        }
        return $toReturn;
    }

    /**
     * @return false|string
     */
    public static function getRootDir()
    {
        return realpath(dirname(\Composer\Factory::getComposerFile()));
    }

    /**
     * @param $class
     *
     * @return string
     */
    public static function classToCommandName($class): string
    {
        $exploded_class = explode("\\Commands\\", $class);
        $command_name = end($exploded_class);
        $command_short = str_replace(
            "Command",
            "",
            $command_name
        );
        return $command_short;
    }


    /**
     * @param $class
     *
     * @return string
     */
    public static function classToGroupName($class): string
    {
        $exploded_class = explode("\\Commands\\", $class);
        $exploded_again = explode("\\", $exploded_class[1]);
        return strtolower($exploded_again[0]);
    }
}
