<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class SoleUser extends Model
{
    use MockableModel;
}

class SoleTest extends TestCase
{
    public function tearDown(): void
    {
        SoleUser::stopFaking();
    }

    public function testSoleFailsForMultipleRecords()
    {
        $this->skipIfNeeded();

        SoleUser::addFakeRow(['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        SoleUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        SoleUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $this->expectException(MultipleRecordsFoundException::class);

        SoleUser::query()->where('age', '>', 21)->sole();
    }

    public function testSole()
    {
        $this->skipIfNeeded();

        SoleUser::addFakeRow(['id' => 1, 'name' => 'Hello', 'age' => 20,]);
        SoleUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        SoleUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $expected = SoleUser::query()->where('name', 'Hello')->first();
        $sole = SoleUser::query()->where('name', 'Hello')->sole();
        $this->assertTrue($expected->is($sole));
    }

    public function testSoleFailsIfNoRecords()
    {
        $this->skipIfNeeded();

        SoleUser::addFakeRow(['id' => 1, 'name' => 'Hello1', 'age' => 20,]);

        try {
            SoleUser::query()->where('name', 'no-name')->sole();
        } catch (ModelNotFoundException $exception) {
            //
        }

        $this->assertSame(SoleUser::class, $exception->getModel());
    }

    private function skipIfNeeded(): void
    {
        if (! method_exists(Builder::class, 'sole')) {
            $this->markTestSkipped();
        }
    }
}
