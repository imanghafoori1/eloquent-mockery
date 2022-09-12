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

    /**
     * @test
     */
    public function where_left_join()
    {
        $results = (new FakeQueryBuilder())
            ->from('users')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
            ->where('user_id', 1)
            ->get()
            ->all();

        $expected = [
            [
                'id' => 1,
                'name' => 'iman 1',
                'common' => 'c1',
                'user_id' => 1,
                'my_text' => 'a 1',
            ],
            [
                'id' => 2,
                'name' => 'iman 1',
                'common' => 'c2',
                'user_id' => 1,
                'my_text' => 'c 2',
            ],
            [
                'id' => 4,
                'name' => 'iman 1',
                'common' => 'c4',
                'user_id' => 1,
                'my_text' => 'orphan 4',
            ],
        ];

        $this->assertEquals($expected, $results);
    }

    /**
     * @test
     */
    public function left_join_select_where()
    {
        $results = (new FakeQueryBuilder())
            ->select('users.id', 'users.common as user_common', 'comments.common as comment_common')
            ->from('users')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 3)
            ->get()
            ->all();

        $this->assertEquals([
            [
                'user_common' => 'u3',
                'comment_common' => 'c3',
                'id' => 3
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function left_join_select_star()
    {
        $results = (new FakeQueryBuilder())
            ->select('comments.*')
            ->from('users')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 3)
            ->get()
            ->all();

        $this->assertEquals([
            [
                'id' => 3,
                'user_id' => 3,
                'my_text' => 'orphan',
                'common' => 'c3',
            ],
        ], $results);

        $results = (new FakeQueryBuilder())
            ->from('users')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 3)
            ->get(['comments.*', 'users.id as user_id'])
            ->all();

        $this->assertEquals([
            [
                'id' => 3,
                'user_id' => 3,
                'my_text' => 'orphan',
                'common' => 'c3',
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function left_join_with_multi_wheres()
    {
        $results = (new FakeQueryBuilder())
            ->from('users')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 3)
            ->where('comments.common', 'c3')
            ->get()
            ->all();

        $this->assertEquals([
            [
                "id"        => 3,
                "user_id"   => 3,
                "my_text"   => "orphan",
                "common"    => "c3",
                "name"      => "iman 3",
            ],
        ], $results);
    }
}
