<?php

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\PyStringNode;

/**
 * Features context for Behat feature testing
 */
class FeatureContext extends BehatContext {
  public $cliroot = '';
  private $_parameters;
  private $_output;
  private $_start_time;

  /**
  * Initializes context
  *
  * @param [array] $parameters Context parameters, set through behat.yml
  * @return [void]
  */
  public function __construct(array $parameters) {
    date_default_timezone_set('UTC');
    $this->cliroot          = dirname(dirname(__DIR__)) . '/..';
    $this->_parameters      = $parameters;
    $this->_start_time      = time();
    $this->_connection_info = array(
      'username' => $parameters['username'],
      'password' => $parameters['password'],
      'host'     => $parameters['host']
    );
  }

  /**
    * Ensures the user has access to the given payment instrument
    * @Given /^a payment instrument with uuid "([^"]*)"$/
    *
    * @param [string] $instrument_uuid UUID of a payment instrument
    * @return [void]
    */
  public function aPaymentInstrumentWithUuid($instrument_uuid) {
    $instruments = $this->iRun('terminus instruments list');
    try {
      $uuid = new PyStringNode(
        $this->_replacePlaceholders($instrument_uuid)
      );
      $this->iShouldGet($uuid);
    } catch(Exception $e) {
      throw new Exception(
        "Your user does not have access to instrument $instrument_uuid."
      );
    }
  }

  /**
   * Ensures a site of the given name exists
   * @Given /^a site named "([^"]*)"$/
   *
   * @param [string] $site Name of site to ensure exists
   * @return [boolean] Always true, else errs
   */
  public function aSiteNamed($site) {
    $output = $this->iGetInfoForTheSite($site);
    if(!$this->_checkResult('created', $output)) {
      $this->iCreateASiteNamed('Drupal 7', $site);
      $recurse = $this->aSiteNamed($site);
      return $recurse;
    }
    return true;
  }

  /**
   * Ensures a site of the given name exists and belongs to given org
   * @Given /^a site named "([^"]*)" belonging to "([^"]*)"$/
   *
   * @param [string] $site Name of site to ensure exists
   * @param [string] $site Name or UUID of site to ensure ownership
   * @return [boolean] Always true, else errs
   */
  public function aSiteNamedBelongingTo($site, $org) {
    $output = $this->iGetInfoForTheSite($site);
    if(!$this->_checkResult($site, $output)) {
      $this->iCreateASiteNamed('Drupal 7', $site, $org);
      $recurse = $this->aSiteNamedBelongingTo($site, $org);
      return $recurse;
    }
    return true;
  }

  /**
  * @BeforeScenario
  * Runs before each scenario
  *
  * @param [ScenarioEvent] $event Feature information from Behat
  * @return [void]
  */
  public function before($event) {
    $this->_setCassetteName($event);
  }

  /**
   * Changes or displays mode, given or not, of given site
   * @Given /^the connection mode of "([^"]*)" is "([^"]*)"$/
   * @When /^I set the connection mode on "([^"]*)" to "([^"]*)"$/
   * @When /^I check the connection mode on "([^"]*)"$/
   *
   * @param [string] $site Site to change or view connection mode of
   * @param [string] $mode If set, changes mode to given. Else, displays mode
   * @return [void]
   */
  public function connectionMode($site, $mode = false) {
    $command = "terminus site connection-mode --env=dev --site=$site";
    if($mode !== false) {
      $command .= " --set=$mode";
    }
    $this->iRun($command);
  }

  /**
    * Uses Drush to activate a Drupal site
    * @When /^I activate the Drupal site at "([^"]*)"$/
    *
    * @param [string] $site Name of the site to activate
    * @return [void]
    */
  public function iActivateTheDrupalSite($site) {
    $instruments = $this->iRun("terminus drush site-install -y --site=$site");
  }

  /**
   * Adds given hostname to given site's given environment
   * @When /^I add hostname "([^"]*)" to the "([^"]*)" environment of "([^"]*)"$/
   *
   * @param [string] $hostname Hostname to add
   * @param [string] $env      Environment on which to add hostname
   * @param [string] $site     Site on which to add hostname
   * @return [void]
   */
  public function iAddHostnameToTheEnvironmentOf($hostname, $env, $site) {
    $this->iRun(
      "terminus site hostnames add --site=$site --env=$env --hostname=$hostname"
    );
  }

  /**
   * Adds $email user from $site
   * @When /^I add "([^"]*)" to the team on "([^"]*)"$/
   *
   * @param [string] $email Email address of user to add
   * @param [string] $site  Name of the site on which to operate
   * @return [void]
   */
  public function iAddToTheTeamOn($email, $site) {
    $this->iRun("terminus site team add-member --site=$site --member=$email");
  }

  /**
   * @When /^I am prompted to "([^"]*)" on "([^"]*)" at "([^"]*)"$/
   *
   * @param [string] $prompt To be output before entering any key
   * @param [string] $site   Site about which prompt is regarding
   * @param [string] $url    URL to open after prompt
   * @return [void]
   */
  public function iAmPrompted(
      $prompt,
      $site,
      $url = "https://[[dashboard_host]]/"
  ) {
    echo $prompt . PHP_EOL;
    echo 'Then press any key.';
    $site      = $this->_replacePlaceholders($site);
    $site_info = $this->iGetInfoForTheSite($site, $return_hash = true);
    $url       = $this->_replacePlaceholders($url, $site_info);
    $this->_openInBrowser($url);
    $line = trim(fgets(STDIN));
  }

  /**
   * Logs in user with username and password set in behat.yml
   * And a blank slate cache
   * @Given /^I am authenticated$/
   *
   * @return [void]
   */
  public function iAmAuthenticated() {
    $this->iLogIn();
    $this->iRun("terminus sites list");
  }

  /**
   * Attaches a given organization as payee of given site
   * @When /^I attach the instrument "([^"]*)" to site "([^"]*)"$/
   *
   * @param [string] $uuid UUID of organization to attach as payee
   * @param [string] $site Name of site on which to attach
   * @return [void]
   */
  public function iAttachTheInstrument($uuid, $site) {
    $this->iRun(
      "terminus site set-instrument --site=$site --instrument=$uuid"
    );
  }

  /**
   * @Given /^I check the list of environments on "([^"]*)"$/
   *
   * @param [string] $site Site to check environments of
   * @return [string] $environments Environment list
   */
  public function iCheckTheListOfEnvironmentsOn($site) {
    $environments = $this->iRun("terminus site environments --site=$site");
    return $environments;
  }

  /**
   * Checks to see if a URL is valid
   * @Then /^I check the URL "([^"]*)" for validity$/
   *
   * @param [string] $url URL to check for validity
   */
  public function iCheckTheUrlForValidity($url) {
    $url = $this->_replacePlaceholders($url);
    if(filter_var($url, FILTER_VALIDATE_URL) === false) {
      throw new Exception("$url URL is not valid.");
    }
  }

  /**
   * Checks which user Terminus is operating as
   * @Given /^I check the user I am logged in as$/
   *
   * @return [void]
   */
  public function iCheckTheUserIAmLoggedInAs() {
    $this->iRun("terminus auth whoami");
  }

  /**
   * Clears site caches
   * @When /^I clear the caches on the "([^"]*)" environment of "([^"]*)"$/
   *
   * @param [string] $env  Environment on which to clear caches
   * @param [string] $site Site on which to clear caches
   * @return [void]
   */
  public function iClearTheCaches($env, $site) {
    $this->iRun("terminus site clear-cache --site=$site --env=$env");
  }

  /**
    * @When /^I clone the "([^"]*)" environment into the "([^"]*)" environment on "([^"]*)"$/
    *
    * @param [string] $from_env Environment to clone from
    * @param [string] $to_env   Environment to clone into
    * @param [string] $site     Site on which to clone an environment
    * @return [void]
    */
  public function iCloneTheEnvironment($from_env, $to_env, $site) {
    $this->iRun(
      "terminus site clone-content --site=$site
      --from-env=$from_env --to-env=$to_env --yes"
    );
  }

  /**
   * Clears the Terminus Sites Cache
   * @Then /^I clear the Terminus cache$/
   *
   * @return [void]
   */
  public function iRebuildTheTerminusCache() {
    $this->iRun("terminus sites cache --rebuild");
  }

  /**
   * Commits changes to given site's given env with given message
   * @When /^I commit changes to the "([^"]*)" environment of "([^"]*)" with message "([^"]*)"$/
   *
   * @param [string] $env     Name of environment on which to commit
   * @param [string] $site    Name of site on which to commit
   * @param [string] $message Message for commit
   * @return [void]
   */
  public function iCommitChanges($env, $site, $message) {
    $this->iRun(
      "terminus site code commit --site=$site --env=$env --message="
      . '"' . $message . '" --yes'
    );
  }

  /**
   * Creates a site for the given name
   * @When /^I create a "([^"]*)" site named "([^"]*)"$/
   *
   * @param [string] $upstream Which upstream to use as new site's source
   * @param [string] $name     Name of site to create
   * @return [void]
   */
  public function iCreateASiteNamed($upstream, $name, $org = false) {
    $append_org = '';
    if ($org !== false) {
      $append_org = '--org=' . $org;
    }
    $this->iRun(
      "terminus sites create --site=$name --label=$name --upstream=\"$upstream\" $append_org"
    );
  }

  /**
   * Creates a multidev env of given name on given site cloning given env
   * @When /^I create multidev environment "([^"]*)" from "([^"]*)" on "([^"]*)"$/
   *
   * @param [string] $multidev Name of new multidev environment
   * @param [string] $env      Name of environment to copy
   * @param [string] $site     Name of site on which to create multidev env
   * @return [void]
   */
  public function iCreateMultidevEnv($multidev, $env, $site) {
    $this->iRun(
      "terminus site create-env --site=$site --env=$multidev --from-env=$env"
    );
  }

  /**
   * Deletes a site of the given name
   * @When /^I delete the site named "([^"]*)"$/
   *
   * @param [string] $site Name of site to delete
   * @return [void]
   */
  public function iDeleteTheSiteNamed($site) {
    $this->iRun("terminus site delete --site=$site --yes");
  }


  /**
    * @Given /^I deploy the "([^"]*)" environment from "([^"]*)" of "([^"]*)" with the message "([^"]*)"$/
    *
    * @param [string] $env     Name of environment to deploy
    * @param [string] $from    Name of environment to deploy from
    * @param [string] $site    Name of site on which to deploy environment
    * @param [string] $message Commit message for the log
    * @return [void]
    */
  public function iDeployTheEnvironmentOf($env, $from, $site, $message) {
    $this->iRun(
      "terminus site deploy
      --site=$site --env=$env --from=$from --note=$note"
    );
  }

  /**
   * Queries for info for a given site
   * @Given /^I get info for the site "([^"]*)"$/
   *
   * @param [string]  $site        Site to get info on
   * @param [boolean] $return_hash Returns values usable array form
   * @return [string] Output from command run
   */
  public function iGetInfoForTheSite($site, $return_hash = false) {
    $return = $this->iRun("terminus site info --site=$site --format=bash");
    if(!$return_hash) {
      return $return;
    }

    $return_array = array();
    $return_lines = explode("\n", $return);
    foreach($return_lines as $line) {
      $line_components = explode(" ", $line);
      $index  = $line_components[0];
      $values = array_splice($line_components, 1);
      $return_array[$index] = $values;
    }
    return $return_array;
  }

  /**
    * @When /^I initialize the "([^"]*)" environment on "([^"]*)"$/
    *
    * @param [string] $env  Name of environment to initialize
    * @param [string] $site Name of site on which to initialize environment
    * @return [void]
    */
  public function iInitializeTheEnvironmentOn($env, $site) {
    $this->iRun("terminus site init-env --site=$site --env=$env");
  }

  /**
   * Installs given module to given Drupal site
   * @When /^I install the module "([^"]*)" to "([^"]*)"$/
   *
   * @param [string] $module Name of Drupal module to install
   * @param [string] $site   Name of the site to which to install
   * @return [void]
   */
  public function iInstallTheModuleTo($module, $site) {
    $this->iRun("terminus drush dl $module -y --site=$site --env=dev");
  }

  /**
   * Lists all hostnames of the given site's given environment
   * @Given /^I list the hostnames on the "([^"]*)" environment of "([^"]*)"$/
   *
   * @param [string] $env  Environment to list hostnames of
   * @param [string] $site Name of the site to list the hostnames of
   * @return [void]
   */
  public function iListTheHostnamesOn($env, $site) {
    $this->iRun("terminus site hostnames list --site=$site --env=$env");
  }

    /**
     * Checks the
     * @Given /^I check the payment instrument of "([^"]*)"$/
     *
     * @param [string] $site Name of site to check payment instrument of
     * @return [void]
     */
    public function iCheckThePaymentInstrumentOfSite($site) {
      $this->iRun("terminus site set-instrument --site=$site");
    }

  /**
   * Lists all sites user is on the team of
   * @When /^I list the sites$/
   *
   * @return [void]
   */
  public function iListTheSites() {
    $this->iRun("terminus sites list");
  }

  /**
   * Lists team members
   * @Given /^I list the team members on "([^"]*)"$/
   *
   * @param [string] $site Name of site of which to retrieve team members
   * @return [void]
   */
  public function iListTheTeamMembersOn($site) {
    $this->iRun("terminus site team list --site=$site");
  }

  /**
   * List the backups of the given environment of the given site
   * @When /^I list the backups of the "([^"]*)" environment of "([^"]*)"$/
   *
   * @param [string] $env  Environment of which to list the backups
   * @param [string] $site Site of which to list the backups
   * @return [string] Output to the CL
   */
  public function iListTheBackupsOf($env, $site) {
    $return = $this->iRun("terminus site backups list --site=$site --env=$env");
    return $return;
  }

  /**
   * Logs in user
   * @When /^I log in as "([^"]*)" with password "([^"]*)"$/
   *
   * @param [string] $username Pantheon username for login
   * @param [string] $password Password for username
   * @return [void]
   */
  public function iLogIn(
      $username = '[[username]]',
      $password = '[[password]]'
  ) {
    $this->iRun("terminus auth login $username --password=$password");
  }

  /**
   * Logs user out
   * @When /^I log out$/
   *
   * @return [void]
   */
  public function iLogOut() {
    $this->iRun("terminus auth logout");
  }

  /**
   * Makes a backup of given elements of given site's given environment
   * @When /^I back up "([^"]*)" elements of the "([^"]*)" environment of "([^"]*)"/
   * @When /^I back up the "([^"]*)" element of the "([^"]*)" environment of "([^"]*)"/
   *
   * @param [string] $elements Elements to back up
   * @param [string] $env      Environment to back up
   * @param [string] $site     Name of the site to back up
   * @return [void]
   */
  public function iMakeABackupElementsOfTheEnvironment($elements, $env, $site) {
    $this->iRun(
      "terminus site backups create --site=$site --env=$env --element=$elements"
    );
  }

  /**
    * @When /^I merge the "([^"]*)" environment into the "([^"]*)" environment on "([^"]*)"$/
    *
    * @param [string] $from_env Environment to merge from
    * @param [string] $to_env   Environment to merge into
    * @param [string] $site     Name of site on which to merge environments
    * @return [void]
    */
  public function iMergeTheEnvironment($from_env, $to_env, $site) {
    $this->setTestStatus('pending');
  }

  /**
   * Removes $email user from $site
   * @When /^I remove "([^"]*)" from the team on "([^"]*)"$/
   *
   * @param [string] $email Email address of user to add
   * @param [string] $site  Name of the site on which to operate
   * @return [void]
   */
  public function iRemoveFromTheTeamOn($email, $site) {
    $this->iRun(
      "terminus site team remove-member --site=$site --member=$email"
    );
  }

  /**
   * @Given /^I restore the "([^"]*)" environment of "([^"]*)" from backup$/
   *
   * @param [string] $env  Environment to restore from backup
   * @param [string] $site Site to restore from backup
   * @return [void]
   */
  public function iRestoreTheEnvironmentOfFromBackup($env, $site) {
    $this->setTestStatus('pending');
  }

  /**
  * @When /^I run "([^"]*)"$/
  * Runs command and saves output
  *
  * @param [string] $command To be entered as CL stdin
  * @return [string] Returns output of command run
  */
  public function iRun($command) {
    $command      = $this->_replacePlaceholders($command);
    $regex        = '/(?<!\.)terminus/';
    $terminus_cmd = sprintf('bin/terminus', $this->cliroot);
    if($this->_cassette_name) {
      $command = 'VCR_CASSETTE=' . $this->_cassette_name . ' ' . $command;
    }
    if(isset($this->_parameters['vcr_mode'])) {
      $command = 'VCR_MODE=' . $this->_parameters['vcr_mode']
        . ' ' . $command;
    }
    if(isset($this->_connection_info['host'])) {
      $command = 'TERMINUS_HOST=' . $this->_connection_info['host']
        . ' ' . $command;
    }
    $command = preg_replace($regex, $terminus_cmd, $command);
    ob_start();
    passthru($command . ' 2>&1');
    $this->_output = ob_get_clean();
    return $this->_output;
  }

  /**
   * @Then /^I should get:$/
   * @Then /^I should get "([^"]*)"$/
   * @Then /^I should get: "([^"]*)"$/
   * Checks the output for the given string
   *
   * @param [string] $string Content which ought not be in the output
   * @return [boolean] $i_have_this True if $string exists in output
   */
  public function iShouldGet($string) {
    $i_have_this = $this->iShouldGetOneOfTheFollowing($string);
    return $i_have_this;
  }

  /**
   * @Then /^I should get one of the following:$/
   * @Then /^I should get one of the following "([^"]*)"$/
   * @Then /^I should get one of the following: "([^"]*)"$/
   * Checks the output for the given substrings, comma-separated
   *
   * @param [array] $list_string Content which ought not be in the output
   * @return [boolean] True if a $string exists in output
    */
  public function iShouldGetOneOfTheFollowing($list_string) {
    $strings  = explode(',', $list_string);
    foreach ($strings as $string) {
      if($this->_checkResult(trim((string)$string), $this->_output)) {
        return true;
      }
    }
    throw new Exception("Actual output:\n" . $this->_output);
  }

  /**
   * Checks for backups made since the test started running
   * @Then /^I should have a new backup$/
   *
   * @return [boolean] True if new backup exists
   */
  public function iShouldHaveANewBackup() {
    $regex = "/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/";
    preg_match_all($regex, $this->_output, $matches);
    foreach($matches[0] as $date) {
      if($this->_start_time < strtotime($date)) {
        return true;
      }
    }
    throw new Exception('No new backups were created.' . PHP_EOL);
  }

  /**
   * Ensures that you do not recieve param $string as result
   * @Then /^I should not get:$/
   *
   * @param [PyStringNode] $string Content which ought not be in the output
   * @return [boolean] True if $string does not exist in output
   */
  public function iShouldNotGet(PyStringNode $string) {
    if($this->_checkResult((string)$string, $this->_output)) {
      throw new Exception("Actual output:\n" . $this->_output);
    }
    return true;
  }

  /**
   * Ensures there is no site with the given name. Loops until this is so
   * @Given /^no site named "([^"]*)"$/
   *
   * @param [string] $site Name of site to ensure does not exist
   * @return [boolean] Always returns true
   */
  public function noSiteNamed($site) {
    $output = $this->iGetInfoForTheSite($site);
    if($this->_checkResult('created', $output)) {
      $this->iDeleteTheSiteNamed($site);
      $status = $this->noSiteNamed($site);
      return $status;
    }
    return true;
  }

  /**
   * Gets or sets service level
   * @When /^I set the service level of "([^"]*)" to "([^"]*)"$/
   * @Given /^I check the service level of "([^"]*)"$/
   * @Given /^the service level of "([^"]*)" is "([^"]*)"$/
   *
   * @param [string] $site          Name of site to work on
   * @param [string] $service_level If not false, will set service level to this
   * @return [void]
   */
  public function serviceLevel($site, $service_level = false) {
    $command = "terminus site set-service-level --site=$site";
    if($service_level !== false) {
      $command .= " --set=$service_level";
    }
    $this->iRun($command);
  }

  /**
   * Automatically assigns pass/fail/skip to the test result
   * @Then /^I "([^"]*)" the test$/
   *
   * @param [string] $status Status to assign to the test
   * @return [boolean] Always true, else errs
   */
  public function setTestStatus($status) {
    if($status == 'pending') {
      throw new Exception("Implementation of this functionality is pending.");
    }
    throw new Exception("Test explicitly set to $status");
  }

  /**
   * Checks the the haystack for the needle
   *
   * @param [string] $needle   That which is searched for
   * @param [string] $haystack That which is searched inside
   * @return [boolean] $result True if $nededle was found in $haystack
   */
  private function _checkResult($needle, $haystack) {
    $needle = $this->_replacePlaceholders($needle);
    $result = preg_match("#" . preg_quote($needle . "#s"), $haystack);
    return $result;
  }

  /**
   * Returns tags in easy-to-use array format.
   *
   * @param [ScenarioEvent] $event Feature information from Behat
   * @return $tags [array] An array of strings corresponding to tags
   */
  private function _getTags($event) {
    $unformatted_tags = $event->getScenario()->getTags();
    $tags = array();

    foreach($unformatted_tags as $tag) {
      $tag_elements = explode(' ', $tag);
      $index        = null;
      if(count($tag_elements < 1)) {
        $index = array_shift($tag_elements);
      }
      if(count($tag_elements == 1)) {
        $tag_elements = array_shift($tag_elements);
      }
      $tags[$index] = $tag_elements;
    }

    return $tags;
  }

  /**
   * Opens param $url in the default browser
   *
   * @param [string] $url URL to open in browser
   * @return [void]
   */
  private function _openInBrowser($url) {
    $url = $this->_replacePlaceholders($url);
    switch(php_uname('s')) {
      case "Linux":
        $cmd = "xdg-open";
        break;
      case "Darwin":
        $cmd = "open";
        break;
      case "Windows NT":
        $cmd = "start";
        break;
    }
    exec("$cmd $url");
  }

  /**
  * Reads one line from STDIN
  *
  * @return [string] $line
  */
  private function _read() {
    $line = trim(fgets(STDIN));
    return $line;
  }

  /**
  * Exchanges values in given string with square brackets for values
  * in $this->_parameters
  *
  * @param [string] $string       The string to perform replacements on
  * @param [array]  $replacements Used to replace with non-parameters
  * @return [string] $string The modified param string
  */
  private function _replacePlaceholders($string, $replacements = array()) {
    $regex = '~\[\[(.*?)\]\]~';
    preg_match_all($regex, $string, $matches);
    if(empty($replacements)) {
      $replacements = $this->_parameters;
    }

    foreach($matches[1] as $id => $replacement_key) {
      if(isset($replacements[$replacement_key])) {
        $replacement = $replacements[$replacement_key];
        if(is_array($replacement)) {
          $replacement = array_shift($replacement);
        }
        $string = str_replace($matches[0][$id], $replacement, $string);
      }
    }

    return $string;
  }

  /**
  * Sets $this->_cassette_name and returns name of the cassette to be used.
  *
  * @param [array] $event Feature information from Behat
  * @return [string] Of scneario name, lowercase, with underscores and suffix
  */
  private function _setCassetteName($event) {
    $tags = $this->_getTags($event);
    $this->_cassette_name = false;
    if(isset($tags['vcr'])) {
      $this->_cassette_name = $tags['vcr'];
    }
    return $this->_cassette_name;
  }
}
