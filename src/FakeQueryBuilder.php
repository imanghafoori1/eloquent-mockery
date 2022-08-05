<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class FakeQueryBuilder extends Builder
{
    public $recordedWheres = [];

    public $recordedWhereIn = [];

    public $recordedWhereNull = [];

    public $recordedWhereNotNull = [];

    public $model = [];

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->recordedWhereIn[] = [$column, $values];

        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        return $this;
    }

    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this;
    }

    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->recordedWheres[] = [$column, $operator, $value];

        return $this;
    }

    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $this->recordedWhereNull[] = [$columns];

        return $this;
    }

    public function whereNotNull($columns, $boolean = 'and')
    {
        $this->recordedWhereNotNull[] = [$columns];

        return $this;
    }

    public function delete($id = null)
    {
        $rows = $this->filterRows($this->model);

        return $rows->count();
    }

    public function update(array $values)
    {
        //$this->updateRow($this->model, $values);
        return $this->filterRows($this->model)->count();
    }

    public function updateRow($originalModel, array $attributes)
    {
        $row = $this->filterRows($this->model);

        foreach ($row as $i) {
            $originalModel::$fakeRows[$i] = $originalModel::$fakeRows[$i] + $attributes;
        }
    }

    public function filterRows($originalModel)
    {
        $collection = collect(($this->model)::$fakeRows);

        if (($this->model)::$ignoreWheres){
            return $collection;
        }

        foreach ($this->recordedWheres as $_where) {
            $_where = array_filter($_where, function ($val) {
                return ! is_null($val);
            });

            $collection = $collection->where(...$_where);
        }

        foreach ($this->recordedWhereIn as $_where) {
            $collection = $collection->whereIn($_where[0], $_where[1]);
        }

        foreach ($this->recordedWhereNull as $_where) {
            $collection = $collection->whereNull($_where[0]);
        }

        foreach ($this->recordedWhereNotNull as $_where) {
            $collection = $collection->whereNotNull($_where[0]);
        }

        return $collection
            ->map(function ($item) use ($originalModel) {
                return $this->_renameKeys(
                    Arr::dot($item),
                    ($originalModel)::$columnAliases
                );
            });
    }

    private function _renameKeys(array $array, array $replace)
    {
        $newArray = [];
        if (! $replace) {
            return $array;
        }

        foreach ($array as $key => $value) {
            $key = array_key_exists($key, $replace) ? $replace[$key] : $key;
            $key = explode('.', $key);
            $key = array_pop($key);
            $newArray[$key] = $value;
        }

        return $newArray;
    }

    public function insertGetId(array $values, $sequence = null)
    {
        $key = array_key_last(($this->model)::$fakeRows);

        return ($this->model)::$fakeRows[$key]['id'] + 1 ?? 1;
    }
}
