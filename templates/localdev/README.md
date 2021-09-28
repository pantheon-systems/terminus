# Docker Local Hosting for Development


1. `brew install direnv`

2. composer require consolidation/robo

3. copy `docker-compose.yml`, `.envrc`, and `RoboFile.php` to root directory. Copy `settings.local.php` to `web/sites/default`

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
