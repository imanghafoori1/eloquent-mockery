<?php

namespace Imanghafoori\EloquentMockery;

class FakeDB
{
    public static $ignoreWheres = false;

    public static $fakeRows = [];

    public static $columnAliases = [];

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
                FakeDB::$fakeRows[$this->table][] = $row;
            }
        };
    }

    public static function truncate()
    {
        self::$fakeRows = [];
    }

}