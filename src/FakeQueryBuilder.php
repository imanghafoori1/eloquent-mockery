<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FakeQueryBuilder extends Builder
{
    public $recordedWheres = [];

    public $recordedWhereIn = [];

    public $recordedWhereNotIn = [];

    public $recordedWhereNull = [];

    public $recordedWhereNotNull = [];

    public $modelObj = null;

    public $recordedWhereLikes = [];

    public $orderBy = [];

    public $recordedWhereBetween = [];

    public $recordedWhereNotBetween = [];

    public function __construct($model)
    {
        $this->modelObj = $model;
    }

    public function offset($value)
    {
        $this->offset = $value;

        return $this;
    }

    public function limit($value)
    {
        $this->limit = $value;

        return $this;
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if ($not) {
            $this->recordedWhereNotIn[] = [$column, $values];
        } else {
            $this->recordedWhereIn[] = [$column, $values];
        }

        return $this;
    }

    public function whereNotIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->recordedWhereNotIn[] = [$column, $values];

        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->orderBy = [$column, $direction];

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
        if ($operator === 'like') {
            $this->recordedWhereLikes[] = [$column, $value];
        } else {
            $this->recordedWheres[] = [$column, $operator, $value];
        }

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

    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $this->recordedWhereBetween[] = [$column, $values];

        return $this;

    }

    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        $this->recordedWhereNotBetween[] = [$column, $values];

        return $this;

    }

    public function delete($id = null)
    {
        return $this->filterRows()->count();
    }

    public function update(array $values)
    {
        $collection = $this->filterRows()->map(function ($item) use ($values) {
            return $values + $item;
        });

        $collection->each(function ($val, $key) {
            $this->modelObj::$fakeRows[$key] = $val;
        });

        return $collection->count();
    }

    public function updateRow($originalModel, array $attributes)
    {
        $row = $this->filterRows();

        foreach ($row as $i) {
            $originalModel::$fakeRows[$i] = $originalModel::$fakeRows[$i] + $attributes;
        }
    }

    public function filterRows()
    {
        $collection = collect($this->modelObj::$fakeRows);

        if ($this->orderBy) {
            $sortBy = ($this->orderBy[1] === 'desc' ? 'sortByDesc' : 'sortBy');
            $column = $this->orderBy[0];
            $createdAt = $this->modelObj->getCreatedAtColumn();
            $updatedAt = $this->modelObj->getUpdatedAtColumn();

            if ($column === $createdAt || $column === $updatedAt || $column === 'deleted_at') {
                $collection = $collection->sort(function ($t, $item) use ($column) {
                    $direction = ($this->orderBy[1] === 'desc' ? 1 : -1);
                    return (strtotime($item[$column]) <=> strtotime($t[$column])) * $direction;
                });
            } else {
                $collection = $collection->$sortBy($column);
            }
        }

        if ($this->modelObj::$ignoreWheres) {
            return $collection;
        }

        foreach ($this->recordedWhereBetween as $_where) {
            $_where[0] = Str::after($_where[0], '.');
            $collection = $collection->whereBetween(...$_where);
        }

        foreach ($this->recordedWhereNotBetween as $_where) {
            $_where[0] = Str::after($_where[0], '.');
            $collection = $collection->whereNotBetween(...$_where);
        }

        foreach ($this->recordedWheres as $_where) {
            $_where = array_filter($_where, function ($val) {
                return ! is_null($val);
            });
            $_where[0] = Str::after($_where[0], '.');
            $collection = $collection->where(...$_where);
        }

        foreach ($this->recordedWhereLikes as $like) {
            $collection = $collection->filter(function ($item) use ($like) {
                $pattern = str_replace('%', '.*', preg_quote($like[1], '/'));

                return (bool) preg_match("/^{$pattern}$/i", $item[$like[0]] ?? '');
            });
        }

        foreach ($this->recordedWhereIn as $_where) {
            $collection = $collection->whereIn(Str::after($_where[0], '.'), $_where[1]);
        }

        foreach ($this->recordedWhereNotIn as $_where) {
            $collection = $collection->whereNotIn(Str::after($_where[0], '.'), $_where[1]);
        }

        foreach ($this->recordedWhereNull as $_where) {
            $collection = $collection->whereNull(Str::after($_where[0], '.'));
        }

        foreach ($this->recordedWhereNotNull as $_where) {
            $collection = $collection->whereNotNull(Str::after($_where[0], '.'));
        }

        $collection = $collection->map(function ($item) {
            return $this->_renameKeys(Arr::dot($item), $this->modelObj::$columnAliases);
        });

        $this->offset && $collection = $collection->skip($this->offset);

        $this->limit && $collection = $collection->take($this->limit);

        return $collection;
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
        end($this->modelClass::$fakeRows);
        $key = key($this->modelClass::$fakeRows);

        $id = $this->modelObj::$fakeRows[$key]['id'] ?? 0;

        reset($this->modelClass::$fakeRows);

        return $id + 1;
    }
}
