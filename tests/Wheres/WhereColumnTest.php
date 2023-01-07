<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class WhereColumnUser extends Model
{
    protected $table = 'users';
}

class WhereColumnTest extends TestCase
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
    public function whereColumn()
    {
        WhereColumnUser::insert(['id' => 1, 'name' => 22, 'age' => 20,]);
        WhereColumnUser::insert(['id' => 2, 'name' => 12, 'age' => 30,]);
        WhereColumnUser::insert(['id' => 3, 'name' => 34, 'age' => 34,]);

        $users = WhereColumnUser::query()->whereColumn('name', '=', 'age')->get();
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertCount(1, $users);
        $this->assertEquals(3, $users[0]->id);
        $this->assertInstanceOf(WhereColumnUser::class, $users[0]);

        $users = WhereColumnUser::query()->whereColumn('name', 'age')->get();
        $this->assertEquals(1, $users->count());
        $this->assertEquals(3, $users[0]->id);

        $users = WhereColumnUser::query()->whereColumn('name', '>', 'age')->get();
        $this->assertCount(1, $users);
        $this->assertEquals(1, $users[0]->id);

        $users = WhereColumnUser::query()->whereColumn('name', '<', 'age')->get();
        $this->assertCount(1, $users);
        $this->assertEquals(2, $users[0]->id);

        $users = WhereColumnUser::query()->whereColumn('name', '>=', 'age')->get();
        $this->assertCount(2, $users);

        $users = WhereColumnUser::query()->whereColumn('name', '>=', 'age')->get();
        $this->assertCount(2, $users);
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals(3, $users[1]->id);

        $users = WhereColumnUser::query()
            ->where('age', '>', 30)
            ->whereColumn('name', '>=', 'age')
            ->get();
        $this->assertCount(1, $users);
        $this->assertEquals(3, $users[0]->id);
    }

    /**
     * @test
     */
    public function orWhereColumnIsIgnored()
    {
        // @todo
        WhereColumnUser::insert(['id' => 1, 'name' => 20, 'age' => 20,]);
        WhereColumnUser::insert(['id' => 2, 'name' => 31, 'age' => 30,]);
        WhereColumnUser::insert(['id' => 3, 'name' => 31, 'age' => 34,]);

        $users = WhereColumnUser::query()
            ->whereColumn('name', '>', 'age')
            ->orWhereColumn('name', '=', 'age')
            ->get();

        $this->assertCount(1, $users);
    }
}
