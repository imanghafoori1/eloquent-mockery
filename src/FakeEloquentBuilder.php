<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FakeEloquentBuilder extends Builder
{
    public function __construct($model, $originalModel)
    {
        $this->query = new FakeBuilder($model);
        $this->model = $model;
        $this->originalModel = $originalModel;
    }

    public function get($columns = ['*'])
    {
        $models = [];
        foreach ($this->filterRows() as $i => $row) {
            $model = new $this->originalModel;
            $model->exists = true;
            $row = $columns === ['*'] ? $row : Arr::only($row, $columns);
            $model->setRawAttributes($row);
            foreach (($this->originalModel)::$fakeRelations as $j => [$relName, $relModel, $relatedRow]) {
                $relModel = new $relModel;
                $relModel->setRawAttributes($relatedRow[$i]);
                $model->setRelation($relName, $relModel);
            }
            $models[] = $model;
        }

        return Collection::make($models);
    }

    public function first($columns = ['*'])
    {
        $filtered = $this->filterRows();
        $data = $this->filterColumns($columns, $filtered)->first();

        if (! $data) {
            return null;
        }

        $this->originalModel::unguard();

        $model = new $this->originalModel($data);
        $model->exists = true;

        ($this->originalModel)::$firstModel = $model;

        return $model;
    }

    public function select($columns = ['*'])
    {
        return $this;
    }

    public function count($columns = '*')
    {
        return $this->filterRows()->count();
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

    public function innerJoin()
    {
        return $this;
    }

    public function create($data = [])
    {
        $model = clone $this->model;
        $model->exists = true;
        ($this->originalModel)::$saveCalls[] = $data;

        return $model;
    }

    private function filterRows()
    {
        $collection = collect(($this->originalModel)::$fakeRows);

        if ($this->originalModel::$ignoreWheres){
            return $collection;
        }

        foreach ($this->query->recordedWheres as $_where) {
            $_where = array_filter($_where, function ($val) {
                return ! is_null($val);
            });

            $collection = $collection->where(...$_where);
        }

        foreach ($this->query->recordedWhereIn as $_where) {
            $collection = $collection->whereIn($_where[0], $_where[1]);
        }

        foreach ($this->query->recordedWhereNull as $_where) {
            $collection = $collection->whereNull($_where[0]);
        }

        foreach ($this->query->recordedWhereNotNull as $_where) {
            $collection = $collection->whereNotNull($_where[0]);
        }

        return $collection
            ->map(function ($item) {
                return $this->_renameKeys_sa47rbt(
                    Arr::dot($item),
                    ($this->originalModel)::$columnAliases
                );
            });
    }

    private function _renameKeys_sa47rbt(array $array, array $replace)
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

    private function filterColumns($columns, $filtered)
    {
        if ($columns !== ['*']) {
            $filtered = $filtered->map(function ($item) use ($columns) {
                return Arr::only($item, $columns);
            });
        }

        return $filtered;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }
}
