<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class BetweenUser extends Model
{
    use MockableModel;
}

class WhereBetweenTests extends TestCase
{
    public function tearDown(): void
    {
        BetweenUser::stopFaking();
    }

    /**
     * @test
     */
    public function whereBetween()
    {
        BetweenUser::addFakeRow(['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        BetweenUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        BetweenUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = BetweenUser::query()->whereBetween('age', [25, 31])->get();
        $this->assertEquals('Iman 2', ($users[0])->name);
        $this->assertEquals(1, ($users->count()));
    }

    /**
     * @test
     */
    public function whereNotBetween()
    {
        BetweenUser::addFakeRow(['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        BetweenUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        BetweenUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = BetweenUser::query()->whereNotBetween('age', [25, 31])->get();
        $this->assertEquals('Hello', ($users[0])->name);
        $this->assertEquals('Iman 3', ($users[1])->name);
        $this->assertEquals(2, ($users->count()));
    }
}
