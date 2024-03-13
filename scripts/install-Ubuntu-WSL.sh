#!/bin/bash

#update the system
sudo apt update &&
sudo apt upgrade -y &&

#install Terminus dependencies
sudo apt install -y curl php8.1 php-xml git composer &&

#download wp-cli
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar &&

#check if wp-cli is working properly
php wp-cli.phar --info &&

#install wp-cli
chmod +x wp-cli.phar &&
sudo mv wp-cli.phar /usr/local/bin/wp &&

#install Terminus
mkdir -p ~/terminus &&
cd ~/terminus &&
curl -L https://github.com/pantheon-systems/terminus/releases/download/3.3.0/terminus.phar --output terminus &&
chmod +x terminus &&
sudo ln -s ~/terminus/terminus /usr/local/bin/terminus &&

echo "Terminus successfully installed! Now you can generate your machine token to log in: https://docs.pantheon.io/terminus/install#machine-token"
