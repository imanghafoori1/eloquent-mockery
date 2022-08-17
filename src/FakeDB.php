<?php

namespace Imanghafoori\EloquentMockery;

class FakeDB
{
    public static $rows = [];

    public static $fakeRows = [];

    public static function table($table)
    {
        return new class ($table) {
            private $table;

            public function __construct($table)
            {
                $this->table = $table;
            }

            public function addRow($row)
            {
                FakeDB::$rows[$this->table][] = $row;
            }
        };
    }


}