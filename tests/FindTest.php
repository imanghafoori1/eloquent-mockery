<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use Imanghafoori\EloquentMockery\FakeConnection;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class FindUser extends Model
{
    protected $table = 'users';
}

class FindTest extends TestCase
{
    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
    }

    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();
    }

    /**
     * @test
     */
    public function find()
    {
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'Iman',
        ]);
        FakeDB::addRow('users', [
            'id' => 2,
            'name' => 'Iman 2',
        ]);

        $user = FindUser::find(1);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman', $user->name);
        $this->assertEquals(true, $user->exists);

        $user = FindUser::query()->find(2);
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);

        $users = FindUser::query()->find([1, 2]);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);

        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals('Iman 2', $users[1]->name);
        $this->assertEquals(2, $users->count());

        $user = FindUser::query()->find(10);
        $this->assertNull($user);
    }

    /**
     * @test
     */
    public function findOrFail()
    {
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'Iman',
        ]);
        FakeDB::addRow('users', [
            'id' => 2,
            'name' => 'Iman 2',
        ]);

        $user = FindUser::findOrFail(1);
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Iman', $user->name);

        $user = FindUser::query()->findOrFail(2);
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Iman 2', $user->name);

        $users = FindUser::query()->findOrFail([1, 2]);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);
        $this->assertEquals(true, $users[0]->exists);

        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals('Iman 2', $users[1]->name);
        $this->assertEquals(2, $users->count());

        $this->expectException(ModelNotFoundException::class);
        $user = FindUser::query()->findOrFail(10);

        $this->assertNull($user);
    }

    /**
     * @test
     */
    public function findMany()
    {
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'Iman',
        ]);

        FakeDB::addRow('users', [
            'id' => 2,
            'name' => 'Iman 2',
        ]);

        FakeDB::addRow('users', [
            'id' => 3,
            'name' => 'Iman 3',
        ]);

        $users = FindUser::findMany(1);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);
        $this->assertEquals(1, $users->count());

        $users = FindUser::query()->findMany(2);
        $this->assertEquals(2, $users[0]->id);
        $this->assertEquals('Iman 2', $users[0]->name);
        $this->assertEquals(1, $users->count());

        $users = FindUser::query()->find([1, 2]);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals('Iman', $users[0]->name);
        $this->assertEquals(true, $users[0]->exists);

        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals('Iman 2', $users[1]->name);
        $this->assertEquals(true, $users[1]->exists);

        $this->assertEquals(2, $users->count());
    }

    /**
     * @test
     */
    public function QueryExecutedEventForFind()
    {
        FakeDB::mockQueryBuilder();
        $dispatcher = new Dispatcher;
        $_SERVER['counter'] = 0;
        $dispatcher->listen(QueryExecuted::class, function (QueryExecuted $event) {
            $_SERVER['counter']++;
            $this->assertIsFloat($event->time);
            $this->assertStringStartsWith('select', $event->sql);
        });

        FindUser::query()->getConnection()->setEventDispatcher($dispatcher);

        $users = FindUser::query()->find([1, 2]);

        $this->assertEquals(1, $_SERVER['counter']);
        FakeDB::dontMockQueryBuilder();
    }

    /**
     * @test
     */
    public function queryLog()
    {
        FindUser::query()->getConnection()->enableQueryLog();
        $users = FindUser::query()->find([1, 2]);

        $actual = FindUser::query()->getConnection()->getQueryLog();
        $expected = [
            "query" => 'select * from "users" where "users"."id" in (?, ?)',
        ];
        $this->assertCount(1, $actual);
        $this->assertInstanceOf(FakeConnection::class, FindUser::query()->getConnection());
        $this->assertEquals($expected['query'], str_replace(['1', '2'], ['?', '?'], $actual[0]['query']));

        if ($actual[0]['bindings']) {
            $this->assertEquals([1, 2], $actual[0]['bindings']);
        }
    }
}
