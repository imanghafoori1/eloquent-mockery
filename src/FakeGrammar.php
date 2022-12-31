<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class FakeGrammar extends Grammar
{
    public function compileExists(Builder $query)
    {
        return $this->stringy([
            'type' => 'exists',
            'builder' => $query,
            'sql' => parent::compileExists($query)
        ]);
    }

    public function compileDelete(Builder $query)
    {
        return $this->stringy([
            'type' => 'delete',
            'builder' => $query,
            'sql' => parent::compileDelete($query)
        ]);
    }

    public function compileUpdate(Builder $query, array $values)
    {
        return $this->stringy([
            'type' => 'update',
            'builder' => $query,
            'value' => $values,
            'table' => $query->from,
            'sql' => parent::compileUpdate($query, $values)
        ]);
    }

    public function compileInsert(Builder $query, array $values)
    {
        return $this->stringy([
            'type' => 'insert',
            'builder' => $query,
            'value' => $values,
            'sql' => parent::compileInsert($query, $values)
        ]);
    }

    public function compileInsertOrIgnore(Builder $query, array $values)
    {
        $data = $this->compileInsert($query, $values);
        $data->data['type'] = 'insertOrIgnore';

        return $data;
    }

    public function compileSelect(Builder $query)
    {
        $data = [
            'type' => 'select',
            'builder' => $query,
            'sql' => parent::compileSelect($query),
        ];

        return $this->stringy($data);
    }

    public function compileUpsert(Builder $query, array $values, array $uniqueBy, array $update)
    {
        return $this->stringy([
            'type' => 'upsert',
            'builder' => $query,
            'values' => $values,
            'uniqueBy' => $uniqueBy,
            'sql' => parent::compileUpsert($query, $values, $uniqueBy, $update)
        ]);
    }

    private function stringy($query)
    {
        return new class ($query) {

            public $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function __toString()
            {
                return $this->data['sql'];
            }
        };
    }
}
