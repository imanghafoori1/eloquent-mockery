<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class InsertyUser extends Model
{
    //
}

class InsertTest extends TestCase
{
    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();
    }

    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
    }

    /**
     * @test
     */
    public function insertBasicTest()
    {
        FakeDB::mockQueryBuilder();
        InsertyUser::query()->insert(['name' => 'Hello', 'age' => 20,]);
        InsertyUser::query()->insert(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        $res3 = InsertyUser::query()->insert(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);
        $res4 = InsertyUser::query()->insert(['name' => 'Hello', 'age' => 20,]);

        $users = InsertyUser::query()->whereKey(1)->get();
        $this->assertEquals(1, ($users[0])->getKey());
        $this->assertEquals('Hello', ($users[0])->name);

        $count = InsertyUser::query()->count();
        $this->assertEquals(4, $count);

        $users = InsertyUser::query()->find(2);
        $this->assertNotNull($users);
        $users = InsertyUser::query()->find(4);
        $this->assertNotNull($users);

        $this->assertTrue($res3);
        $this->assertTrue($res4);
    }
}
