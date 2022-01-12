#!/bin/bash

echo "Installing composer dependencies with --no-dev..."
composer install --no-dev

echo "Downloading phar-composer tool.."
curl -o phar-composer.phar -L https://clue.engineering/phar-composer-latest.phar

EXCLUDED_FILES=()
if [[ ! -z "${TERMINUS_ON_PHAR_BUILD_EXCLUDE_FILES}" ]]; then
  IFS=', ' read -r -a EXCLUDED_FILES <<< "$TERMINUS_ON_PHAR_BUILD_EXCLUDE_FILES"
fi

# Exclude files and directories by prefixing them with the dot character (phar-composer excludes them from the final phar archive).
for EXCLUDED_FILE in "${EXCLUDED_FILES[@]}"
do
    if [ -f "$EXCLUDED_FILE" ] || [ -d "$EXCLUDED_FILE" ]; then
         echo "Excluding '$EXCLUDED_FILE' from phar..."
         mv "$EXCLUDED_FILE" ".$EXCLUDED_FILE"
    fi
done

echo "Deleting terminus.phar file..."
rm -Rf ./terminus.phar

echo "Building terminus.phar..."
php -d phar.readonly=Off phar-composer.phar build .

# Restore excluded files and dirs.
for EXCLUDED_FILE in "${EXCLUDED_FILES[@]}"
do
    if [ -f ".$EXCLUDED_FILE" ] || [ -d ".$EXCLUDED_FILE" ]; then
         mv ".$EXCLUDED_FILE" "$EXCLUDED_FILE"
    fi
done

echo "Deleting phar-composer tool..."
rm phar-composer.phar

chmod +x terminus.phar

if [[ ! -z "${TERMINUS_ON_PHAR_COMPLETE_REINSTALL_COMPOSER_WITH_DEV}" ]]; then
  echo "Reinstalling composer dependencies with --dev..."
  composer install --dev
fi

echo "terminus.phar file has been created successfully!"
