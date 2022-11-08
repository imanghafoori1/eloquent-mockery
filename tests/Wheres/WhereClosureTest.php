<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class UserClosure extends Model
{
    use MockableModel;
}

class WhereClosureTest extends TestCase
{
    public function tearDown(): void
    {
        UserClosure::stopFaking();
    }

    /**
     * @test
     */
    public function where_closure()
    {
        UserClosure::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        UserClosure::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        UserClosure::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);
        UserClosure::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);
        UserClosure::addFakeRow(['id' => 5, 'name' => 'Iman 4', 'age' => 10,]);

        $users = UserClosure::query()
            ->where('age', '<', 31)
            ->where(function ($query) {
                $query->where('age', '>', 21);
            })->get();

        $this->assertEquals('Iman 2', ($users[0])->name);
        $this->assertTrue(($users[0])->exists);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(1, $users->count());

        UserClosure::stopFaking();
    }
}
