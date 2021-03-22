<?php

namespace Darkness\Repository\Cache;

trait QueryCacheTrait
{
    public $avoidCache = true;
    public $defaultCacheTags = ['QueryCache'];

    public function cacheFor(int $seconds)
    {
        $this->cacheTime = $seconds;
        $this->avoidCache = false;
        return $this;
    }

    public function noCache()
    {
        $this->avoidCache = true;
        return $this;
    }

    public function getCacheTime()
    {
        return property_exists($this, 'cacheTime') ? $this->cacheTime : 0;
    }

    public function getInCache()
    {
        return $this->getCacheTime() && !$this->avoidCache;
    }

    public function getCacheKey($service, $function, array $bindings)
    {
        return CacheKey::generate($service, $function, $bindings);
    }

    public function callWithCache(callable $callback, array $params, $cacheKey, array $tags = [])
    {
        // TODO: no cache
        // redis server die
        // remove this to cache
        $this->noCache();

        $tags = array_unique(array_merge($tags, $this->defaultCacheTags, [$cacheKey, app()->make('request')->tag]));
        if ($this->getInCache()) {
            $this->avoidCache = true;
            return \Cache::tags($tags)->remember($cacheKey, $this->getCacheTime(), function () use ($callback, $params) {
                return call_user_func_array($callback, $params);
            });
        }
        return call_user_func_array($callback, $params);
    }
}
