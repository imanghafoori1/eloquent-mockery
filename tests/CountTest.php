<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class CountUser extends Model
{
    use MockableModel;
}

class CountTest extends TestCase
{
    /**
     * @test
     */
    public function basic_count()
    {
        CountUser::addFakeRow(['id' => 1, 'name' => null, 'age' => 20,]);
        CountUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        CountUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
        CountUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);

        $count1 = CountUser::count();
        $count2 = CountUser::query()->count();
        $count3 = CountUser::query()->where('id', 1)->count();
        $count4 = CountUser::whereNull('name')->count();
        $count5 = CountUser::whereNull('id')->count();
        $count6 = CountUser::query()->where('id', '<', 4)->where('age', '>', 20)->count();

        $this->assertEquals(4, $count1);
        $this->assertEquals(4, $count2);
        $this->assertEquals(1, $count3);
        $this->assertEquals(1, $count4);
        $this->assertEquals(0, $count5);
        $this->assertEquals(1, $count6);
    }
}
