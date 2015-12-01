#Run PHP Copy-Paste Detector
vendor/bin/phpcpd php

set -e
phpcs="vendor/bin/phpcs --standard=tests/config/standards.xml --extensions=php --warning-severity=6 --error-severity=1"
#Run PHP Code Sniffer on files with internal documentation
cmd=$phpcs+" --ignore=php/Terminus/Commands/*,vendor/*,tests/unit_tests/*,tests/features/bootstrap/FeatureContext.php *"
eval $cmd
cmd=$phpcs+" php/Terminus/Commands/TerminusCommand.php"
eval $cmd
cmd=$phpcs+" php/Terminus/Commands/CommandWithSSH.php"
eval $cmd

#Run PHP Code Sniffer on command files using a standards subset excepting internal documentation
phpcs="vendor/bin/phpcs --standard=tests/config/command_standards.xml --extensions=php --warning-severity=6 --error-severity=1"
cmd=$phpcs+" --ignore=php/Terminus/Commands/TerminusCommand.php,php/Terminus/Command/CommandWithSSH.php php/Terminus/Commands/* tests/unit_tests/*"
eval $cmd

#Run PHP Code Sniffer on command files using a standards subset excepting long lines
phpcs="vendor/bin/phpcs --standard=tests/config/context_standards.xml --extensions=php --warning-severity=6 --error-severity=1"
cmd=$phpcs+" tests/features/bootstrap/FeatureContext.php"
eval $cmd
