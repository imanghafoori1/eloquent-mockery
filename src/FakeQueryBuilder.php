<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;

class FakeQueryBuilder extends Builder
{
    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this;
    }
}
