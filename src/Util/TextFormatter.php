<?php

namespace Gh0stytopflo\Taskmgr\Util;

class TextFormatter
{
    public static function assocImplode(array $arr): string
    {
        $str = '';

        foreach ($arr as $k => $v) {
            $str .= $k . ' = ' . $v . " ";
        }

        return $str;
    }
}