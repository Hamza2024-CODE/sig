<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache a value using a callback.
     *
     * @param  string  $key
     * @param  int|\DateTimeInterface  $ttl
     * @param  \Closure  $callback
     * @return mixed
     */
    public static function remember(string $key, $ttl, \Closure $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|\DateTimeInterface|null  $ttl
     * @return bool
     */
    public static function put(string $key, $value, $ttl = null)
    {
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public static function forget(string $key)
    {
        return Cache::forget($key);
    }

    /**
     * Clear the entire cache.
     *
     * @return bool
     */
    public static function flush()
    {
        return Cache::flush();
    }
}
