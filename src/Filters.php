<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Support\Str;

class Filters
{
    const WHERES = 'wheres';
    const WHERE_IN = 'where_in';
    const WHERE_NOT_IN = 'where_not_in';
    const WHERE_NULL = 'where_null';
    const WHERE_NOT_NULL = 'where_not_null';
    const WHERE_LIKES = 'where_likes';
    const WHERE_BETWEEN = 'where_between';
    const WHERE_NOT_BETWEEN = 'where_not_between';

    public static function filterConditions(string $key, $conditions, $_where)
    {
        $methodName = Str::camel(explode('.', $key)[0]);

        return self::$methodName($conditions, $_where);
    }

    public static function whereBetween($collection, $_where)
    {
        $_where[0] = Str::after($_where[0], '.');
        
        return $collection->whereBetween(...$_where);
    }
    
    public static function whereNotBetween($collection, $_where)
    {
        $_where[0] = Str::after($_where[0], '.');
        
        return $collection->whereNotBetween(...$_where);
    }
    
    public static function wheres($collection, $_where)
    {
        $_where = array_filter($_where, function ($val) {
            return !is_null($val);
        });
        $_where[0] = Str::after($_where[0], '.');

        return $collection->where(...$_where);
    }
    
    public static function whereLikes($collection, $_where)
    {
        return $collection->filter(function ($item) use ($_where) {
            $pattern = str_replace('%', '.*', preg_quote($_where[1], '/'));

            return (bool)preg_match("/^{$pattern}$/i", $item[$_where[0]] ?? '');
        });
    }
    
    public static function whereIn($collection, $_where)
    {
        return $collection->whereIn(Str::after($_where[0], '.'), $_where[1]);
    }
    
    public static function whereNotIn($collection, $_where)
    {
        return $collection->whereNotIn(Str::after($_where[0], '.'), $_where[1]);
    }
    
    public static function whereNull($collection, $_where)
    {
        return $collection->whereNull(Str::after($_where[0], '.'));
    }
    
    public static function whereNotNull($collection, $_where)
    {
        return $collection->whereNotNull(Str::after($_where[0], '.'));
    }
}
