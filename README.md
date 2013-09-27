# Terminus

Terminus is a Drush-based CLI interface into the Pantheon core API via a
pseudoproxy.

Terminus is being developed initially to support some engineering and partner
edge cases (DevOps shops, Kalabox, proviso), but we hope to make it a solid tool
for all command-line and script-savvy developers.

If you'd like to write your own code using terminus as an example, other PHP
libraries can be used to make direct API calls.

See `drush help --filter=terminus` for a list of commands.

Travis CI status: [<img src="https://travis-ci.org/pantheon-systems/terminus.png?branch=master">](https://travis-ci.org/pantheon-systems/terminus)

## Requirements

* Drush 5.1 or higher - https://github.com/drush-ops/drush
* PHP 5.3.3 or higher with cURL

## Installing Terminus with Composer and Packagist

[Composer](http://getcomposer.org) is a dependency manager for PHP, and
[Packagist](https://packagist.org/) is the main Composer repository. Terminus
can be found on Packagist as [pantheon-systems/terminus](https://packagist.org/packages/pantheon-systems/terminus)

The easiest way to install Composer for *nix (including Mac):

    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer

More detailed installation instructions for multiple platforms can be found in
the [Composer Documentation](http://getcomposer.org/doc/00-intro.md).

### Normal installation

    # Download Terminus for non-development use.
    composer create-project pantheon-systems/terminus $HOME/.drush/terminus -s dev --no-dev -n
    # Clear Drush's cache.
    drush cc drush

That's it! If you ever need to update Terminus, just use the following command:

    # Updating Terminus.
    composer update --no-dev --working-dir $HOME/.drush/terminus

### Development installation

If you want to contribute to Terminus development, you'll want to download the
dependencies for performing Unit Tests as well.

    # Make a projects directory if it doesn't already exist.
    mkdir -p $HOME/Projects
    # Download Terminus and development dependencies.
    composer create-project pantheon-systems/terminus $HOME/Projects/terminus -s dev --keep-vcs
    # Symbolically link Terminus directory to make it available to drush
    ln -s $HOME/Projects/terminus $HOME/.drush/terminus
    # Clear Drush's cache.
    drush cc drush

Terminus includes a number of Unit Tests for automated quality assurance. If you
add or modify functionality, please include test coverage as well.

    # Change directory.
    cd $HOME/Projects/terminus
    # Execute unit tests
    run_tests.sh

Updates can be performed in the directory:

    # Update Terminus and development dependencies.
    composer update

## Installing Terminus with Git

If you are unable to use Composer, Terminus can be installed using git. This
method is not recommended as dependencies will not be automatically installed if
they're missing.

    git clone https://github.com/pantheon-systems/terminus.git $HOME/.drush/terminus
    # Clear Drush's cache.
    drush cc drush

## Quick Demo

    # Authenticate.
    drush pantheon-auth
    # List all your sites.
    drush pantheon-sites
    # Download and replace pantheon.aliases.drushrc.php
    drush pantheon-aliases
    # Show all terminus commands.
    drush help --filter=terminus

## Build a site on Pantheon with drush_make

    # Specify the site name...
    SITE_NAME=REPLACEME
    # And a description...
    SITE_DESC="Building a site with drush_make and terminus"
    # Authenticate.
    drush pauth YOUR@EMAIL.COM --password=TOOMANYSECRETS
    # Create the site using Drupal 7 (drops-7) as the base.
    drush psite-create $SITE_NAME --label="$SITE_DESC" --product=21e1fada-199c-492b-97bd-0b36b53a9da0
    # Update your aliases.
    drush paliases
    # Determine the site_uuid of the newly created site.
    SITE_UUID=$(drush psite-uuid $SITE_NAME)
    # Change the connection mode of the dev environment to SFTP.
    drush psite-cmode $SITE_UUID dev sftp
    # Use a public URL as the source for drush_make to download a few modules.
    drush -y @pantheon.$SITE_NAME.dev make --no-core https://raw.github.com/pantheon-systems/terminus/master/demo.make
    # Install the site. Remember to grab the password, or use drush uli later.
    drush -y @pantheon.$SITE_NAME.dev si --site-name="$SITE_DESC" pantheon
    # Commit the changes.
    drush psite-commit $SITE_UUID dev --message="Base tools from drush_make"
    # Change the connection mode back to git.
    drush psite-cmode $SITE_UUID dev git
    # Disable unnecessary modules.
    drush -y @pantheon.$SITE_NAME.dev dis overlay comment rdf toolbar
    # Enable new modules.
    drush -y @pantheon.$SITE_NAME.dev en admin_menu module_filter features views views_ui ctools generate_errors admin_menu_toolbar devel_generate
    # Generate test content.
    drush @pantheon.$SITE_NAME.dev generate-content 50
    # Deploy code...
    # COMING SOON!

## TODO

- Site import
- Team management
- In-progress job status
- Websockets support for real-time status info?
- A "pantheon shell" option to keep you in context for interactive use

## Contributing

The Terminus source code is [hosted on GitHub](https://github.com/pantheon-systems/terminus).

Please use the [issue tracker](https://github.com/pantheon-systems/terminus/issues) if you find any bugs or wish to contribute.

### MIT license

Copyright (c) 2013 Pantheon Systems, Inc.

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
