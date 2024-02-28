# API Transition

Terminus is undergoing an API Transition that will improve the performance and reliability of the Terminus API.

## What is the API Transition?

The hostname terminus uses to access the backing API will be transitioned from one infrastructure to the other.
In a perfect world we'd want this transition to be seamless for the customer, but we realize the complexity
of our customer's build systems sometimes makes this impossible.

To that end, we're providing a way for you to test your build systems against the new API before the transition happens.

## How do I test my build system against the new API?

You can test your build system against the new API by setting the `TERMINUS_HOST` environment variable to `api.pantheon.io`.

You can do that in several different ways:

## Methods for pointing terminus to PantheonAPI

*   **Recommended:** Add `TERMINUS_HOST: api.pantheon.io` to the file `~/.terminus/config.yml` on your computer. You can simply paste the code below into your terminal and it will create teh file if it does not exist and add the line to it:

    ```bash
    mkdir -p ~/.terminus && echo "TERMINUS_HOST: api.pantheon.io" >> ~/.terminus/config.yml
    ```

*   shell alias (add to shell config files):

    ```bash
    alias terminus="TERMINUS_HOST=api.pantheon.io terminus"
    ```

*   shell variable (add to shell config files):

    ```bash
    export TERMINUS_HOST=api.pantheon.io
    ```


To confirm that you are using the new API, run a terminus command with `-vvv` to see the API requests. The `URI` will normally use `terminus.pantheon.io`.  If your terminus is pointing to the new API, it will show `api.pantheon.io`.

Normal (old) API:

```bash
$ terminus -vvv auth:whoami
Headers: {"User-Agent":"Terminus/3.3.4 (php_version=8.3.3&script=bin/terminus)","Accept":"application/json","X-Pantheon-Trace-Id":"a2e3e3e3-3e3e-3e3e-3e3e-3e3e3e3e3e3e","X-Pantheon-Terminus-Command":"{\"command\":\"auth:whoami\",\"arguments\":{\"command\":\"auth:whoami\"},\"options\":{\"format\":\"string\",\"fields\":\"\",\"field\":\"\",\"help\":false,\"quiet\":false,\"verbose\":true,\"version\":false,\"ansi\":null,\"no-interaction\":false,\"define\":[],\"yes\":false},\"truncated\":false}","Authorization":"**HIDDEN**"}
URI: https://terminus.pantheon.io:443/api/users/a2e3e3e3-3e3e-3e3e-3e3e-3e3e3e3e3e3e
Method: GET
Body: null
```

New API:

```bash
$ terminus -vvv auth:whoami
Headers: {"User-Agent":"Terminus/3.3.4 (php_version=8.3.3&script=bin/terminus)","Accept":"application/json","X-Pantheon-Trace-Id":"a2e3e3e3-3e3e-3e3e-3e3e-3e3e3e3e3e3e","X-Pantheon-Terminus-Command":"{\"command\":\"auth:whoami\",\"arguments\":{\"command\":\"auth:whoami\"},\"options\":{\"format\":\"string\",\"fields\":\"\",\"field\":\"\",\"help\":false,\"quiet\":false,\"verbose\":true,\"version\":false,\"ansi\":null,\"no-interaction\":false,\"define\":[],\"yes\":false},\"truncated\":false}","Authorization":"**HIDDEN**"}
URI: https://terminus.pantheon.io:443/api/users/a2e3e3e3-3e3e-3e3e-3e3e-3e3e3e3e3e3e
Method: GET
Body: null
```

You can switch back to the old API by undoing these changes.  If you used the recommended method and you haven't customized your `~/.terminus/config.yml` file, you can simply delete the file.

## Here are some configurations for common CI Systems:

### CircleCI:

```yaml
version: 2.1

jobs:
  build:
    docker:
      - image: circleci/node:10

    environment:
      TERMINUS_HOST: "api.pantheon.io"

    steps:
      - name: set var in environment
        run: echo "export TERMINUS_HOST=api.pantheon.io" >> $BASH_ENV
      - checkout
      # Add your build steps here
```

### GitHub Actions:

```yaml
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest

    env:
      TERMINUS_HOST: "api.pantheon.io"

    steps:
      - uses: actions/checkout@v2
      # Add your build steps here
```

### Travis CI:

```yaml
language: node_js

env:
  global:
    - TERMINUS_HOST="api.pantheon.io"

script:
  # Add your build script here
```

### GitLab CI/CD:

```yaml
variables:
  TERMINUS_HOST: "api.pantheon.io"

stages:
  - build

build_job:
  stage: build
  script:
    # Add your build script here
```

### Jenkins:

```groovy
pipeline {
    agent any

    environment {
        TERMINUS_HOST = "api.pantheon.io"
    }

    stages {
        stage('Build') {
            steps {
                // Add your build steps here
            }
        }
    }
}
```

This environment variable will be available to your CI job during execution and whenever terminus executes commands, it will execute the command against the new API.
