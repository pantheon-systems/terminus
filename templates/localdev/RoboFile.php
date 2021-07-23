<?php

/**
 * Automate common housekeeping tasks.
 *
 * Class RoboFile
 */
class RoboFile extends \Robo\Tasks
{

  /**
   * Stand up a docker instance from the site docker-compose.yml file
   */
  public function dockerUp()
  {
    $this->taskExec('docker compose up -d')->run();
  }

  /**
   * Bring down all containers in the docker-compose.yml
   */
  public function dockerDown()
  {
    $this->taskExec('docker compose down')->run();
  }

  /**
   * Clean the cruft from your docker installation
   */
  public function dockerClean()
  {
    $this->taskExec('docker system prune -f')->run();
    $this->taskExec('docker container prune  -f')->run();
    $this->taskExec('docker image prune -f')->run();
    $this->taskExec('docker network prune -f')->run();
    $this->taskExec('docker volume prune -f')->run();
  }

  /**
   * Install drupal using a profile.
   *
   * @param string $profile 'demo_umami'
   *
   * @throws \Exception
   */
  public function siteInstall(string $profile = 'demo_umami')
  {
    // TODO: wait for mysql service to be avail in container
    $project = getenv('PROJECT_NAME');
    $container = "{$project}-php";
    $this->stopOnFail(true);
    $this->confirm(
      "Type 'y' to erase the database in the docker container and re-install drupal with the '{$profile}' profile"
    );
    $this->taskExec('rm -Rf web/sites/default/files web/sites/default/temp web/sites/default/private')
      ->run();
    $this->waitForContainer("{$project}-mysql");
    $this->taskDockerExec($container)
      ->interactive(true)
      ->exec("drush site:install --account-name=demo --site-name={$project} --locale=en --yes  {$profile}")
      ->run();
    $this->siteEnableModules([
      'redis',
      'search_api',
      'search_api_solr',
      'search_api_pantheon',
    ]);
  }

  /**
   * @param string $container
   * @param int $retries
   *
   * @throws \Exception
   */
  protected function waitForContainer(string $container, $retries = 10)
  {
    $iterations = 0;
    $status = $this->getContainerHealth($container);
    while ($status != 'healthy') {
      sleep(10);
      if ($iterations >= $retries) {
        throw new \Exception(
          "Service {$container} was not available after {$retries} retries"
        );
      }
      $iterations += 1;
      $status = $this->getContainerHealth($container);
    }
  }

  /**
   * @param string $container
   *
   * @return string|null
   */
  protected function getContainerHealth(string $container): ? string
  {
    $response = shell_exec(
      "docker inspect {$container} | jq -r '.[].State.Health.Status'"
    );
    return trim(str_replace(PHP_EOL, '', $response));
  }

  /**
   * @param array|string[] $modules
   *
   * @return Robo\Result
   */
  protected function siteEnableModules(array $modules = ['redis'])
  {
    return $this->dockerDrush('pm-enable --yes ' . join(' ', $modules));
  }

  /**
   * Send a command to the docker PHP container's drush.
   *
   * @aliases drush dd
   * @param string $drushCommand
   * @param string $container 'php'
   *
   * @return \Robo\Result
   */
  public function dockerDrush(string $drushCommand = 'site:status', string $container = 'php')
  {
    return $this->taskDockerExec(getenv('PROJECT_NAME') . '-' . $container)
      ->interactive(true)
      ->exec('drush ' . $drushCommand)
      ->run();
  }

  /**
   * @param string $siteUser
   *
   */
  public function siteLogin(string $siteUser = 'admin')
  {
    $url = (string)$this->dockerDrush('uli ' . $siteUser)
      ->getOutputData();
    $this->taskOpenBrowser($url);
  }


  /**
   * Copy these templates back to terminus project
   * TODO: remove this function before production
   */
  public function copyBackTemplates()
  {
    $templateDir = getenv('HOME') . '/Projects/terminus/templates/localdev';
    copy(__DIR__ . '/docker-compose.yml',  $templateDir . "/docker-compose.yml");
    copy(__DIR__ . '/RoboFile.php',  $templateDir . "/RoboFile.php" );
    copy(__DIR__ . '/web/sites/default/settings.local.php',  $templateDir . "/settings.local.php" );
    copy(__DIR__ . '/.envrc', $templateDir . '/.envrc');
  }

  /**
   * Gets the database from Pantheon.
   *
   * @param string $env
   * @author @megclaypool
   */
  function sitePullDatabase(string $env = "live")
  {
    $project = getenv('PROJECT_NAME');
    $siteEnv = $project . '.' . $env;
    $backup_file_name = "{$project}.sql.gz";

    $this->say('Creating backup on Pantheon.');
    $this->taskExec('terminus')
      ->args('backup:create', $siteEnv, '--element=db')
      ->run();
    $this->say('Downloading backup file.');
    $this->taskExec('terminus')
      ->args('backup:get', $siteEnv, "--to=db/{$backup_file_name}", '--element=db')
      ->run();
    $this->say('Unzipping and importing data');
    $mysqlCommand = vsprintf(
      'pv \"./db/%s\" | gunzip | mysql -u root --password=%s --host 127.0.0.1 --port 33067 --protocol tcp %s ',
      [
        $backup_file_name,
        getenv('MYSQL_ROOT_PASSWORD'),
        getenv('MYSQL_DATABASE'),
      ]
    );
    $this->_exec( $mysqlCommand);
    $this->say('Data Import complete.');
  }

  /**
   * Gets files folder from pantheon
   *
   * @param $env
   * @author @megclaypool
   */
  function sitePullFiles(string $env = 'live')
  {
    $project = getenv('PROJECT_NAME');
    $siteEnv = $project . '.' . $env;
    $download = 'files_' . $siteEnv;
    $this->say('Creating files backup on Pantheon.');
    $this->taskExec('terminus')
      ->args('backup:create', $siteEnv, '--element=files')
      ->run();
    $this->say('Downloading files.');
    $this->taskExec('terminus')
      ->args('backup:get', $siteEnv, '--to=db/files.tar.gz', '--element=files')
      ->run();
    $this->say('Unzipping archive');
    $this->taskExec('tar')
      ->args('-xvf', './files.tar.gz', __DIR__ . "/db")
      ->run();
    $this->say('Copying Files');
    $this->_symlink(__DIR__ . '/db/files', __DIR__ . 'web/sites/default/files');
  }


  /**
   * @return \Robo\Result
   */
  public function redisFlush()
  {
    return $this->taskDockerExec(getenv('PROJECT_NAME') . "-redis")
      ->exec('redis-cli flushall')
      ->run();
  }

  /**
   * @return \Robo\Result
   */
  public function resetDependencies()
  {
    $response = $this->confirm("Are you sure you want to delete the vendor folder and all dependencies installed by composer?");
    if ($response == true) {
      $this->_exec("composer clear-cache");
	    $this->_exec("rm -Rf vendor web/modules/composer web/themes/composer web/modules/contrib web/themes/contrib web/core composer.lock");
      return exec("composer install");
    }
  }

}
