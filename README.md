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

TODO
====

- Site import
- Team management
- In-progress job status
- Websockets support for real-time status info?
- A "pantheon shell" option to keep you in context for interactive use
