<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class PluckUser extends Model
{
    protected $table = 'users';
}

class PluckTest extends TestCase
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
    public function pluck_test()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => null, 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => null,]);

        $this->assertEquals([1, 2, 3], PluckUser::pluck('id')->all());
        $this->assertEquals([3, 2, 1], PluckUser::orderBy('id', 'desc')->pluck('id')->all());
        $this->assertEquals([20, 30, null], PluckUser::pluck('age')->all());
        $this->assertEquals([1, 2, 3], PluckUser::query()->pluck('id')->all());
        $this->assertEquals([1], PluckUser::query()->where('id', 1)->pluck('id')->all());
    }
}
