terminus
========

A Drush-based CLI interface into the Pantheon core API via a pseudoproxy.

This is being developed initially to support some engineering and partner edge cases (DevOps shops, Kalabox, proviso), but we hope to make it a solid tool for all command-line and script-savvy developers.

See the "terminus" section in `drush help` for a list of commands.

Quick Demo
==========

    git clone https://github.com/pantheon-systems/terminus.git $HOME/.drush/terminus
    drush cc drush
    drush pantheon-auth
    drush pantheon-sites
    drush pantheon-aliases

You'll find many more fun commands in "drush help".

Building a Site on Pantheon using drush_make
============================================

Specify the site name...

    SITE_NAME=REPLACEME

And a description...

    SITE_DESC="Building a site with drush_make and terminus"

Create the site using Drupal 7 (drops-7) as the base.

    drush psite-create $SITE_NAME --label="$SITE_DESC" --product=21e1fada-199c-492b-97bd-0b36b53a9da0

Update your aliases.

    drush paliases

Determine the site_uuid of the newly created site.

    SITE_UUID=$(drush psite-uuid $SITE_NAME)

Change the connection mode of the dev environment to SFTP.

    drush psite-cmode $SITE_UUID dev sftp

Use a public gist as the source for drush make to download a few common modules.

    drush -y @pantheon.$SITE_NAME.dev make --no-core https://gist.github.com/fluxsauce/6590013/raw/7ccce9c0288377ba48d756596bb52c92b4acf736/gistfile1.txt

Commit the changes.

    drush psite-commit $SITE_UUID dev --message="Base tools from drush_make"

Change the connection mode back to git.

    drush psite-cmode $SITE_UUID dev git

Install the site. Remember to grab the password, or use drush uli later.

    drush -y @pantheon.$SITE_NAME.dev si --site-name="$SITE_DESC" pantheon

Disable unnecessary modules.

    drush -y @pantheon.$SITE_NAME.dev dis overlay comment rdf toolbar

Enable new modules.

    drush -y @pantheon.$SITE_NAME.dev en admin_menu module_filter features views views_ui ctools generate_errors admin_menu_toolbar devel_generate

Generate test content.

    drush @pantheon.$SITE_NAME.dev generate-content 50

Deploy code...

    # COMING SOON!

TODO
====

- Site import
- Team management
- In-progress job status
- Websockets support for real-time status info?
- A "pantheon shell" option to keep you in context for interactive use
