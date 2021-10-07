# Docker Local Hosting for Development


1. `brew install direnv pv realpath basename`

2. `env | grep shell` will tell you which shell you're using

3. Follow the instructions to enable DIRENV for your shell: https://direnv.net/docs/hook.html

4. composer require consolidation/robo

5. copy `docker-compose.yml`, `.envrc`, and `RoboFile.php` to root directory. Copy `settings.local.php` to `web/sites/default`

```
project root
|
|    => .envrc <=
|       composer.json
|       composer.lock
|    => docker-compose.yml <=
|       pantheon.yml
|    => RoboFile.php <=
|------ web
        |------ core
        |------ modules
        |------ sites
                |-----default
                |     |----- => settings.local.php <=
                |
                |-----themes

```

4. adjust the settings in `.envrc`

5. `direnv allow`

6. `robo docker:up`

7: Occasionally, docker scripts won't build a core in the docker container. Whichcase, go to http://localhost:8983/ and add one manually.
