<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Support\Collection;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\FakeQueryBuilder;
use PHPUnit\Framework\TestCase;

class JoinTest extends TestCase
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
            'my_text' => 'b 1',
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
    public function where_join()
    {
        $results = (new FakeQueryBuilder())
            ->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
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
                'my_text' => 'b 1',
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
    public function join_select_where()
    {
        $results = (new FakeQueryBuilder())
            ->select('users.common as uc', 'comments.common as cc')
            ->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 3)
            ->get()
            ->all();

        $this->assertEquals([
            [
                'uc' => 'u3',
                'cc' => 'c3',
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function join_with_multi_wheres()
    {
        $results = (new FakeQueryBuilder())
            ->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 3)
            ->where('comments.common', 'c3')
            ->get()
            ->all();

        $this->assertEquals([
            [
                "id" => 3,
                "user_id" => 3,
                "my_text" => "orphan",
                "common" => "c3",
                "name" => "iman 3",
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function join()
    {
        $results = (new FakeQueryBuilder())
            ->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
            ->get();

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals(4, $results->count());

        $e = [
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
                'my_text' => 'b 1',
            ],
            [
                'id' => 4,
                'name' => 'iman 1',
                'common' => 'c4',
                'user_id' => 1,
                'my_text' => 'orphan 4',
            ],
            [
                'id' => 3,
                'name' => 'iman 3',
                'common' => 'c3',
                'user_id' => 3,
                'my_text' => 'orphan',
            ],
        ];
        $this->assertEquals($e, $results->all());

        $this->assertEquals([], (new FakeQueryBuilder())
            ->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 2)
            ->get()
            ->all());

        $this->assertEquals([], (new FakeQueryBuilder())
            ->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
            ->where('comments.user_id', 3)
            ->where('comments.common', 'c33')
            ->get()
            ->all());
    }
}