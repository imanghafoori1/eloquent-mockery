<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class SelectyUser extends Model
{
    use MockableModel;
}

class SelectTest extends TestCase
{
    /**
     * @test
     */
    public function test_get()
    {
        SelectyUser::addFakeRow(['id' => 1, 'name' => null, 'age' => 20,]);
        SelectyUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        SelectyUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
        SelectyUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 40,]);

        $users = SelectyUser::whereNull('name')->get(['age']);
        $this->assertEquals(null, ($users[0])->id);
        $this->assertEquals(20, ($users[0])->age);
        $this->assertEquals(1, $users->count());

        $users = SelectyUser::whereNull('name')->select('age')->first();
        $this->assertEquals(null, $users->id);
        $this->assertEquals(20, $users->age);

        $users = SelectyUser::whereNull('name')->select('age as mag')->first();
        $this->assertEquals(null, $users->id);
        $this->assertEquals(20, $users->mag);
        $this->assertEquals(null, $users->age);

        $users = SelectyUser::whereNull('name')->select(['age as mag', 'id as uid'])->first();
        $this->assertEquals(null, $users->id);
        $this->assertEquals(1, $users->uid);
        $this->assertEquals(20, $users->mag);
        $this->assertEquals(null, $users->age);

        $users = SelectyUser::whereNull('name')->first('age');
        $this->assertEquals(null, $users->id);
        $this->assertEquals(20, $users->age);

        $users = SelectyUser::whereNull('name')->first('age as mage');
        $this->assertEquals(null, $users->id);
        $this->assertEquals(null, $users->age);
        $this->assertEquals(20, $users->mage);

        $users = SelectyUser::whereNull('name')->select('age')->addSelect('id')->first();
        $this->assertEquals(1, $users->id);
        $this->assertEquals(20, $users->age);

        $users = SelectyUser::whereNull('name')->select('age')->first('id');
        $this->assertEquals(1, $users->id);
        $this->assertEquals(20, $users->age);

        SelectyUser::stopFaking();
    }
}
