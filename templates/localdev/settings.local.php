<?php

if (!defined('PANTHEON_ENVIRONMENT')) {
  $env = getenv('ENV');
  $databases['default']['default'] = [
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASSWORD'),
    'host' => getenv('DB_HOST'),
    'port' => getenv('DB_PORT'),
    'driver' => getenv('DB_DRIVER'),
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'prefix' => '',
    'collation' => 'utf8mb4_general_ci',
  ];
  $settings['hash_salt'] = $_SERVER['DRUPAL_HASH_SALT'];
  $settings['cache']['bins']['config'] = 'cache.backend.chainedfast';
  $redis_host = getenv('CACHE_HOST');
  if (PHP_SAPI == 'cli') {
    ini_set('max_execution_time', 999);
  }
  else {
    $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';
    $settings['redis.connection']['interface'] = 'PhpRedis';
    $settings['redis.connection']['host'] = getenv('CACHE_HOST');
    $settings['redis.connection']['port'] = getenv('CACHE_PORT');
    $settings['cache']['bins']['bootstrap'] = 'cache.backend.redis';
    $settings['cache']['bins']['config'] = 'cache.backend.redis';
    $settings['cache']['bins']['render'] = 'cache.backend.redis';

    /**
     * $settings['cache']['bins']['bootstrap']           = 'cache.backend.chainedfast';
     * $settings['cache']['bins']['discovery']           = 'cache.backend.chainedfast';
     * $settings['cache']['bins']['config']              = 'cache.backend.chainedfast';
     * $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';
     * $settings['cache']['bins']['page']                = 'cache.backend.null';
     * $settings['cache']['bins']['dynamic_page_cache']  = 'cache.backend.null';
     **/
  }


  $config['system.logging']['error_level'] = getenv('DRUPAL_SYSTEM_LOGGING_ERROR_LEVEL');
  //$config['system.performance']['css']['preprocess'] = getenv('PREPROCESS_CSS');
  //$config['system.performance']['js']['preprocess'] = getenv('PREPROCESS_JS');

  $settings['extension_discovery_scan_tests'] = TRUE;
  $settings['rebuild_access'] = FALSE;
  $settings['skip_permissions_hardening'] = TRUE;

  $settings['file_public_path'] = 'sites/default/files';
  $settings['file_private_path'] = 'sites/default/private';
  $settings["file_temp_path"] = 'sites/default/temp';
  $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

}
