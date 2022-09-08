<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Plugins\PluginInfo;

/**
 * Search for Terminus plugins to install.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 * @TODO Bonus: Add the ability to search and prompt to install new plugins.
 * @TODO Keep an internal registry of approved third-party plugins.
 * @TODO Do lookup if given a plugin name and not a project name, prompt OK for match, install
 */
class SearchCommand extends PluginBaseCommand
{
    const APPROVED_PROJECTS = 'terminus-plugin-project/terminus-pancakes-plugin';
    const NO_PLUGINS_MESSAGE = 'No compatible plugins have met your criterion.';
    const OFFICIAL_PLUGIN_AUTHOR = 'pantheon-systems';
    const SEARCH_COMMAND = 'composer search -d {dir} -t terminus-plugin {keyword}';
    const PROJECT_URL = 'https://repo.packagist.org/p2/{project}.json';
    const PROJECT_DEV_URL = 'https://repo.packagist.org/p2/{project}~dev.json';

    /**
     * Search for available Terminus plugins.
     *
     * @command self:plugin:search
     * @aliases self:plugin:find self:plugin:locate plugin:search plugin:find plugin:locate
     *
     * @param string $keyword A search string used to query for plugins
     *
     * @field-labels
     *     name: Name
     *     status: Status
     *     description: Description
     * @usage <plugin> Searches for Terminus plugins with "plugin" in the name.
     *
     * @return RowsOfFields
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function search($keyword)
    {
        $command = str_replace('{keyword}', $keyword, self::SEARCH_COMMAND);
        $command = self::populateComposerWorkingDir($command, $this->getTerminusDependenciesDir());
        $results = explode(
            PHP_EOL,
            str_replace(' - ', ' ', trim($this->runCommand($command)['output']))
        );

        $projects = array_map(
            function ($message) {
                list($project, $description) = explode(' ', $message, 2);
                return [
                    'name' => $project,
                    'status' => self::checkStatus($project),
                    'description' => $description,
                ];
            },
            array_filter(
                $results,
                function ($message) {
                    list($project) = explode(' ', $message, 2);
                    if (preg_match('#^[^/]*/[^/]*$#', $project)) {
                        $url = str_replace('{project}', $project, self::PROJECT_URL);
                        $json = json_decode(file_get_contents($url), true, 10);
                        if ($this->validatePackageVersions($json['packages'][$project])) {
                            return true;
                        }
                        $url = str_replace('{project}', $project, self::PROJECT_DEV_URL);
                        $json = json_decode(file_get_contents($url), true, 10);
                        if ($this->validatePackageVersions($json['packages'][$project])) {
                            return true;
                        }
                    }
                    return false;
                }
            )
        );

        if (empty($projects)) {
            $this->log()->warning(self::NO_PLUGINS_MESSAGE);
        }

        // Output the plugin list in table format.
        asort($projects);
        return new RowsOfFields($projects);
    }

    /**
     * Validate package versions against terminus major version.
     *
     * @param $versions_array
     *
     * @return bool
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function validatePackageVersions($versions_array)
    {
        $plugin_info = $this->getContainer()->get(PluginInfo::class);
        foreach ($versions_array as $version) {
            $plugin_compatible = $version['extra']['terminus']['compatible-version'] ?? '';
            if (!$plugin_compatible) {
                continue;
            }
            if ($plugin_info->isVersionCompatible($plugin_compatible)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check for minimum plugin command requirements.
     *
     * @hook validate self:plugin:search
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function validate()
    {
        $this->checkRequirements();
    }

    /**
     * Check the project status on Packagist.
     *
     * @param string $project Project name
     * @return string Project status
     */
    protected static function checkStatus($project)
    {
        if (preg_match('#^'. self::OFFICIAL_PLUGIN_AUTHOR . '/#', $project)) {
            return 'Official';
        }

        if (in_array($project, explode('|', self::APPROVED_PROJECTS))) {
            return 'Approved';
        }

        return 'Unofficial';
    }
}
