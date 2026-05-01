<?php
/**
 * RedisManager singleton class
 * Mengelola koneksi Redis dan menyediakan abstraksi sederhana
 */
class RedisManager
{
    private static ?self $instance = null;
    private $redis = null;
    private bool $available = false;

    private function __construct()
    {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $host = Config::get('REDIS_HOST') ?: '127.0.0.1';
                $port = (int)(Config::get('REDIS_PORT') ?: 6379);
                $pass = Config::get('REDIS_PASSWORD');

                // Jika port 0, asumsikan host adalah path ke unix socket
                $connect_result = ($port === 0) 
                    ? $this->redis->connect($host) 
                    : $this->redis->connect($host, $port, 1.0);

                if ($connect_result) {
                    if ($pass && $pass !== 'null') {
                        $this->redis->auth($pass);
                    }
                    $this->available = true;
                }
            } catch (Exception $e) {
                error_log("Redis connection failed: " . $e->getMessage());
                $this->available = false;
            }
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getClient()
    {
        return $this->redis;
    }

    // Helper: Increment counter
    public function incr(string $key): void
    {
        if ($this->available) {
            $this->redis->incr($key);
        }
    }

    // Helper: Decrement counter
    public function decr(string $key): void
    {
        if ($this->available) {
            $this->redis->decr($key);
        }
    }

    // Helper: Hash Set
    public function hSet(string $key, string $field, $value): void
    {
        if ($this->available) {
            $this->redis->hSet($key, $field, $value);
        }
    }

    // Helper: Hash Get All
    public function hGetAll(string $key): array
    {
        if ($this->available) {
            return $this->redis->hGetAll($key) ?: [];
        }
        return [];
    }

    // Helper: Set with TTL
    public function set(string $key, $value, int $ttl = 3600): void
    {
        if ($this->available) {
            $this->redis->set($key, json_encode($value), $ttl);
        }
    }

    // Helper: Get
    public function get(string $key)
    {
        if ($this->available) {
            $val = $this->redis->get($key);
            return $val ? json_decode($val, true) : null;
        }
        return null;
    }

    // Helper: Delete
    public function del(string $key): void
    {
        if ($this->available) {
            $this->redis->del($key);
        }
    }

    // Helper: Flush all reports cache
    public function flushReports(): void
    {
        if ($this->available) {
            $keys = $this->redis->keys('report:*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        }
    }

    // Helper: Flush all search results cache
    public function flushSearchCache(): void
    {
        if ($this->available) {
            $keys = $this->redis->keys('search:*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        }
    }

    // Prevent cloning
    private function __clone() {}
    public function __wakeup() {}
}
