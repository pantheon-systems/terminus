# terminus : Pantheon's Command-Line Interface

## Status

[![Commit Build](https://github.com/pantheon-systems/terminus/actions/workflows/ci.yml/badge.svg?branch=v3.0)](https://github.com/pantheon-systems/terminus/actions/workflows/ci.yml)
[![Actively Maintained](https://img.shields.io/badge/Pantheon-Actively_Maintained-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#actively-maintained-support)

## About

Terminus is Pantheon's Command Line Interface (CLI), providing at least equivalent functionality to the Pantheon's
browser-based Dashboard and easier scripting.

If you would like to contribute, pull requests are welcome!

## The Manual

Our documentation is kept in the Terminus Manual, located here: https://pantheon.io/docs/terminus

## Requirements

| Operating System       | Version    |
|------------------------|------------|
| MacOS                  | 10.14+     |
| Ubuntu                 | Latest LTS |
| Windows + WSL + Ubuntu | 20.0+      |

### Package Manager

- [Homebrew](https://brew.sh) for mac

#### Required Packages

These packages are required to take full advantage of Terminus.

- [Composer 2](https://getcomposer.org) (Needed for the plugin manager component)

- [PHP](https://www.php.net) (v8.2+)

- [Git](https://help.github.com/articles/set-up-git/) (May be needed for the plugin manager component)

- OpenSSH 7.8 or later

#### Recommended Packages

- [Drush](http://docs.drush.org/en/master/install/) (Useful to run incompatible-with-Terminus Drush commands)

- [WP-CLI](http://wp-cli.org/) (Useful to run incompatible-with-Terminus WP-CLI commands)


## Installation

### Mac OS:

Terminus is published as a package under pantheon-systems/external. To install it, you should run:

```
brew install pantheon-systems/external/terminus
```

### Standalone Terminus PHAR

The commands below will:

- Create a `terminus` folder in your home directory (`~/`)
- Get the latest release tag of Terminus
- Download and save the release as `~/terminus/terminus`
- Make the file executable
- Add a symlink to your local `bin` directory for the Terminus executable

```bash
mkdir -p ~/terminus && cd ~/terminus
curl -L https://github.com/pantheon-systems/terminus/releases/download/3.6.1/terminus.phar --output terminus
chmod +x terminus
./terminus self:update
sudo ln -s ~/terminus/terminus /usr/local/bin/terminus
```

### Standalone Docker container

Terminus is also published as a Docker image, so it can run without a local PHP environment. Each tagged release is available from the GitHub Container Registry. Bind-mount your local `~/.terminus` so configuration and plugins persist:

```bash
mkdir -p ~/.terminus
docker run --rm -tv ~/.terminus:/home/terminus/.terminus ghcr.io/pantheon-systems/terminus:latest self:info
```

For convenience, add an alias to your shell:

```bash
alias terminus="docker run --rm -tv ~/.terminus:/home/terminus/.terminus ghcr.io/pantheon-systems/terminus:latest"
```

See [docs/docker.md](docs/docker.md) for image tags, plugins, and how the images are built.
