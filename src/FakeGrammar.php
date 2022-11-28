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

    public function compileInsertOrIgnore(Builder $query, array $values)
    {
        $data = $this->compileInsert($query, $values);
        $data['type'] = 'insertOrIgnore';

        return $data;
    }

    public function compileSelect(Builder $query)
    {
        return [
            'builder' => $query,
            'sql' => parent::compileSelect($query)
        ];
    }

    public function compileUpsert(Builder $query, array $values, array $uniqueBy, array $update)
    {
        return [
            'builder' => $query,
            'values' => $values,
            'uniqueBy' => $uniqueBy,
            'sql' => parent::compileUpsert($query, $values, $uniqueBy, $update)
        ];
    }
}
