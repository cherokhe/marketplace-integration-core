<?php

declare(strict_types=1);

namespace MarketplaceIntegrationCore\Tests\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class InMemoryCache implements CacheRepository
{
    /** @var array<string, mixed> */
    private array $store = [];

    /** @var array<string, int> unix timestamp */
    private array $expiresAt = [];

    private bool $blockInvalidScopeKeys;

    public function __construct(bool $blockInvalidScopeKeys = true)
    {
        $this->blockInvalidScopeKeys = $blockInvalidScopeKeys;
    }

    private function isExpired(string $key): bool
    {
        if (!isset($this->expiresAt[$key])) {
            return false;
        }
        return time() >= $this->expiresAt[$key];
    }

    private function purgeIfExpired(string $key): void
    {
        if ($this->isExpired($key)) {
            unset($this->store[$key], $this->expiresAt[$key]);
        }
    }

    private function isBlocked(string $key): bool
    {
        if (!$this->blockInvalidScopeKeys) {
            return false;
        }
        return str_contains($key, 'mic:invalid:0:0:');
    }

    public function get($key, $default = null)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return $default;
        }

        $this->purgeIfExpired($key);
        return $this->store[$key] ?? $default;
    }

    public function put($key, $value, $ttl = null)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return;
        }

        $this->store[$key] = $value;

        if ($ttl === null) {
            unset($this->expiresAt[$key]);
            return;
        }

        if (is_int($ttl)) {
            $this->expiresAt[$key] = time() + max(0, $ttl);
            return;
        }

        unset($this->expiresAt[$key]);
    }

    public function forever($key, $value)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return;
        }

        $this->store[$key] = $value;
        unset($this->expiresAt[$key]);
    }

    public function add($key, $value, $ttl = null)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return false;
        }

        $this->purgeIfExpired($key);

        if (array_key_exists($key, $this->store)) {
            return false;
        }

        $this->store[$key] = $value;

        if ($ttl === null) {
            unset($this->expiresAt[$key]);
            return true;
        }

        if (is_int($ttl)) {
            $this->expiresAt[$key] = time() + max(0, $ttl);
            return true;
        }

        unset($this->expiresAt[$key]);
        return true;
    }

    public function increment($key, $value = 1)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return 0;
        }

        $this->purgeIfExpired($key);

        $current = $this->store[$key] ?? 0;
        if (!is_int($current)) {
            $current = 0;
        }

        $inc = is_int($value) ? $value : 1;
        $current += $inc;

        $this->store[$key] = $current;
        return $current;
    }

    public function decrement($key, $value = 1)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return 0;
        }

        $this->purgeIfExpired($key);

        $current = $this->store[$key] ?? 0;
        if (!is_int($current)) {
            $current = 0;
        }

        $dec = is_int($value) ? $value : 1;
        $current -= $dec;

        $this->store[$key] = $current;
        return $current;
    }

    public function forget($key)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return true;
        }

        unset($this->store[$key], $this->expiresAt[$key]);
        return true;
    }

    public function has($key)
    {
        $key = (string)$key;

        if ($this->isBlocked($key)) {
            return false;
        }

        $this->purgeIfExpired($key);
        return array_key_exists($key, $this->store);
    }

    public function pull($key, $default = null)
    {
        $val = $this->get($key, $default);
        $this->forget($key);
        return $val;
    }

    public function putMany(array $values, $ttl = null)
    {
        foreach ($values as $k => $v) {
            $this->put((string)$k, $v, $ttl);
        }
    }

    public function many(array $keys)
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $this->get((string)$k);
        }
        return $out;
    }

    public function sear($key, \Closure $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->forever($key, $value);
        return $value;
    }

    public function remember($key, $ttl, \Closure $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->put($key, $value, $ttl);
        return $value;
    }

    public function rememberForever($key, \Closure $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->forever($key, $value);
        return $value;
    }

    public function getDefaultCacheTime()
    {
        return 0;
    }

    public function setDefaultCacheTime($seconds)
    {
        return $this;
    }

    public function store($name = null)
    {
        return $this;
    }

    public function tags($names)
    {
        throw new \BadMethodCallException('tags() not supported in InMemoryCache');
    }

    public function flush()
    {
        $this->store = [];
        $this->expiresAt = [];
        return true;
    }
}
