<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;

class FakeQueryBuilder extends Builder
{
    public $shuffle = false;

    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this;
    }

    public function filterRows($sort = true, $columns = ['*'])
    {
        return FakeDB::filter(
            $this,
            $columns,
            $sort ? $this->orders : null
        );
    }

    public function aggregate($function, $columns = ['*'])
    {
        return $this->filterRows(false)->$function($columns);
    }

    public function exists()
    {
        return $this->count() > 0;
    }

    public function inRandomOrder($seed = '')
    {
        return $this->shuffle = [true, ($seed ?: null)];
    }

    public function count($columns = '*')
    {
        if ($columns !== '*') {
            foreach ((array) $columns as $column) {
                $this->whereNotNull($column);
            }
        }

        return $this->filterRows(false)->count();
    }

    public function addFakeRow(string $table, $val, $key)
    {
        FakeDB::changeFakeRow($table, $val, $key);
    }
}
