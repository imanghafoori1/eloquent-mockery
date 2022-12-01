<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;

class FakeQueryBuilder extends Builder
{
    public $shuffle = false;

    private $dates = [];

    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this;
    }

    public function filterRows($sort = true, $columns = ['*'])
    {
        return FakeDB::filter(
            $this,
            $this->from,
            $this->joins ?? [],
            $columns,
            $this->columns,
            $this->offset,
            $this->limit,
            $sort ? $this->orders : null,
            $this->shuffle,
            $this->dates
        );
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        $collection = $this->filterRows()->map(function ($item) use ($amount, $column, $extra) {
            $item[$column] = $item[$column] + $amount;

            return $extra + $item;
        });

        return FakeDB::syncTable($collection, $this->from);
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->increment($column, $amount * -1, $extra);
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
