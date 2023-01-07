<?php

namespace Imanghafoori\EloquentMockery\Tests\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class HasOneUser extends Model
{
    public $fillable = ['name'];

    protected $table = 'users';

    public function comments()
    {
        return $this->hasOne(HasOneComment::class, 'user_id');
    }
}

class HasOneComment extends Model
{
    protected $table = 'comments';

    public $fillable = ['comment'];

    public function user()
    {
        return $this->belongsTo(HasOneUser::class);
    }
}

class HasOneTest extends TestCase
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
    public function has_one()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1']);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2']);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3']);
        FakeDB::addRow('users', ['id' => 4, 'name' => 'Iman 4']);

        FakeDB::addRow('comments', ['id' => 1, 'user_id' => 1, 'comment' => 'sss']);
        FakeDB::addRow('comments', ['id' => 2, 'user_id' => 1, 'comment' => 'aaa']);
        FakeDB::addRow('comments', ['id' => 3, 'user_id' => 3, 'comment' => 'bbb']);

        $user = HasOneUser::with('comments')->where('id', 1)->first();

        $this->assertInstanceOf(HasOneComment::class, $user->comments);
        $this->assertEquals(1, $user->comments->id);

        $user = HasOneUser::find(1)->load('comments');
        $this->assertInstanceOf(HasOneComment::class, $user->comments);
        $this->assertEquals(1, $user->comments->id);

        $this->assertEquals(2, HasOneUser::find(1)->comments()->count());
        $this->assertEquals(1, HasOneUser::find(1)->comments()->where('comment', 'sss')->count());

        $this->assertEquals('sss', HasOneUser::find(1)->comments()->first()->comment);
        $time = Carbon::now()->getTimestamp();

        $newUser = HasOneComment::query()->find(3)->user()->create([
            'name' => 'created',
        ]);

        $this->assertEquals($time, $newUser->created_at->getTimestamp());
        $this->assertEquals($time, $newUser->updated_at->getTimestamp());
        $this->assertEquals('created', $newUser->name);
        //$this->assertSame(HasOneUser::getCreatedModel(), $newUser);
        $this->assertNotNull(HasOneUser::find(5));
        $this->assertEquals(5, HasOneUser::count());

        $comment = HasOneUser::find(4)->comments()->create([
            'comment' => 'created!',
        ]);

        $this->assertEquals('created!', $comment->comment);
        $this->assertEquals(4, $comment->id);
        $this->assertEquals(4, $comment->user_id);
        $this->assertEquals($time, $comment->created_at->getTimestamp());
        $this->assertEquals($time, $comment->updated_at->getTimestamp());
        //$this->assertSame(HasOneComment::getCreatedModel(), $comment);
    }
}
