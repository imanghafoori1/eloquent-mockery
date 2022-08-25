<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FakeEloquentBuilder extends Builder
{
    public function __construct(Model $modelObj, $modelClass)
    {
        $this->query = new FakeQueryBuilder($modelObj->getDates());
        $this->model = $modelObj;
        $this->modelClass = $modelClass;
    }

    public function addSelect($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->query->columns = array_merge($this->query->columns ?? [], $columns);

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
        return $this->query->join($table, $first, $operator, $second);
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
        try {
            return $count = parent::delete();
        } finally {
            if ($count !== 0) {
                $this->modelClass::$changedModels['deleted'][] = $this->model;
            }
        }
    }

    public function forceDelete()
    {
        try {
            return $count = parent::forceDelete();
        } finally {
            if ($count !== 0) {
                $this->modelClass::$changedModels['deleted'][] = $this->model;
            }
        }
    }

    public function newModelInstance($attributes = [])
    {
        return $this->model->newInstance($attributes);
    }

    public function update(array $values)
    {
        $this->model->getAttributes() && $this->modelClass::$changedModels['updated'][] = $this->model;

        return parent::update($values);
    }
}
