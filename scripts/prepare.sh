#!/bin/bash

# called by Travis CI

set -ex

composer self-update
composer install --no-interaction --prefer-source

curl http://behat.org/downloads/behat.phar > behat.phar
