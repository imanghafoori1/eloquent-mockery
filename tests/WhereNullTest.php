<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class WhereNullUser extends Model
{
    use MockableModel;
}

class WhereNullTest extends TestCase
{
    public function setUp(): void
    {
        WhereNullUser::addFakeRow(['id' => 1, 'name' => null, 'age' => 20,]);
        WhereNullUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        WhereNullUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => null,]);
    }

    public function tearDown(): void
    {
        WhereNullUser::stopFaking();
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

    public function whereNotNull()
    {
        $users = WhereNullUser::whereNotNull('name')->get();
        $this->assertEquals(2, ($users[0])->id);
        $this->assertEquals(3, ($users[1])->id);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(2, $users->count());
    }
}
