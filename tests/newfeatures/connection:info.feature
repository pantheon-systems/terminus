Feature: Display site connection information
  In order to see whether the site connection type must be altered before changes
  As a user
  I need to be able to check the current connection mode.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_connection-info
  Scenario: Show default connection info for a site environment
    When I run "terminus connection:info [[test_site_name]].[[test_env_name]]"
    Then I should get:
    """
 +----------------+------------------------------------------------------------------------------------------------------------------------------------------------+
| Key            | Value                                                                                                                                           |
+----------------+-------------------------------------------------------------------------------------------------------------------------------------------------+
| sftp_command   | sftp -o Port=2222 [[test_env_name]].[[test_site_id]]@appserver.[[test_env_name]].[[test_site_host]].drush.in                                    |
| git_command    | git clone ssh://codeserver.[[test_env_name]].[[test_site_id]@codeserver.[[test_env_name]].[[test_site_host]].drush.in:2222/~/repository.git [[test_site_name]] |                                                                                                                     |
| mysql_command  | mysql -u pantheon -p[[test_db_password]] -h dbserver.dev.[[test_db_host]].drush.in -P [[test_db_port]] pantheon                                 
| redis_command  |  <command>
+----------------+-------------------------------------------------------------------------------------------------------------------------------------------------+
    """

  Scenario: Show default connection info for a site environment
    When I run "terminus connection:info [[test_site_name]].[[test_env_name]] -v"
    Then I should get:
    """
  +----------------+------------------------------------------------------------------------------------------------------------------------------------------+
| Parameter      | Value                                                                                                                                    |
+----------------+------------------------------------------------------------------------------------------------------------------------------------------+
| Sftp Username  | dev.932bdc35-0b38-4222-b87b-eccf498eedde                                                                                                 |
| Sftp Host      | appserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in                                                                              |
| Sftp Password  | Use your account password                                                                                                                |
| Sftp Url       | sftp://dev.932bdc35-0b38-4222-b87b-eccf498eedde@appserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:2222                         |
| Sftp Command   | sftp -o Port=2222 dev.932bdc35-0b38-4222-b87b-eccf498eedde@appserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in                   |
| Git Username   | codeserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde                                                                                      |
| Git Host       | codeserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in                                                                             |
| Git Port       | 2222                                                                                                                                     |
| Git Url        | ssh://codeserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde@codeserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:2222/~/repository |
|                | .git                                                                                                                                     |
| Git Command    | git clone ssh://codeserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde@codeserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:2222/~/ |
|                | repository.git ari                                                                                                                       |
| Mysql Host     | dbserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in                                                                               |
| Mysql Username | pantheon                                                                                                                                 |
| Mysql Password | e2b603c9adfa4e129dbb8f9cbfc1c1f9                                                                                                         |
| Mysql Port     | 10950                                                                                                                                    |
| Mysql Database | pantheon                                                                                                                                 |
| Mysql Url      | mysql://pantheon:e2b603c9adfa4e129dbb8f9cbfc1c1f9@dbserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:10950/pantheon              |
| Mysql Command  | mysql -u pantheon -pe2b603c9adfa4e129dbb8f9cbfc1c1f9 -h dbserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in -P 10950 pantheon     |
+----------------+------------------------------------------------------------------------------------------------------------------------------------------+    
    """

  @vcr site_connection-info
  Scenario: Show specific connection value
    When I run "terminus connection:info [[test_site_name]].[[test_env_name]] git_url"
    Then I should get:
    """
    ssh://
    """
    And I should not get:
    """
    sftp://
    """
