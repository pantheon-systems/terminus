Feature: Terminus Command-Line Interaction
  In order to ease my use of the terminus command-line
  As a terminus user
  I want to be prompted and guided through command arguments

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"


  ### Site Name Prompts

  Scenario: Responding to a prompt for the site argument using a number
    When this step is implemented I will test: site selection interactivity by number
    When I run: terminus site:info
    Then I should get: "Please choose a site for this command:"
    Then I should get: "1) [[test_site_name]]"
    Then I should get: "Enter site name or number: "
    When I enter: 1
    Then I should see a table with rows like:
    """
      ID
      Name
      Label
      Created
      Framework
      Organization
      Service Level
      Upstream
      Holder Type
      Holder ID
      Owner
    """

  Scenario: Responding to a prompt for the site argument using a name
    When this step is implemented I will test: site selection interactivity by name
    When I run: terminus site:info
    Then I should get:
    """
    Please choose a site for this command:
    1) [[test_site_name]]
    Enter site name or number:
    """
    When I enter: [[test_site_name]]
    Then I should see a table with rows like:
    """
      ID
      Name
      Label
      Created
      Framework
      Organization
      Service Level
      Upstream
      Holder Type
      Holder ID
      Owner
    """


  ### Environment Name Prompts

  Scenario: Responding to a prompt for the environment argument using a number
    When this step is implemented I will test: environment selection interactivity by number
    When I run: terminus env:info [[test_site_name]]
    Then I should get:
    """
    Please choose an environment for this command:
    1) dev
    2) test
    3) live
    4) my-multidev
    Enter environment name or number:
    """
    When I enter: 1
    Then I should see a table with rows like:
    """
      ID
      Created
      Domain
      Locked
      Initialized
      Connection Mode
    """

  Scenario: Responding to a prompt for the environment argument using a name
    When this step is implemented I will test: environment selection interactivity by name
    When I run: terminus env:info [[test_site_name]]
    Then I should get:
    """
    Please choose an environment for this command:
    1) dev
    2) test
    3) live
    4) my-multidev
    Enter environment name or number:
    """
    When I enter: dev
    Then I should see a table with rows like:
    """
      ID
      Created
      Domain
      Locked
      Initialized
      Connection Mode
    """


  ### Organization Name Prompts

  Scenario: Responding to a prompt for the organization argument using a number
    When this step is implemented I will test: organization selection interactivity by number
    When I run: terminus org:site:list
    Then I should get:
    """
    Please choose an organization for this command:
    1) Organization Name
    Enter organization name or number:
    """
    When I enter: 1
    Then I should see a table with the headers: Name, ID, Service Level, Framework, Owner, Created, Tags

  Scenario: Responding to a prompt for the organization argument using a name
    When this step is implemented I will test: organization selection interactivity by name
    When I run: terminus org:site:list
    Then I should get:
    """
    Please choose an organization for this command:
    1) [[organization_name]]
    Enter organization name or number:
    """
    When I enter: [[organization_name]]
    Then I should see a table with the headers: Name, ID, Service Level, Framework, Owner, Created, Tags


  ### Role Name Prompts

  Scenario: Responding to a prompt for the role argument using a number
    When this step is implemented I will test: role selection interactivity by number
    When I run: terminus site:team:role [[test_site_name]] [[username]]
    Then I should get:
    """
    Please choose a role for this command:
    1) Team Member
    2) Developer
    Enter role name or number:
    """
    When I enter: 1
    Then I should get: "Changed a user role"

  Scenario: Responding to a prompt for the role argument using a name
    When this step is implemented I will test: role selection interactivity by name
    When I run: terminus site:team:role [[test_site_name]] [[username]]
    Then I should get:
    """
    Please choose a role for this command:
    1) Team Member
    2) Developer
    Enter role name or number:
    """
    When I enter: Developer
    Then I should get: "Changed a user role"


  ### Machine Token Prompts

  Scenario: Responding to a prompt for the machine-token argument using a number
    When this step is implemented I will test: machine-token selection interactivity by number
    When I run: terminus machine-token:delete
    Then I should get:
    """
    Please choose a machine-token for this command:
    1) [[machine_token_device]]
    Enter machine-token name or number:
    """
    When I enter: 1
    Then I should get: "Deleted [[machine_token_device]]!"

  Scenario: Responding to a prompt for the machine-token argument using a name
    When this step is implemented I will test: machine-token selection interactivity by name
    When I run: terminus machine-token:delete
    Then I should get:
    """
    Please choose a machine-token for this command:
    1) [[machine_token_device]]
    Enter machine-token name or number:
    """
    When I enter: token-name
    Then I should get: "Deleted [[machine_token_device]]!"
