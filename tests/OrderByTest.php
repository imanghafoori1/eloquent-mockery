<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;


class OrderUser extends Model
{
    use MockableModel;
}

class OrderByTest extends TestCase
{
    /**
     * @test
     */
    public function orderBy()
    {
        OrderUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        OrderUser::addFakeRow(['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        OrderUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $users = OrderUser::query()->orderBy('id')->get();
        $user = $users[0];
        $this->assertEquals(1, $user->id);
        $this->assertEquals('Hello', $user->name);

        $users = OrderUser::query()->orderBy('id', 'desc')->get();
        $user = $users[0];
        $this->assertEquals(3, $user->id);
        $this->assertEquals('Iman 3', $user->name);

        OrderUser::stopFaking();
    }

    /**
     * @test
     */
    public function latest_oldest()
    {
        OrderUser::addFakeRow([
            'id' => 1,
            'name' => 'Hello',
            'age' => 20,
            'created_at' => '2022-08-14 16:59:29'
        ]);
        OrderUser::addFakeRow([
            'id' => 2,
            'name' => 'Iman 2',
            'age' => 30,
            'created_at' => '2022-08-22 16:59:29'
        ]);
        OrderUser::addFakeRow([
            'id' => 3,
            'name' => 'Iman 3',
            'age' => 34,
            'created_at' => '2020-01-10 16:59:29',
        ]);
        OrderUser::addFakeRow([
            'id' => 4,
            'name' => 'Iman 3',
            'age' => 34,
            'created_at' => '2022-08-10 16:59:29',
        ]);

        $user = OrderUser::query()->latest()->first();
        $this->assertEquals(2, $user->id);

        $user = OrderUser::query()->oldest()->first();
        $this->assertEquals(3, $user->id);

        OrderUser::stopFaking();
    }
}
