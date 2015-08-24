#Run PHP Copy-Paste Detector
vendor/bin/phpcpd php

#Run PHP Code Sniffer
phpcpd_cmd="vendor/bin/phpcs --standard=tests/config/standards.xml --extensions=php"
if [ ! -z $1 ]; then phpcpd_cmd+=" --severity=0"; fi
phpcpd_cmd+=" php"
eval $phpcpd_cmd
