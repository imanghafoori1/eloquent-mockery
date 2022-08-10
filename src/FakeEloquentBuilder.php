<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FakeEloquentBuilder extends Builder
{
    public function __construct($modelObj, $modelClass)
    {
        $this->query = new FakeQueryBuilder($modelObj);
        $this->model = $modelObj;
        $this->modelClass = $modelClass;
    }

    public function get($columns = ['*'])
    {
        $models = [];
        foreach ($this->applyScopes()->applyWheres() as $i => $row) {
            $model = new $this->modelClass;
            $model->exists = true;
            $row = $columns === ['*'] ? $row : Arr::only($row, $columns);
            $model->setRawAttributes($row);
            foreach (($this->modelClass)::$fakeRelations as $j => [$relName, $relModel, $relatedRow]) {
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
        $data = self::filterColumns($columns, $this->applyScopes()->applyWheres())->first();

        if (! $data) {
            return null;
        }

        $this->modelClass::unguard();

        $model = new $this->modelClass($data);
        $model->exists = true;

        ($this->modelClass)::$firstModel = $model;

        return $model;
    }

    public function select($columns = ['*'])
    {
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

            self::removeModel($this->modelClass, $this->model->getKey(), $this->model->getKeyName());
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

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->query->from = '';

        return $this;
    }

    public static function removeModel($modelClass, $modelId, $keyName = 'id')
    {
        foreach ($modelClass::$fakeRows as $i => $row) {
            if ($row[$keyName] === $modelId) {
                unset($modelClass::$fakeRows[$i]);
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

    public static function insertRow($originalModel, array $attributes)
    {
        $originalModel::$fakeRows[] = $attributes;
    }

    public function update(array $values)
    {
        $this->model->getAttributes() && $this->modelClass::$changedModels['updated'][] = $this->model;

        return self::update($values);
    }

    public function create(array $attributes = [])
    {
        $model = self::create($attributes);
        self::insertRow($this->modelClass, $model->getAttributes());
        $this->modelClass::$changedModels['created'][] = $model;

        return $model;
    }

    public function addUpdatedAtColumn(array $values)
    {
        $values = self::addUpdatedAtColumn($values);
        $updatedAt = $this->model->getUpdatedAtColumn();
        if (isset($values['.'.$updatedAt])) {
            $values[$updatedAt] = $values['.'.$updatedAt];
            unset($values['.'.$updatedAt]);
        }

        return $values;
    }
}
