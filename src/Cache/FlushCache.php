<?php

namespace Darkness\Repository\Cache;

use Cache;

class FlushCache
{
    public static function all()
    {
        Cache::tags('QueryCache')->flush();
    }


    public static function request($request)
    {
        Cache::tags($request->tag)->flush();
    }
}
