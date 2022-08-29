<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FakeEloquentBuilder extends Builder
{
    public function __construct($query, Model $modelObj)
    {
        $this->query = $query;
        $this->model = $modelObj;
        $this->modelClass = get_class($modelObj);
    }

    public function addSelect($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->query->columns = array_merge($this->query->columns ?? [], $columns);

        return $this;
    }

    public function delete()
    {
        try {
            return $count = parent::delete();
        }
        finally {
            if ($count !== 0) {
                $this->modelClass::$changedModels['deleted'][] = $this->model;
            }
        }
    }

    public function forceDelete()
    {
        try {
            return $count = parent::forceDelete();
        }
        finally {
            if ($count !== 0) {
                $this->modelClass::$changedModels['deleted'][] = $this->model;
            }
        }
    }

    public function update(array $values)
    {
        $this->model->getAttributes() && $this->modelClass::$changedModels['updated'][] = $this->model;

        return parent::update($values);
    }
}
