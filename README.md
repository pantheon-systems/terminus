Pantheon CLI
============

The Pantheon Command Line Interface is a successor to the "Terminus" project, which provides access to the Pantheon Platform via Drush. While Terminus has provided great value (and we're going to keep the name in the product), we felt that it was important to have a first-class standalone CLI toolkit:

- That we could distribute as an independent executable.
- Which could "wrap" other commands that run on the platform.

If you would like to contribute, pull requests are welcome!

Installation
------------

Currently the quickest way to install the project is to grab the .phar file from github. We are currently in pre-release, and so instructions will change, but for now something like this should work:

```
curl https://github.com/pantheon-systems/cli/releases/download/0.0.2-alpha/terminus.phar -L -o /usr/local/bin/terminus
chmod +x /usr/local/bin/terminus
```

Stay tuned for updated installation instructions as the project matures.

Usage
-----

Currently the command-set exposed by the Pantheon CLI is limited:

- Authentication to the platform.
- List your sites.
- Perform remote actions on sites using the ```drush``` or ```wp-cli``` utilities.

**Example**

```
terminus auth login
Your email address?: josh@getpantheon.com
Your dashboard password (input will not be shown):
Logging in as josh@getpantheon.com
Success!
terminus sites show
+--------------------+-----------+---------------+--------------------------------------+
| Site               | Framework | Service Level | UUID                                 |
+--------------------+-----------+---------------+--------------------------------------+
| outlandish-josh    |           | personal      | 3ecd4d40-bdf2-4e52-a519-7e697ecdfe20 |
| multidev-sneakpeak |           | business      | 0706939a-fd1f-42fb-bd46-a5ce89ac5789 |
| sftp-mode-ftw      |           | free          | 100486f7-3488-eb11-10c4-9fbfab1996af |
| new-wp-spinup      | wordpress | free          | c33df3d3-deae-48ac-b4e1-97e36ea1ba34 |
+--------------------+-----------+---------------+--------------------------------------+

# Run a drush cc all
terminus drush cc all --site=outlandish-josh --environment=dev

# Install a freshly spun-up WordPress site.
# NOTE: Replace with your own site name, email, password!
terminus wp core install --site=new-wp-spinup --title=CliInstall --admin_user=admin --admin_password=XXXXXX --admin_email=josh@getpantheon.com --url=dev-new-wp-spinup.gotpantheon.com
```

Credits
-------
We have leaned heavily on the work of [wp-cli](http://wp-cli.org/) in architecting this command line utility with an object-oriented structure. We've also (obviously) been greatly inspired by[Drush](http://drush.ws/).
