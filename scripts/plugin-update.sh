#!/bin/bash
GIT=$(which git)
if [ $? == 1 ]; then
  echo "[Error] Git is not installed.  See https://git-scm.com/downloads."
  exit 1
fi
TERMINUS_PLUGINS_DIR=$(echo $TERMINUS_PLUGINS_DIR)
if [ -z $TERMINUS_PLUGINS_DIR ]; then
  TERMINUS_PLUGINS_DIR=$HOME/terminus/plugins
fi
cd $TERMINUS_PLUGINS_DIR
EXISTING_PLUGINS=$(ls | xargs 2> /dev/null)
for PLUGIN in $EXISTING_PLUGINS; do
  if [ -d $PLUGIN ]; then
    echo "Updating $PLUGIN..."
    cd $PLUGIN
    if [ -d .git ]; then
      git pull
    else
      echo "[Error] Unable to update plugin.  Git repository does not exist."
      echo "The recommended way to install plugins is to git clone <plugin_repository>."
      echo "See https://github.com/pantheon-systems/terminus/wiki/Plugins."
    fi
    cd ..
  fi
done
