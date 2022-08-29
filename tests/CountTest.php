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
        CountUser::addFakeRow(['id' => 1, 'name' => null, 'age' => -20,]);
        CountUser::addFakeRow(['id' => 2, 'name' => '', 'age' => 30,]);
        CountUser::addFakeRow(['id' => 3, 'name' => [], 'age' => null,]);
        CountUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 0,]);

        $this->assertEquals(4, CountUser::count());
        $this->assertEquals(4, CountUser::query()->count());
        $this->assertEquals(1, CountUser::query()->where('id', 1)->count());
        $this->assertEquals(1, CountUser::whereNull('name')->count());
        $this->assertEquals(0, CountUser::whereNull('id')->count());
        $this->assertEquals(1, CountUser::query()->where('id', '<', 4)->where('age', '>', 20)->count());
        $this->assertEquals(3, CountUser::count('name'));
        $this->assertEquals(3, CountUser::count('age'));
    }
}
