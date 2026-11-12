<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class InsightCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class InsightCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Opens or displays connection info for RedisInsight.
     *
     * @authorize
     * @interact
     *
     * @command redis:insight
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option bool $url-only Only display the Redis URL without attempting to open RedisInsight
     *
     * @usage <site>.<env> Opens RedisInsight with automatic connection setup for <site>'s <env> environment.
     * @usage <site>.<env> --url-only Displays only the Redis URL.
     *
     * @throws TerminusException
     */
    public function insight($site_env, $options = ['url-only' => false])
    {
        $env = $this->getEnv($site_env);

        $this->log()->notice(
            'Retrieving Redis connection information for {site}.{env}...',
            ['site' => $env->getSite()->getName(), 'env' => $env->getName()]
        );

        $connection_info = $env->connectionInfo();

        if (!isset($connection_info['redis_url'])) {
            throw new TerminusException(
                'Redis is not enabled for {site}.{env}. Please enable Redis in the Site Dashboard.',
                ['site' => $env->getSite()->getName(), 'env' => $env->getName()]
            );
        }

        $redis_url = $connection_info['redis_url'];

        // Parse the Redis URL to extract components
        $parsed = parse_url($redis_url);
        $host = $parsed['host'] ?? '';
        $port = $parsed['port'] ?? 6379;
        $username = $parsed['user'] ?? '';
        $password = $parsed['pass'] ?? '';

        if ($options['url-only']) {
            $this->output()->writeln($redis_url);
            return;
        }

        // Display connection information
        $this->log()->notice('Redis Connection Information:');
        $this->output()->writeln('');
        $this->output()->writeln('  <info>Redis URL:</info> ' . $redis_url);
        $this->output()->writeln('  <info>Host:</info> ' . $host);
        $this->output()->writeln('  <info>Port:</info> ' . $port);
        $this->output()->writeln('  <info>Password:</info> ' . $password);
        $this->output()->writeln('');

        // Add database connection to RedisInsight
        $db_name = sprintf('%s.%s', $env->getSite()->getName(), $env->getName());
        $redis_insight_dir = $_SERVER['HOME'] . '/.redis-insight';
        $db_file = $redis_insight_dir . '/redisinsight.db';

        // Check if RedisInsight database exists
        if (!file_exists($db_file)) {
            $this->output()->writeln('<comment>RedisInsight database not found.</comment>');
            $this->output()->writeln('<comment>Please launch RedisInsight at least once to initialize it.</comment>');
            $this->output()->writeln('');
            $this->output()->writeln('Connection details:');
            $this->output()->writeln('  Host: ' . $host);
            $this->output()->writeln('  Port: ' . $port);
            $this->output()->writeln('  Password: ' . $password);
            return;
        }

        // Find RedisInsight app first to check if it needs to be quit
        $redisinsight_app = null;

        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS - Check for both app name variations
            $app_names = ['Redis Insight', 'RedisInsight'];
            foreach ($app_names as $app_name) {
                if (file_exists("/Applications/{$app_name}.app")) {
                    $redisinsight_app = $app_name;
                    break;
                }
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // Windows
            $win_paths = [
                'C:\Program Files\RedisInsight\RedisInsight.exe',
                'C:\Program Files\Redis Insight\Redis Insight.exe',
            ];
            foreach ($win_paths as $path) {
                if (file_exists($path)) {
                    $redisinsight_app = $path;
                    break;
                }
            }
        } else {
            // Linux
            $linux_paths = ['/usr/local/bin/redisinsight', '/usr/bin/redisinsight'];
            foreach ($linux_paths as $path) {
                if (file_exists($path)) {
                    $redisinsight_app = $path;
                    break;
                }
            }
        }

        // Quit RedisInsight if it's running to avoid database conflicts
        $this->log()->notice('Closing RedisInsight to update database...');
        if (PHP_OS_FAMILY === 'Darwin') {
            exec("killall '{$redisinsight_app}' > /dev/null 2>&1");
        } elseif (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /IM RedisInsight.exe /F > nul 2>&1");
        } else {
            exec("pkill -f redisinsight > /dev/null 2>&1");
        }
        sleep(1);

        $this->log()->notice('Adding connection to RedisInsight database...');

        // Generate UUID for the connection
        $uuid = $this->generateUuid();

        // Escape values for SQLite (use '' to escape single quotes in SQL strings)
        $host_escaped = str_replace("'", "''", $host);
        $db_name_escaped = str_replace("'", "''", $db_name);
        $username_escaped = str_replace("'", "''", $username);
        $password_escaped = str_replace("'", "''", $password);
        $uuid_escaped = str_replace("'", "''", $uuid);

        // Delete any existing connections with same host:port to avoid duplicates
        $delete_sql = sprintf(
            "DELETE FROM database_instance WHERE host='%s' AND port=%d;",
            $host_escaped,
            $port
        );

        exec(sprintf(
            "sqlite3 %s \"%s\"",
            escapeshellarg($db_file),
            $delete_sql
        ));

        // Encrypt password using RedisInsight's KEYTAR encryption
        $encrypted_password = $this->encryptPassword($password);

        if (!$encrypted_password) {
            $this->output()->writeln('<comment>Failed to encrypt password. Unable to add connection automatically.</comment>');
            $this->output()->writeln('<comment>Please manually add the connection with the details shown above.</comment>');
            return;
        }

        $encrypted_password_escaped = str_replace("'", "''", $encrypted_password);

        // Insert new connection with encrypted password
        // Set lastConnection to current time to make it appear first in the list
        // Note: username is kept null as per RedisInsight best practice for standalone connections
        $insert_sql = sprintf(
            "INSERT INTO database_instance (id, host, port, name, username, password, db, connectionType, provider, modules, compressor, tls, ssh, encryption, timeout, new, forceStandalone, lastConnection) VALUES ('%s', '%s', %d, '%s', NULL, '%s', 0, 'STANDALONE', 'UNKNOWN', '[]', 'NONE', 0, 0, 'KEYTAR', 30000, 0, 0, datetime('now'));",
            $uuid_escaped,
            $host_escaped,
            $port,
            $db_name_escaped,
            $encrypted_password_escaped
        );

        // Execute the SQL command
        exec(sprintf(
            "sqlite3 %s \"%s\"",
            escapeshellarg($db_file),
            $insert_sql
        ), $output, $return_var);

        if ($return_var !== 0) {
            $this->output()->writeln('<comment>Failed to add connection to RedisInsight.</comment>');
            $this->output()->writeln('<comment>You can manually add the connection with the details shown above.</comment>');
            return;
        }

        $this->log()->notice('Connection saved to RedisInsight database.');

        if (!$redisinsight_app) {
            $this->output()->writeln('<comment>RedisInsight not found on your system.</comment>');
            $this->output()->writeln('<comment>To use RedisInsight, install it from: https://redis.io/insight/</comment>');
            $this->output()->writeln('');
            $this->output()->writeln('Once installed, run this command again to automatically configure the connection.');
            return;
        }

        $this->log()->notice('Opening RedisInsight...');

        // Launch RedisInsight
        if (PHP_OS_FAMILY === 'Darwin') {
            exec("open -a '{$redisinsight_app}' > /dev/null 2>&1 &");
        } elseif (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start \"\" \"$redisinsight_app\"", 'r'));
        } else {
            exec("{$redisinsight_app} > /dev/null 2>&1 &");
        }

        // Wait for RedisInsight to fully start
        sleep(2);

        $this->output()->writeln('');
        $this->output()->writeln('<info>Connection to ' . $db_name . ' added to RedisInsight!</info>');
        $this->output()->writeln('');
        $this->output()->writeln('Click on the connection in RedisInsight to connect and manage your Redis data.');
    }

    /**
     * Encrypts a password using RedisInsight's KEYTAR encryption.
     *
     * @param string $password The password to encrypt
     * @return string|false The encrypted password or false on failure
     */
    protected function encryptPassword($password)
    {
        // Create a temporary Node.js script to encrypt the password
        $script = <<<'JAVASCRIPT'
const crypto = require('crypto');
const keytar = require('%s');

(async () => {
  try {
    const password = process.argv[2];
    const encryptionKey = await keytar.getPassword('redisinsight', 'app');

    if (!encryptionKey) {
      process.exit(1);
    }

    // Hash the base64 encryption key with SHA-256
    const key = crypto.createHash('sha256').update(encryptionKey, 'utf8').digest();

    // Use zero-filled IV (RedisInsight standard)
    const iv = Buffer.alloc(16);

    // Encrypt with AES-256-CBC
    const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
    let encrypted = cipher.update(password, 'utf8', 'hex');
    encrypted += cipher.final('hex');

    console.log(encrypted);
  } catch (error) {
    process.exit(1);
  }
})();
JAVASCRIPT;

        // Find RedisInsight's keytar module
        $keytar_path = '/Applications/Redis Insight.app/Contents/Resources/app.asar.unpacked/node_modules/keytar';

        if (PHP_OS_FAMILY === 'Windows') {
            $keytar_path = 'C:\\Program Files\\RedisInsight\\resources\\app.asar.unpacked\\node_modules\\keytar';
            if (!file_exists($keytar_path)) {
                $keytar_path = 'C:\\Program Files\\Redis Insight\\resources\\app.asar.unpacked\\node_modules\\keytar';
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $keytar_path = '/opt/redisinsight/resources/app.asar.unpacked/node_modules/keytar';
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            // Check for both app name variations on macOS
            if (!file_exists($keytar_path)) {
                $keytar_path = '/Applications/RedisInsight.app/Contents/Resources/app.asar.unpacked/node_modules/keytar';
            }
        }

        if (!file_exists($keytar_path)) {
            return false;
        }

        $script = sprintf($script, $keytar_path);
        $temp_file = tempnam(sys_get_temp_dir(), 'redis_encrypt_') . '.js';
        file_put_contents($temp_file, $script);

        // Execute the script
        exec(sprintf(
            'node %s %s 2>&1',
            escapeshellarg($temp_file),
            escapeshellarg($password)
        ), $output, $return_var);

        unlink($temp_file);

        if ($return_var !== 0 || empty($output)) {
            return false;
        }

        return trim($output[0]);
    }

    /**
     * Generates a UUID v4.
     *
     * @return string UUID
     */
    protected function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
