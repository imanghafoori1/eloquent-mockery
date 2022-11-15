<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class LikeUser extends Model
{
    use MockableModel;
}

class WhereLikeTest extends TestCase
{
    public function tearDown(): void
    {
        LikeUser::stopFaking();
    }

    /**
     * @test
     */
    public function whereLike()
    {
        LikeUser::addFakeRow(['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        LikeUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        LikeUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = LikeUser::where('name', 'like', '%man 3')->get();
        $this->assertEquals('Iman 3', ($users[0])->name);
        $this->assertEquals(1, ($users->count()));

        $users = LikeUser::where('name', 'like', 'Iman%')->get();
        $this->assertEquals('Iman 2', ($users[0])->name);
        $this->assertEquals('Iman 3', ($users[1])->name);
    }
}
