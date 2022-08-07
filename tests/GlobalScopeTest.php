<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class GlobalScopeUser extends Model
{
    use MockableModel;

    protected static function booted()
    {
        static::addGlobalScope(new AncientScope);
    }
}

class AncientScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('id', '>', 2);
    }
}

class GlobalScopeTest extends TestCase
{
    public function tearDown(): void
    {
        GlobalScopeUser::stopFaking();
    }

    /**
     * @test
     */
    public function global_scope_test()
    {
        GlobalScopeUser::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        GlobalScopeUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        GlobalScopeUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $user = GlobalScopeUser::get();
        $user = $user[0];
        $this->assertEquals('Iman 3', $user->name);
        $user = GlobalScopeUser::query()->get();
        $user = $user[0];
        $this->assertEquals('Iman 3', $user->name);

        GlobalScopeUser::stopFaking();
    }

    /**
     * @test
     */
    public function global_scope_raw_update()
    {
        GlobalScopeUser::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        GlobalScopeUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        GlobalScopeUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);
        GlobalScopeUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 37,]);

        $count = GlobalScopeUser::query()->update(['age' => 40]);
        $this->assertEquals(2, $count);

        GlobalScopeUser::stopFaking();
    }
}
