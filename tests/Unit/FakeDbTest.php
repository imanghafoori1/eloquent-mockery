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

    /**
     * @test
     */
    public function fakeDbGetLatestRowWorksProperly()
    {
        // Arrange
        $rows = [
            ['id' => 1],
            ['id' => 2],
        ];

        FakeDB::$fakeRows = ['::table::' => $rows];

        // Act
        $result = FakeDB::getLatestRow('::table::');

        // Assert
        $this->assertEquals(['id' => 2], $result);
    }

    /**
     * @test
     */
    public function fakeDbGetLatestRowReturnsEmptyArrayIfThereIsNoRow()
    {
        // Arrange
        FakeDB::$fakeRows = ['::table::' => []];

        // Act
        $result = FakeDB::getLatestRow('::table::');

        // Assert
        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function fakeDbGetChangedModel()
    {
        // Arrange
        FakeDB::$changedModels = [
            '::model::' => [
                'create' => [
                    '::test'
                ]
            ]
        ];

        // Act
        $result = FakeDB::getChangedModel('create', 0, '::model::');

        // Assert
        $this->assertEquals('::test', $result);
    }

    /**
     * @test
     */
    public function fakeDbTruncate()
    {
        // Arrange
        FakeDB::$changedModels = [
            '::model::' => [
                'create' => [
                    '::test'
                ]
            ]
        ];

        FakeDB::$fakeRows = [
            '::table::' => [
                ['id' => 1],
            ]
        ];

        FakeDB::$tables = [
            '::table::' => [
                ['latestRowIndex' => 1],
            ]
        ];

        // Act
        FakeDB::truncate();

        // Assert
        $this->assertEmpty(FakeDB::$tables);
        $this->assertEmpty(FakeDB::$fakeRows);
        $this->assertEmpty(FakeDB::$changedModels);
    }

    /**
     * @test
     */
    public function fakeDbRemoveTableName()
    {
        // Arrange
        $data = [
            'users.id' => 1,
            'users.name' => '::name::',
        ];

        $expectedResult = [
            'id' => 1,
            'name' => '::name::'
        ];

        // Act
        $result = FakeDB::removeTableName($data);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }
}
