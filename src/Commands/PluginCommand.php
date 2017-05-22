<?php

/**
 * The PluginCommand class manages Terminus plugins
 */

namespace Pantheon\Terminus\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Manage Terminus plugins
 *
 * @package Pantheon\Terminus\Commands
 */
class PluginCommand extends TerminusCommand
{

    /**
     * Install plugin(s)
     *
     * @command plugin:install
     * @aliases plugin:add
     *
     * @option project A comma delimited list of one or more URLs to plugin Git repositories or names of Packagist projects
     *
     * @usage terminus plugin:<add|install> --project=<URL to plugin Git repository 1 or Packagist project 1>,[URL to plugin Git repository 2 or Packagist project 2],...
     *   Install (or add) plugins
     */
    public function install(array $options = ['project' => null])
    {
        if (empty($options['project'])) {
            $message = "Usage: terminus plugin:<install|add>";
            $message .= " --project=<URL to plugin Git repository 1 or Packagist project 1>,";
            $message .= "[URL to plugin Git repository 2 or Packagist project 2],...";
            $this->log()->error($message);
            return false;
        }

        $projects = explode(',', $options['project']);
        $plugins_dir = $this->getPluginDir();
        foreach ($projects as $project) {
            $is_url = (filter_var($project, FILTER_VALIDATE_URL) !== false);
            if (!$is_url) {
                if (!$this->isValidPackagistProject($project)) {
                    $message = "{$project} is not a valid Packagist project.";
                    $this->log()->error($message);
                } else {
                    exec("cd \"$plugins_dir\" && composer create-project {$project}", $output);
                }
            } else {
                $parts = parse_url($project);
                $path = explode('/', $parts['path']);
                $plugin = array_pop($path);
                $repository = $parts['scheme'] . '://' . $parts['host'] . implode('/', $path);
                if (!$this->isValidGitRepository($repository, $plugin)) {
                    $message = "{$project} is not a valid plugin Git repository.";
                    $this->log()->error($message);
                } else {
                    if (is_dir($plugins_dir . $plugin)) {
                        $message = "{$plugin} plugin already installed.";
                        $this->log()->notice($message);
                    } else {
                        exec("cd \"$plugins_dir\" && git clone $project --branch 1.x", $output);
                        foreach ($output as $message) {
                            $this->log()->notice($message);
                        }
                    }
                }
            }
        }
    }

    /**
     * List all installed plugins
     *
     * @command plugin:show
     * @aliases plugin:list
     *
     * @field-labels
     *   name: Name
     *   location: Location
     *   description: Description
     * @return RowsOfFields
     *
     * @usage terminus plugin:<list|show>
     *   List all installed plugins
     */
    public function show()
    {
        $plugins_dir = $this->getPluginDir();
        exec("ls \"$plugins_dir\"", $plugins);
        if (empty($plugins[0])) {
            $message = "You have no plugins installed.";
            $this->log()->notice($message);
        } else {
            $rows = array();
            $windows = (php_uname('s') == 'Windows NT');
            if ($windows) {
                $slash = '\\\\';
            } else {
                $slash = '/';
            }
            $message = "Plugins are installed in {$plugins_dir}.";
            $this->log()->notice($message);
            foreach ($plugins as $plugin) {
                $plugin_dir = $plugins_dir . $plugin;
                if (is_dir("$plugin_dir")) {
                    $git_dir = $plugin_dir . $slash . '.git';
                    if (is_dir("$git_dir")) {
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
                                    $rows[] = [
                                        'name'        => $plugin,
                                        'location'    => $repository,
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
                    } else {
                      $composer_json = $plugin_dir . $slash . 'composer.json';
                      if (file_exists($composer_json)) {
                          // TODO: Display the details of the Packagist project.
                          // Might be able to use a YAML parser.
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
            $message = "You have {$count} plugin{$plural} installed.  Use 'terminus plugin:install --project=...' to add more plugins.";
            $this->log()->notice($message);

            // Output the plugin list in table format.
            return new RowsOfFields($rows);
        }
    }

    /**
     * Update plugin(s)
     *
     * @command plugin:update
     * @aliases plugin:up
     *
     * @option name A comma delimited list of one or more installed plugins to update
     *
     * @usage terminus plugin:<update|up> --name=<plugin-name-1|all>,[plugin-name-2],...
     *   Update plugin(s)
     */
    public function update(array $options = ['name' => null])
    {
        if (empty($options['name'])) {
            $message = "Usage: terminus plugin:<update|up>";
            $message .= " --name=<plugin-name-1|all>,";
            $message .= "[plugin-name-2],...";
            $this->log()->error($message);
            return false;
        }

        $plugins = explode(',', $options['name']);
        if ($plugins[0] == 'all') {
            $plugins_dir = $this->getPluginDir();
            exec("ls \"$plugins_dir\"", $output);
            if (empty($output[0])) {
                $message = "You have no plugins installed.";
                $this->log()->notice($message);
            } else {
                foreach ($output as $plugin) {
                    $this->updatePlugin($plugin);
                }
            }
        } else {
            foreach ($plugins as $plugin) {
                $this->updatePlugin($plugin);
            }
        }
    }

    /**
     * Remove plugin(s)
     *
     * @command plugin:uninstall
     * @aliases plugin:remove
     *
     * @option name A comma delimited list of one or more installed plugins to remove
     *
     * @usage terminus plugin:<remove|uninstall> --name=<plugin-name-1>,[plugin-name-2],...
     *   Remove plugin(s)
     */
    public function uninstall(array $options = ['name' => null])
    {
        if (empty($options['name'])) {
            $message = "Usage: terminus plugin:<uninstall|remove>";
            $message .= " --name=<plugin-name-1>,";
            $message .= "[plugin-name-2],...";
            $this->log()->error($message);
            return false;
        }

        $plugins = explode(',', $options['name']);
        foreach ($plugins as $plugin) {
            $plugin = $this->getPluginDir($plugin);
            if (!is_dir("$plugin")) {
                $message = "{$plugin} plugin is not installed.";
                $this->log()->error($message);
            } else {
                exec("rm -rf \"$plugin\"", $output);
                foreach ($output as $message) {
                    $this->log()->notice($message);
                }
                $message = "{$plugin} plugin was removed successfully.";
                $this->log()->notice($message);
            }
        }
    }

    /**
     * Get the plugin directory
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
     * Update a specific plugin
     *
     * @param string $plugin Plugin name
     */
    private function updatePlugin($plugin)
    {
        $plugin = $this->getPluginDir($plugin);
        if (is_dir("$plugin")) {
            $windows = (php_uname('s') == 'Windows NT');
            if ($windows) {
                $slash = '\\\\';
            } else {
                $slash = '/';
            }
            $git_dir = $plugin . $slash . '.git';
            $message = "Updating {$plugin} plugin...";
            $this->log()->notice($message);
            if (is_dir("$git_dir")) {
                exec("cd \"$plugin\" && git pull", $output);
            } else {
                exec("cd \"$plugin\" && composer update", $output);
            }
            foreach ($output as $message) {
                $this->log()->notice($message);
            }
        }
    }

    /**
     * Check whether a Git repository is valid
     *
     * @param string Repository URL
     * @param string Plugin name
     * @return string Plugin title, if found
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
     * Check whether a Packagist project is valid
     *
     * @param string Packagist name
     * @return boolean true if valid, false otherwise
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
     * Check whether a URL is valid
     *
     * @param string $url The URL to check
     * @return bool True if the URL returns a 200 status
     */
    /*
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
    */
}
