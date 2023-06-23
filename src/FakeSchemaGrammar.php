<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Illuminate\Support\Fluent;

class FakeSchemaGrammar extends SchemaGrammar
{
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        return $this->stringy([
            'args' => [$blueprint, $command, $connection],
            'type' => 'createTable',
            'sql' => parent::compileCreate($blueprint, $command, $connection),
        ]);
    }

    public function compileTableExists()
    {
        return $this->stringy([
            'type' => 'tableExists',
            'sql' => parent::compileTableExists(),
        ]);
    }

    private function stringy(array $data)
    {
        return new class ($data){

            public $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function __toString()
            {
                return $this->data['sql'];
            }
        };
    }
}