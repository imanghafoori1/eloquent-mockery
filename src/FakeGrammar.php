<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class FakeGrammar extends Grammar
{
    public function compileDelete(Builder $query)
    {
        return $query;
    }

    public function compileUpdate(Builder $query, array $values)
    {
        return [$query, $values];
    }

    public function compileInsert(Builder $query, array $values)
    {
        return [$query, $values];
    }
}