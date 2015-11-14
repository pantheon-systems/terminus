#Run PHP Copy-Paste Detector
vendor/bin/phpcpd php

#Run PHP Code Sniffer on non-command files
vendor/bin/phpcs --standard=tests/config/standards.xml --extensions=php --warning-severity=6 --error-severity=1 --ignore=php/commands php/*
