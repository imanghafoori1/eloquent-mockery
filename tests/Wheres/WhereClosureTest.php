<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class UserClosure extends Model
{
    protected $table = 'users';
}

class WhereClosureTest extends TestCase
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
    public function where_closure()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);
        FakeDB::addRow('users', ['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);
        FakeDB::addRow('users', ['id' => 5, 'name' => 'Iman 4', 'age' => 10,]);

        $users = UserClosure::query()
            ->where('age', '<', 31)
            ->where(function ($query) {
                $query->where('age', '>', 21);
            })->get();

        $this->assertEquals('Iman 2', ($users[0])->name);
        $this->assertEquals(true, ($users[0])->exists);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(1, $users->count());
    }
}
