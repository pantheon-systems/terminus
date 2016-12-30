<?php

/**
 * The PluginCommand class manages Terminus plugins.
 */

namespace Pantheon\Terminus\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Manage Terminus plugins.
 *
 * @package Pantheon\Terminus\Commands
 */
class PluginCommand extends TerminusCommand
{

    /**
     * Install one or more Terminus plugins.
     *
     * @command plugin:install
     * @aliases plugin:add
     *
     * @option array $projects A space delimited list of one or more plugin projects to install
     *
     * @usage <URL to plugin archive, Git or Packagist project 1> [URL to plugin archive, Git or Packagist project 2] ...
     */
    public function install(array $projects)
    {
        if (empty($projects)) {
            $message = "Usage: terminus plugin:<install|add>";
            $message .= " <URL to plugin archive, Git or Packagist project 1>";
            $message .= " [URL to plugin archive, Git or Packagist project 2] ...";
            throw new TerminusNotFoundException($message);
        }

        $plugins_dir = $this->getPluginDir();
        foreach ($projects as $project) {
            $is_url = (filter_var($project, FILTER_VALIDATE_URL) !== false);
            if (!$is_url) {
                if (!$this->commandExists('composer')) {
                    $message = "In order to install Packagist plugin projects, you need to install Composer.";
                    $this->log()->notice($message);
                } elseif (!$this->isValidPackagistProject($project)) {
                    $message = "{$project} is not a valid Packagist plugin project.";
                    $this->log()->error($message);
                } else {
                    $path = explode('/', $project);
                    $plugin = $path[1];
                    if (is_dir($plugins_dir . $plugin)) {
                        $message = "{$plugin} plugin is already installed.";
                        $this->log()->notice($message);
                    } else {
                        exec("composer create-project -n -d {$plugins_dir} {$project}:~1", $messages);
                        foreach ($messages as $message) {
                            $this->log()->notice($message);
                        }
                    }
                }
            } else {
                if ($ext = pathinfo(parse_url($project, PHP_URL_PATH), PATHINFO_EXTENSION)) {
                    switch ($ext) {
                        case 'git':
                            $parts = parse_url($project);
                            $path = explode('/', $parts['path']);
                            $plugin = array_pop($path);
                            $repository = $parts['scheme'] . '://' . $parts['host'] . implode('/', $path);
                            if (!$this->isValidGitRepository($repository, $plugin)) {
                                $message = "{$project} is not a valid plugin Git repository.";
                                $this->log()->error($message);
                            } elseif (is_dir($plugins_dir . $plugin)) {
                                $message = "{$plugin} plugin is already installed.";
                                $this->log()->notice($message);
                            } elseif ($this->commandExists('git')) {
                                exec("git clone --branch 1.x {$project} {$plugins_dir}{$plugin}", $messages);
                                foreach ($messages as $message) {
                                    $this->log()->notice($message);
                                }
                            } else {
                                $message = "In order to clone Git repository plugin projects, you need to install Git.";
                                $this->log()->notice($message);
                            }
                            break;

                        case 'gz':
                            if ($this->commandExists('curl') && $this->commandExists('tar')) {
                                exec("curl {$project} -L | tar -C {$plugins_dir} -xvz", $messages);
                            } else {
                                $messages[] = "In order to install archive plugin projects, you need to install curl and tar.";
                            }
                            foreach ($messages as $message) {
                                $this->log()->notice($message);
                            }
                    }
                } else {
                    $message = "{$project} is not a valid plugin project URL.";
                    $this->log()->error($message);
                }
            }
        }
    }

    /**
     * List all installed Terminus plugins.
     *
     * @command plugin:show
     * @aliases plugin:list plugin:display
     *
     * @field-labels
     *   name: Name
     *   location: Location
     *   method: Method
     *   version: Version
     *   description: Description
     *
     * @return RowsOfFields
     */
    public function show()
    {
        $rows = array();
        $plugins_dir = $this->getPluginDir();
        $plugins = $this->getPluginProjects($plugins_dir);
        if (!empty($plugins[0])) {
            $message = "Plugins are installed in {$plugins_dir}.";
            $this->log()->notice($message);
            foreach ($plugins as $plugin) {
                $plugin_dir = $plugins_dir . $plugin;
                if (is_dir("$plugin_dir")) {
                    $method = $this->getInstallMethod($plugin);
                    switch ($method) {
                        case 'git':
                            $remotes = array();
                            exec("cd \"$plugin_dir\" && git remote -v", $remotes);
                            foreach ($remotes as $line) {
                                $parts = explode("\t", $line);
                                if (isset($parts[1])) {
                                    $repo = explode(' ', $parts[1]);
                                    $parts = parse_url($repo[0]);
                                    $path = explode('/', $parts['path']);
                                    $base = array_pop($path);
                                    $repository = $parts['scheme'] . '://' . $parts['host'] . implode('/', $path);
                                    if ($title = $this->isValidGitRepository($repository, $base)) {
                                        $description = '';
                                        $parts = explode(':', $title);
                                        if (isset($parts[1])) {
                                            $description = trim($parts[1]);
                                        }
                                        $version = '';
                                        $composer_info = $this->getComposerInfo($plugin);
                                        if (!empty($composer_info)) {
                                            $version = $composer_info['extra']->terminus->{'compatible-version'};
                                        }
                                        $rows[] = [
                                            'name'        => $plugin,
                                            'location'    => $repository,
                                            'method'      => $method,
                                            'version'     => $version,
                                            'description' => $description,
                                        ];
                                    } else {
                                        $message = "{$repo} is not a valid plugin Git repository.";
                                        $this->log()->error($message);
                                    }
                                    break;
                                } else {
                                    $message = "{$plugin_dir} is not a valid plugin Git repository.";
                                    $this->log()->error($message);
                                }
                            }
                            break;

                        case 'archive':
                        case 'composer':
                        default:
                            $name = $plugin;
                            $location = '';
                            $description = '';
                            $version = '';
                            $composer_info = $this->getComposerInfo($plugin);
                            if (!empty($composer_info)) {
                                $project = $composer_info['name'];
                                $description = $composer_info['description'];
                                $path = explode('/', $project);
                                $name = $path[1];
                                if ($method == 'composer') {
                                    $location = "https://packagist.org/packages/{$path[0]}";
                                } else {
                                    $location = "https://github.com/{$project}/archive/1.x.tar.gz";
                                }
                                $version = $composer_info['extra']->terminus->{'compatible-version'};
                            }
                            $rows[] = [
                                'name'        => $name,
                                'location'    => $location,
                                'method'      => $method,
                                'version'     => $version,
                                'description' => $description,
                            ];
                    }
                }
            }
        }

        if (empty($rows)) {
            $this->log()->notice('You have no plugins installed.');
            return false;
        }

        $count = count($rows);
        $plural = ($count > 1) ? 's' : '';
        $message = "You have {$count} plugin{$plural} installed.  Use 'terminus plugin:install <project>...' to add more plugins.";
        $this->log()->notice($message);

        // Output the plugin list in table format.
        return new RowsOfFields($rows);
    }

    /**
     * Search for available Terminus plugins.
     *
     * @command plugin:search
     * @aliases plugin:find plugin:locate
     *
     * @option string $keyword A search string used to query for plugins. Example: terminus plugin:search pantheon.
     *
     * @return List of search results
     */
    public function search($keyword = '')
    {
        if (empty($keyword)) {
            $message = "Usage: terminus plugin:<search|find|locate> <string>";
            throw new TerminusNotFoundException($message);
        }

        if ($this->commandExists('composer')) {
            exec("composer search -t terminus-plugin {$keyword}", $messages);
            foreach ($messages as $message) {
                if (stripos($message, 'terminus') && stripos($message, 'plugin')) {
                    $this->log()->notice($message);
                }
            }
        } else {
            $message = "In order to search for Packagist project plugins, you need to install Composer.";
            $this->log()->notice($message);
        }
    }

    /**
     * Update one or more Terminus plugins.
     *
     * @command plugin:update
     * @aliases plugin:upgrade plugin:up
     *
     * @option array $plugins A space delimited list of one or more installed plugins to update
     *
     * @usage <plugin-name-1|all> [plugin-name-2] ...
     */
    public function update(array $plugins)
    {
        if (empty($plugins)) {
            $plugins = array('all');
        }

        if ($plugins[0] == 'all') {
            $plugins_dir = $this->getPluginDir();
            $plugins = $this->getPluginProjects($plugins_dir);
            if (empty($plugins[0])) {
                $message = "You have no plugins installed.";
                $this->log()->notice($message);
                return false;
            }
        }
        foreach ($plugins as $plugin) {
            $this->updatePlugin($plugin);
        }
    }

    /**
     * Remove one or more Terminus plugins.
     *
     * @command plugin:uninstall
     * @aliases plugin:remove plugin:delete
     *
     * @option array $plugins A space delimited list of one or more installed plugins to remove
     *
     * @usage <plugin-name-1> [plugin-name-2] ...
     */
    public function uninstall(array $plugins)
    {
        if (empty($plugins)) {
            $message = "Usage: terminus plugin:<uninstall|remove|delete>";
            $message .= " <plugin-name-1> [plugin-name-2] ...";
            throw new TerminusNotFoundException($message);
        }

        foreach ($plugins as $plugin) {
            $plugin = $this->getPluginDir($plugin);
            if (!is_dir("$plugin")) {
                $message = "{$plugin} plugin is not installed.";
                $this->log()->error($message);
            } else {
                exec("rm -rf \"$plugin\"", $messages);
                foreach ($messages as $message) {
                    $this->log()->notice($message);
                }
                $message = "{$plugin} plugin was removed successfully.";
                $this->log()->notice($message);
            }
        }
    }

    /**
     * Get plugin projects.
     *
     * @param string $plugins_dir Plugins directory
     * @return array Plugin projects
     */
    private function getPluginProjects($plugins_dir)
    {
        $projects = array();
        $finder = new Finder();
        $finder->files()->in($plugins_dir);
        foreach ($finder as $file) {
            $path = $file->getRelativePath();
            // Get the parent path only.
            if (!strpos($path, '/')) {
                // Make sure the path is unique.
                if (!in_array($path, $projects)) {
                    $projects[] = $path;
                }
            }
        }
        return $projects;
    }

    /**
     * Get the plugin directory.
     *
     * @param string $plugin Plugin name
     * @return string Plugin directory
     */
    private function getPluginDir($plugin = '')
    {
        $plugins_dir = getenv('TERMINUS_PLUGINS_DIR');
        $windows = (php_uname('s') == 'Windows NT');
        if (!$plugins_dir) {
            // Determine the correct $plugins_dir based on the operating system
            $home = getenv('HOME');
            if ($windows) {
                $system = '';
                if (getenv('MSYSTEM') !== null) {
                    $system = strtoupper(substr(getenv('MSYSTEM'), 0, 4));
                }
                if ($system != 'MING') {
                    $home = getenv('HOMEPATH');
                }
                $home = str_replace('\\', '\\\\', $home);
                $plugins_dir = $home . '\\\\terminus\\\\plugins\\\\';
            } else {
                $plugins_dir = $home . '/.terminus/plugins/';
            }
        } else {
            // Make sure the proper trailing slash(es) exist
            if ($windows) {
                $slash = '\\\\';
                $chars = 2;
            } else {
                $slash = '/';
                $chars = 1;
            }
            if (substr("$plugins_dir", -$chars) != $slash) {
                $plugins_dir .= $slash;
            }
        }
        // Create the directory if it doesn't already exist
        if (!is_dir("$plugins_dir")) {
            mkdir("$plugins_dir", 0755, true);
        }
        return $plugins_dir . $plugin;
    }

    /**
     * Get the plugin installation method.
     *
     * @param string Plugin name
     * @return string Plugin installation method
     */
    private function getInstallMethod($plugin)
    {
        $plugins_dir = $this->getPluginDir($plugin);
        $windows = (php_uname('s') == 'Windows NT');
        if ($windows) {
            $slash = '\\\\';
        } else {
            $slash = '/';
        }
        $git_dir = $plugins_dir . $slash . '.git';
        if (is_dir("$git_dir") && $this->commandExists('git')) {
            return 'git';
        }
        $composer_json = $plugins_dir . $slash . 'composer.json';
        if (file_exists($composer_json)) {
            return $this->commandExists('composer') ? 'composer' : 'archive';
        }
        return 'default';
    }

    /**
     * Get the plugin Composer information.
     *
     * TODO: This could be a generic utility function to use in other places.
     *
     * @param string $plugin Plugin name
     * @return array of Composer information
     */
    private function getComposerInfo($plugin)
    {
        $plugin_dir = $this->getPluginDir($plugin);
        $windows = (php_uname('s') == 'Windows NT');
        if ($windows) {
            $slash = '\\\\';
        } else {
            $slash = '/';
        }
        $composer_json = $plugin_dir . $slash . 'composer.json';
        if (file_exists($composer_json)) {
            $composer_data = @file_get_contents($composer_json);
            return (array)json_decode($composer_data);
        }
        return array();
    }

    /**
     * Platform independent check whether a command exists.
     *
     * TODO: Do we have a generic utility function we could use instead?
     *
     * @param string $command Command to check
     * @return bool True if exists, false otherwise
     */
    private function commandExists($command)
    {
        $windows = (php_uname('s') == 'Windows NT');
        $test_command = $windows ? 'where' : 'command -v';
        $file = popen("$test_command $command", 'r');
        $result = fgets($file, 255);
        return $windows ? !preg_match('#Could not find files#', $result) : !empty($result);
    }

    /**
     * Update a specific plugin.
     *
     * @param string $plugin Plugin name
     */
    private function updatePlugin($plugin)
    {
        $plugins_dir = $this->getPluginDir();
        $plugin_dir = $plugins_dir . $plugin;
        if (!is_dir("$plugin_dir")) {
            $message = "Plugin {$plugin} is not installed.";
            throw new TerminusNotFoundException($message);
        }
        $messages = array();
        $message = "Updating {$plugin} plugin...";
        $this->log()->notice($message);
        $method = $this->getInstallMethod($plugin);
        switch ($method) {
            case 'git':
                exec("cd \"$plugin_dir\" && git pull", $messages);
                break;

            case 'composer':
                $composer_info = $this->getComposerInfo($plugin);
                $project = $composer_info['name'];
                exec("rm -rf \"{$plugin_dir}\" && composer create-project -n -d {$plugins_dir} {$project}:~1", $messages);
                break;

            case 'archive':
            default:
                if ($this->commandExists('curl') && $this->commandExists('tar')) {
                    $project = 'unknown';
                    $composer_info = $this->getComposerInfo($plugin);
                    if (!empty($composer_info)) {
                        $project = $composer_info['name'];
                    }
                    $archive_url = "https://github.com/{$project}/archive/1.x.tar.gz";
                    exec("rm -rf \"{$plugin_dir}\" && curl {$archive_url} -L | tar -C {$plugins_dir} -xvz", $messages);
                } else {
                    $messages[] = "In order to update archive plugin projects, you need to install curl and tar.";
                }
        }
        foreach ($messages as $message) {
            $this->log()->notice($message);
        }
    }

    /**
     * Check whether a Git repository is valid.
     *
     * @param string $repository Repository URL
     * @param string $plugin Plugin name
     * @return string Plugin title, if found, otherwise, empty string
     */
    private function isValidGitRepository($repository, $plugin)
    {
        // Make sure the URL is valid
        $is_url = (filter_var($repository, FILTER_VALIDATE_URL) !== false);
        if (!$is_url) {
            return '';
        }
        // Make sure a subpath exists
        $parts = parse_url($repository);
        if (!isset($parts['path']) || ($parts['path'] == '/')) {
            return '';
        }
        // Search for a plugin title
        $plugin_data = @file_get_contents($repository . '/' . $plugin);
        if (!empty($plugin_data)) {
            preg_match('|<title>(.*)</title>|', $plugin_data, $match);
            if (isset($match[1])) {
                $title = $match[1];
                if (stripos($title, 'terminus') && stripos($title, 'plugin')) {
                    return $title;
                }
                return '';
            }
            return '';
        }
        return '';
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @param string $project Packagist project name
     * @return bool true if valid, false otherwise
     */
    private function isValidPackagistProject($project)
    {
        $valid = false;
        // Search for the Packagist project
        exec("composer search -N -t terminus-plugin {$project}", $items);
        if (!empty($items)) {
            foreach ($items as $item) {
                if ($item == $project) {
                    $valid = true;
                    break;
                }
            }
        }
        return $valid;
    }

    /**
     * Check whether a URL is valid.
     *
     * @param string $url The URL to check
     * @return bool true if the URL returns a 200 status
     */
    private function isValidUrl($url = '')
    {
        if (!$url) {
            return false;
        }
        $headers = @get_headers($url);
        if (!isset($headers[0])) {
            return false;
        }
        return (strpos($headers[0], '200') !== false);
    }
}
