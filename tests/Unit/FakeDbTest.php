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
        $table = 'users';
        $aliases = [
            'common' => 'users.common',
            'cc' => 'comments.common',
        ];

        $userRow = [
            'id' => 3,
            'common' => 'someValue',
        ];
        $item = [
            'users' => $userRow,
            'comments' => ['common' => 'aValue'],
        ];

        $resultingItem = [
            'users' => $userRow,
            'comments' => ['cc' => 'aValue'],
        ];

        $this->assertEquals($resultingItem, FakeDB::aliasColumns($aliases, $item, $table));
    }

    /**
     * @test
     */
    public function fakeDbAliasColumns2()
    {
        // In this case, the users renames from "common" to "uu"
        // and the comment key, renames from "common" to "cc"
        $table = "users";
        $aliases = [
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

        $this->assertEquals($resultingItem, FakeDB::aliasColumns($aliases, $item, $table));
    }

    /**
     * @test
     */
    public function fakeDbGetLatestRowWorksProperly()
    {
        FakeDB::addRow('::table1::', ['id' => 2]);
        FakeDB::addRow('::table1::', ['id' => 1]);
        FakeDB::addRow('::table2::', ['id' => 3]);
        FakeDB::addRow('::table2::', ['id' => 5]);

        // Act
        $result1 = FakeDB::getLatestRow('::table1::');
        $result2 = FakeDB::getLatestRow('::table2::');
        $result3 = FakeDB::getLatestRow('::table3::'); // absent table

        // Assert
        $this->assertEquals([
            'id' => 1,
        ], $result1);

        $this->assertEquals([
            'id' => 5,
        ], $result2);

        $this->assertEquals([], $result3);
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
    public function fakeDbTruncate()
    {
        // Arrange
        FakeDB::$changedModels = [
            '::model::' => [
                'create' => [
                    '::test',
                ],
            ],
        ];

        FakeDB::$fakeRows = [
            '::table::' => [
                ['id' => 1],
            ],
        ];

        FakeDB::$tables = [
            '::table::' => [
                ['latestRowIndex' => 1],
            ],
        ];

        // Act
        FakeDB::truncate();

        // Assert
        $this->assertEmpty(FakeDB::$tables);
        $this->assertIsArray(FakeDB::$tables);
        $this->assertEmpty(FakeDB::$fakeRows);
        $this->assertIsArray(FakeDB::$fakeRows);
        $this->assertEmpty(FakeDB::$changedModels);
        $this->assertIsArray(FakeDB::$changedModels);
    }

    /**
     * @test
     */
    public function fakeDbRemoveTableName()
    {
        // Arrange
        $data = [
            'hello.id' => 0,
            'users.id' => 1,
            'users.name' => '::name::',
            'users.name.some' => '::some::',
        ];

        $expectedResult = [
            'id' => 1,
            'name' => '::name::',
            'some' => '::some::',
        ];

        // Act
        $result = FakeDB::removeTableName($data);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }
}
