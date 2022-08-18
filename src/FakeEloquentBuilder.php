<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FakeEloquentBuilder extends Builder
{
    private $select = [];

    public function __construct(Model $modelObj, $modelClass)
    {
        $this->query = new FakeQueryBuilder($modelObj->getDates());
        $this->model = $modelObj;
        $this->modelClass = $modelClass;
    }

    public function select($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->query->columns = $columns;

        return $this;
    }

    public function addSelect($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->query->columns = array_merge($this->query->columns, $columns);

        return $this;
    }

    public function count($columns = '*')
    {
        return $this->applyScopes()->query->count($columns);
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

    public static function removeModel($modelId, $table, $keyName = 'id')
    {
        foreach (FakeDB::$fakeRows[$table] as $i => $row) {
            if ($row[$keyName] === $modelId) {
                unset(FakeDB::$fakeRows[$table][$i]);
            }
        }
    }

    protected function applyWheres($sort = true)
    {
        return $this->query->filterRows($sort, $this->select);
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
