#!/usr/bin/env sh

behat_cmd="vendor/bin/behat -c=tests/config/behat.yml -p=qa $*"
eval $behat_cmd
