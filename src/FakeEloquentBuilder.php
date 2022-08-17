<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FakeEloquentBuilder extends Builder
{
    private $select = [];

    public function __construct(Model $modelObj, $modelClass)
    {
        $this->query = new FakeQueryBuilder($modelObj->getDates());
        $this->model = $modelObj;
        $this->modelClass = $modelClass;
    }

    public function get($columns = ['*'])
    {
        $models = [];
        foreach ($this->applyScopes()->applyWheres() as $i => $row) {
            $model = new $this->modelClass;
            $model->exists = true;
            if ($this->select) {
                $row = Arr::only($row, $this->select);
            }
            $row = $columns === ['*'] ? $row : Arr::only($row, $columns);
            $model->setRawAttributes($row);
            $models[] = $model;
        }

        $models = $this->eagerLoadRelations($models);

        return Collection::make($models);
    }

    public function select($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->select = $columns;

        return $this;
    }

    public function addSelect($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->select = array_merge($this->select, $columns);

        return $this;
    }

    public function count($columns = '*')
    {
        if ($columns !== '*') {
            foreach ((array) $columns as $column) {
                $this->query->whereNotNull($column);
            }
        }

        return $this->applyScopes()->applyWheres()->count();
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->query->orderBy($column, $direction);

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

    public function crossJoin()
    {
        return $this;
    }

    public function delete()
    {
        $count = parent::delete();
        if ($count !== 0) {
            $this->modelClass::$changedModels['deleted'][] = $this->model;

            self::removeModel($this->model->getKey(), $this->model->getTable(), $this->model->getKeyName());
        }
    }

    public function forceDelete()
    {
        return $this->delete();
    }

    private static function filterColumns($columns, $filtered)
    {
        if ($columns !== ['*']) {
            $filtered = $filtered->map(function ($item) use ($columns) {
                return Arr::only($item, $columns);
            });
        }

        return $filtered;
    }

    public static function removeModel($modelId, $table, $keyName = 'id')
    {
        foreach (FakeDB::$fakeRows[$table] as $i => $row) {
            if ($row[$keyName] === $modelId) {
                unset(FakeDB::$fakeRows[$table][$i]);
            }
        }
    }

    protected function applyWheres()
    {
        return $this->query->filterRows();
    }

    public function newModelInstance($attributes = [])
    {
        return $this->model->newInstance($attributes);
    }

    public static function insertRow(array $attributes, $table)
    {
        FakeDB::$fakeRows[$table][] = $attributes;
    }

    public function update(array $values)
    {
        $this->model->getAttributes() && $this->modelClass::$changedModels['updated'][] = $this->model;

        return parent::update($values);
    }

    public function create(array $attributes = [])
    {
        $model = parent::create($attributes);
        FakeEloquentBuilder::insertRow($model->getAttributes(), $model->getTable());

        return $model;
    }
}
