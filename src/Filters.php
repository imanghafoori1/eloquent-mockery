<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Support\Str;

class Filters
{
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
