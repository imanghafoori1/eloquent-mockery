<?php

namespace Imanghafoori\EloquentMockery\Tests\Unit;

use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class FakeDbTest extends TestCase
{
    /**
     * @test
     */
    public function fakeDbAliasColumns()
    {
        // In this case, the users row does not change
        // but the comment key renames from "common" to "cc"
        $table = "users";
        $aliases =  [
            "common" => "users.common",
            "cc" => "comments.common",
        ];

        $userRow = [
            "id" => 3,
            "common" => "someValue",
        ];
        $item = [
            "users" => $userRow,
            "comments" => ["common" => "aValue"],
        ];

        $resultingItem = [
            "users" => $userRow,
            "comments" => ["cc" => "aValue"],
        ];

        $this->assertEquals($resultingItem, FakeDb::aliasColumns($aliases, $item, $table));
    }

    /**
     * @test
     */
    public function fakeDbAliasColumns2()
    {
        // In this case, the users renames from "common" to "uu"
        // and the comment key, renames from "common" to "cc"
        $table = "users";
        $aliases =  [
            "uu" => "users.common",
            "cc" => "comments.common",
        ];

        $item = [
            "users" => [
                "id" => 3,
                "common" => "someValue",
            ],
            "comments" => ["common" => "aValue"],
        ];

        $resultingItem = [
            "users" => [
                "id" => 3,
                "uu" => "someValue",
            ],
            "comments" => ["cc" => "aValue"],
        ];

        $this->assertEquals($resultingItem, FakeDb::aliasColumns($aliases, $item, $table));
    }
}
