<?php

namespace Imanghafoori\EloquentMockery;

class FakePDO
{
    public function lastInsertId($sequence = '')
    {
        return FakeDB::lastInsertId();
    }
}
