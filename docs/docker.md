# Running Terminus with Docker

Terminus is published as a Docker image, so it can run without a local PHP
environment or other system dependencies. The image runs as a non-root
`terminus` user.

## Pulling an image

Each tagged release is published to the
[GitHub Container Registry](https://github.com/pantheon-systems/terminus/pkgs/container/terminus).
Pull the tag you want — a specific version, a major version, or `latest`:

```bash
docker pull ghcr.io/pantheon-systems/terminus:4.3.2   # a specific release
docker pull ghcr.io/pantheon-systems/terminus:4       # the latest 4.x release
docker pull ghcr.io/pantheon-systems/terminus:latest  # the latest release
```

## Persisting configuration and plugins

Terminus stores your machine token, configuration, and installed plugins in
`~/.terminus`. Bind-mount your local `~/.terminus` directory into the container
so that data is written to — and persists on — your local filesystem:

```bash
mkdir -p ~/.terminus
docker run --rm -tv ~/.terminus:/home/terminus/.terminus ghcr.io/pantheon-systems/terminus:latest self:info
```

For convenience, add an alias to your shell:

```bash
alias terminus="docker run --rm -tv ~/.terminus:/home/terminus/.terminus ghcr.io/pantheon-systems/terminus:latest"
terminus auth:login --machine-token=<token>
```

Because `~/.terminus` lives on your local filesystem, plugins you install remain
available on subsequent runs — including plugins installed from a local path:

```bash
terminus self:plugin:install pantheon-systems/terminus-plugin-example
terminus self:plugin:install /path/to/your/plugin
```

## Building the image locally

Instead of pulling a published image, you can build one from a local checkout of
this repository:

```bash
docker build . -t terminus
docker run --rm -tv ~/.terminus:/home/terminus/.terminus terminus self:info
```

## How the images are built

The [`Dockerfile`](../Dockerfile) uses a multi-stage build:

1. **Composer layer** — provides the `composer` binary.
2. **Build layer** — installs the production Composer dependencies and compiles
   `terminus.phar` with [Box](https://github.com/box-project/box) on a
   `php:8.4-cli-alpine` base.
3. **Runtime layer** — copies the compiled `terminus.phar` onto a fresh
   `php:8.4-cli-alpine` base with `git`, `openssh`, and `unzip`, creates the
   non-root `terminus` user (UID/GID 1000, so a bind-mounted `~/.terminus` from
   the typical first local user stays writable), and sets the phar as the
   entrypoint.

Each tagged release is published to the GitHub Container Registry by the
`publish_docker_image` job in
[`.github/workflows/4.x.yml`](../.github/workflows/4.x.yml). That job builds the
image from the tagged source and pushes it with version tags derived from the
release tag (for example `4.3.2`, `4.3`, `4`, and `latest`), OCI labels that
record the source commit, and a build-provenance attestation.
