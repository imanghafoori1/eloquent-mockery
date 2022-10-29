<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FakeEloquentBuilder extends Builder
{
    public function __construct(FakeQueryBuilder $query, Model $modelObj)
    {
        $this->query = $query;
        $this->model = $modelObj;
    }

    public function delete()
    {
        try {
            return $count = parent::delete();
        } finally {
            if (is_int($count) && $count > 0) {
                FakeDB::setChangedModel('deleted', $this->model);
            }
        }
    }

    public function forceDelete()
    {
        try {
            return $count = parent::forceDelete();
        } finally {
            if ($count !== 0) {
                FakeDB::setChangedModel('deleted', $this->model);
            }
        }
    }

    public function update(array $values)
    {
        $this->model->getAttributes() && FakeDB::setChangedModel('updated', $this->model);

        return parent::update($values);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->query->where(...func_get_args());

        return $this;
    }
}
