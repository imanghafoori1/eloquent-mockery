<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;

class FakeSchemaGrammar extends SchemaGrammar
{
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