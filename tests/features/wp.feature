Feature: wp

  Scenario: Activate WordPress Installation
    @vcr wp-core-install
    #When I run "terminus wp core install --path=[binding]/code/ --url=dev-[site].pantheon.io --admin_user=[user] --admin_password=[pass] --admin_email=[email] --title=[title] --site=[site] --env=dev"
    #Then I should get:
    #"""
    #"""

  Scenario: Install WordPress Plugin
    @vcr wp-plugin-install
    #When I run "terminus wp plugin install [plugin name] --site=[site] --env=dev"
    #Then I should get:
    #"""
    #"""
