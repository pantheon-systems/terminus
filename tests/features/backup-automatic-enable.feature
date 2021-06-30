Feature: Set a Backup Schedule for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to set my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-schedule-set.yml
  Scenario: Set the backup schedule for an environment
    When I run "[[executable]] backup:automatic:enable [[test_site_name]].test --day=Mon"
    Then I should get "Backup schedule successfully set."
    When I run "[[executable]] backup:automatic:info [[test_site_name]].test"
    Then I should get: "Monday"

  @vcr backup-schedule-set-ttl.yml
  Scenario: Set the backup schedule for an environment with a TTL
    When I run "[[executable]] backup:automatic:enable [[test_site_name]].test --day=Tue --keep-for=180"
    Then I should get "Backup schedule successfully set."
    When I run "[[executable]] backup:automatic:info [[test_site_name]].test --field=expiry"
    Then I should get: "180 days"
