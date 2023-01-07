<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class WhereNullUser extends Model
{
    protected $table = 'users';
}

class WhereNullTest extends TestCase
{
    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();
        FakeDB::addRow('users', ['id' => 1, 'name' => null, 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
    }

    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
    }

    /**
     * @test
     */
    public function whereNull()
    {
        $users = WhereNullUser::whereNull('name')->get();
        $this->assertEquals(1, ($users[0])->id);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(1, $users->count());
    }

    /**
     * @test
     */
    public function whereNotNull()
    {
        $users = WhereNullUser::whereNotNull('name')->get();
        $this->assertEquals(2, ($users[0])->id);
        $this->assertEquals(3, ($users[1])->id);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(2, $users->count());
    }
}
