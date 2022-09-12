<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\FakeQueryBuilder;
use PHPUnit\Framework\TestCase;

class LeftJoinTest extends TestCase
{
    public function setUp(): void
    {
        FakeDB::table('users')->addRow(['id' => 1, 'common' => 'u1', 'name' => 'iman 1']);
        FakeDB::table('users')->addRow(['id' => 2, 'common' => 'u2', 'name' => 'iman 2']);
        FakeDB::table('users')->addRow(['id' => 3, 'common' => 'u3', 'name' => 'iman 3']);

        FakeDB::table('comments')->addRow([
            'id' => 1,
            'user_id' => 1,
            'my_text' => 'a 1',
            'common' => 'c1',
        ]);
        FakeDB::table('comments')->addRow([
            'id' => 2,
            'user_id' => 1,
            'my_text' => 'c 2',
            'common' => 'c2',
        ]);
        FakeDB::table('comments')->addRow([
            'id' => 3,
            'user_id' => 3,
            'my_text' => 'orphan',
            'common' => 'c3',
        ]);
        FakeDB::table('comments')->addRow([
            'id' => 4,
            'user_id' => 1,
            'my_text' => 'orphan 4',
            'common' => 'c4',
        ]);
    }

    public function tearDown(): void
    {
        FakeDB::truncate();
    }

    /**
     * @test
     */
    public function left_join_empty_table()
    {
        $results = (new FakeQueryBuilder())
            ->from('users')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
            ->where('user_id', 2)
            ->get()
            ->all();

        $this->assertEquals([], $results);
    }
}
