<?php
namespace Darkness\Repository;

class Code
{
    public static function generate($code, $prefix = null, $length = 6, $str = '0')
    {
        return  ($prefix ? $prefix : '') . str_pad($code, $length, $str, STR_PAD_LEFT);
    }
}
