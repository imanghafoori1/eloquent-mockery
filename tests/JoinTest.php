<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Support\Collection;
use Imanghafoori\EloquentMockery\FakeConnection;
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
    public function join_empty_table()
    {
        $results = $this->getBuilder()->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
            ->join('non_comments', 'non_comments.user_id', '=', 'users.id')
            ->where('user_id', 1)->get()->all();

        $this->assertEquals([], $results);
    }

    /**
     * @test
     */
    public function where_join()
    {
        $results = $this->getBuilder()->from('users')
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

        $results = $this->getBuilder()->from('users')->join('comments', 'comments.user_id', '=', 'users.id')->where('user_id', 1)->get()->all();

        $this->assertEquals($expected, $results);
    }

    /**
     * @test
     */
    public function join_select_where()
    {
        $results = $this->getBuilder()->select('users.id', 'users.common as common', 'comments.common as cc')->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 3)->get()->all();

        $this->assertEquals([
            [
                'common' => 'u3',
                'cc' => 'c3',
                'id' => 3
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function join_select_star()
    {
        $results = $this->getBuilder()->select('comments.*')->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 3)->get()->all();

        $this->assertEquals([
            [
                'id' => 3,
                'user_id' => 3,
                'my_text' => 'orphan',
                'common' => 'c3',
            ],
        ], $results);

        $results = $this->getBuilder()->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 3)->get([
            'comments.*',
            'users.id as uid',
        ])->all();

        $this->assertEquals([
            [
                'id' => 3,
                'user_id' => 3,
                'my_text' => 'orphan',
                'common' => 'c3',
                'uid' => 3,
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function join_with_multi_wheres()
    {
        $results = $this->getBuilder()->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 3)->where('comments.common', 'c3')->get()->all();

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
        $results = $this->getBuilder()->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->get();

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
                'my_text' => 'c 2',
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

        $this->assertEquals([], $this->getBuilder()->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 2)->get()->all());

        $this->assertEquals([], $this->getBuilder()->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 3)->where('comments.common', 'c33')->get()->all());
    }

    /**
     * @test_
     */
    public function left_join()
    {
        FakeDB::table('comments')->addRow([
            'id' => 5,
            'user_id' => 10,
            'my_text' => 'left joined',
            'common' => 'left joined',
        ]);

        $results = $this->getBuilder()
            ->from('users')
            ->leftJoin('comments', 'users.id', '=', 'comments.user_id')
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
                'my_text' => 'c 2',
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

        $this->assertEquals([], $this->getBuilder()->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 2)->get()->all());

        $this->assertEquals([], $this->getBuilder()->from('users')->join('comments', 'users.id', '=', 'comments.user_id')->where('comments.user_id', 3)->where('comments.common', 'c33')->get()->all());
    }

    /**
     * @test
     */
    public function join_multiple_tables()
    {
        FakeDB::table('posts')->addRow([
            'id' => 1,
            'body' => 'post 1',
            'user_id' => 1,
        ]);

        FakeDB::table('posts')->addRow([
            'id' => 2,
            'body' => 'body 2',
            'user_id' => 2,
        ]);

        FakeDB::table('posts')->addRow([
            'id' => 3,
            'body' => 'post 3',
            'user_id' => 1,
        ]);

        FakeDB::table('posts')->addRow([
            'id' => 4,
            'body' => 'body 4',
            'user_id' => 10,
        ]);
        $results = $this->getBuilder()->from('users')
            ->join('comments', 'users.id', '=', 'comments.user_id')
            ->join('posts', 'users.id', '=', 'posts.user_id')
            ->get();

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals(6, $results->count());

        $first = $results[0];
        $this->assertTrue($first == [
            "id" => 1,
            "common" => "c1",
            "name" => "iman 1",
            "user_id" => 1,
            "my_text" => "a 1",
            "body" => "post 1",
        ]);

   }

    private function getBuilder(): FakeQueryBuilder
    {
        return FakeConnection::resolve()->query();
    }
}