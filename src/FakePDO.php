<?php

namespace Imanghafoori\EloquentMockery;

class FakePDO
{
    public function quote($binding)
    {
        return is_string($binding) ? $binding : '';
    }

    public function lastInsertId($sequence = '')
    {
        return FakeDB::lastInsertId();
    }
}
