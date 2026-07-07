<?php

namespace App\Cache;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
    /**
     * Retrieve an item from the cache.
     *
     * @param  string  $key
     * @param  int|null  $ttl  Ignored since Laravel manages TTL on set/put
     * @return mixed
     */
    public function get(string $key, $ttl = null)
    {
        return Cache::get($key);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $ttl  TTL in seconds (default 600)
     * @return bool
     */
    public function set(string $key, $value, $ttl = 600)
    {
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget(string $key)
    {
        return Cache::forget($key);
    }
}
