<?php

namespace Imanghafoori\EloquentMockery\Tests\Wheres;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class WhereHasUser extends Model
{
    protected $table = 'users';

    public $fillable = ['name'];

    public function comments()
    {
        return $this->hasMany(WhereHasComment::class, 'user_id');
    }
}

class WhereHasComment extends Model
{
    protected $table = 'comments';

    public $fillable = ['comment'];

    public function user()
    {
        return $this->belongsTo(WhereHasUser::class);
    }
}

class WhereHasTest extends TestCase
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
    public function whereHas()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1']);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2']);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3']);
        FakeDB::addRow('users', ['id' => 4, 'name' => 'Iman 4']);

        FakeDB::addRow('comments', ['id' => 3, 'user_id' => 2]);

        $users = WhereHasUser::query()->whereHas('comments')->get();
        $this->assertEquals(1, $users->count());
        $this->assertEquals(2, $users[0]->id);

        FakeDB::addRow('comments', ['id' => 1, 'user_id' => 1]);

        $users = WhereHasUser::query()->whereHas('comments')->get();
        $this->assertEquals(2, $users->count());
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals(2, $users[1]->id);

        FakeDB::addRow('comments', ['id' => 1, 'user_id' => 4]);

        $users = WhereHasUser::query()->whereHas('comments')->get();
        $this->assertEquals(3, $users->count());
        $this->assertEquals(1, $users[0]->id);
        $this->assertEquals(2, $users[1]->id);
        $this->assertEquals(4, $users[2]->id);
    }
}