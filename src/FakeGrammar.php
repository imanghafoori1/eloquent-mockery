<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class FakeGrammar extends Grammar
{
    public function compileDelete(Builder $query)
    {
        return [
            'builder' => $query,
            'sql' => parent::compileDelete($query)
        ];
    }

    public function compileUpdate(Builder $query, array $values)
    {
        return [
            'builder' => $query,
            'value' => $values,
            'sql' => parent::compileUpdate($query, $values)
        ];
    }

    public function compileInsert(Builder $query, array $values)
    {
        return [
            'builder' => $query,
            'value' => $values,
            'sql' => parent::compileInsert($query, $values)
        ];
    }
}
