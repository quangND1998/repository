<?php

namespace Darkness\Repository\Cache;

trait ModelCacheTrait
{
    public $cacheTime = 200;

    public function cacheTime()
    {
        return $this->cacheTime;
    }

    protected static function boot()
    {
        parent::boot();

        static::observe(
            \Darkness\Repository\Cache\FlushCacheObserver::class
        );
    }

    public static function getName()
    {
        preg_match('@\\\\([\w]+)$@', get_called_class(), $matches);
        return $matches[1];
    }

    /**
     * all cache keys
     *
     * @param [type] $type
     * @return void
     */
    public function listCacheKeys($type)
    {
        return array_unique(array_merge($this->defaultCacheKeys($type), $this->customCacheKeys($type)));
    }

    /**
     * default cache keys
     *
     * @param [type] $type
     * @return void
     */
    public function defaultCacheKeys($type)
    {
        switch ($type) {
            case 'detail':
                return [
                    CacheKey::generate(env('APP_NAME'), $this->getName() . '.getById', ['id' => $this->id]),
                    CacheKey::generate(env('APP_NAME'), $this->getName() . '.getByIdInTrash', ['id' => $this->id])
                ];
            case 'list':
                return [
                    'lists.' . $this->getName()
                ];
            default:
                return [];
        }
    }

    /**
     * custom cache keys
     *
     * @param [type] $type
     * @return void
     */
    public function customCacheKeys($type)
    {
        return [];
    }

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        $queryBuilder =
            new QueryBuilderWithCache(
                $connection,
                $connection->getQueryGrammar(),
                $connection->getPostProcessor()
            );
        return $queryBuilder->cacheFor($this->cacheTime())->withName($this->getName());
    }
}
