<?php

namespace Imanghafoori\EloquentMockery;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

class FakeConnection extends Connection implements ConnectionInterface
{
    public static function resolve($connection = null, $db = '', $prefix = '', $config = ['name' => 'arrayDB'])
    {
        $fakeConnection = new FakeConnection(function () {
            return new FakePDO;
        }, $db, $prefix, $config);

        $fakeConnection->setQueryGrammar(new FakeGrammar);
        
        return $fakeConnection;
    }

    public function transaction(Closure $callback, $attempts = 1)
    {
        return $callback();
    }

    public function getSchemaBuilder()
    {
        return new FakeSchemaBuilder($this);
    }

    public function getSchemaGrammar()
    {
        return $this->getDefaultSchemaGrammar();
    }

    public function query()
    {
        return new FakeQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    protected function getDefaultQueryGrammar()
    {
        return new FakeGrammar;
    }

    public function statement($query, $bindings = [])
    {
        if (is_object($query)) {
            $query = $query->data;
        } else {
            
        }
        if (FakeSchemaGrammar::$query && is_string($query)) {
            $payload = array_shift(FakeSchemaGrammar::$query);
            $query = [
                'sql' => $query,
                'args' => $payload['args'] ?? null,
                'type' => $payload['type'],
            ];
        }

        if (is_string($query)) {
            return parent::statement($query);
        }

        $pretending = true;

        return $this->run($query['sql'], $bindings, function () use ($query, $pretending) {
            if ($this->pretending()) {
                return $pretending;
            }

            if ($query['type'] === 'createTable') {
                FakeDB::createTable($query['args']);
            } elseif ($query['type'] === 'index') {
            } elseif ($query['type'] === 'primary') {
            } elseif ($query['type'] === 'autoIncrementStartValue') {
            } elseif ($query['type'] === 'change') {
            } elseif ($query['type'] === 'enableForeignKeyConstraints') {
            } elseif ($query['type'] === 'disableForeignKeyConstraints') {
            } elseif ($query['type'] === 'dropColumn') {
                [$blueprint, $fluent] = $query['args'];
                foreach ($blueprint->getCommands() as $fluentCommand) {
                    $cols = $fluentCommand->getAttributes()['columns'];
                    FakeDB::dropColumns($blueprint->getTable(), $cols);
                }
            } elseif ($query['type'] === 'drop') {
                [$blueprint, $fluent] = $query['args'];
                FakeDB::dropTable($blueprint->getTable());
            } elseif ($query['type'] === 'dropAllTables') {
                FakeDB::dropAllTables();
            } elseif ($query['type'] === 'dropIfExists') {
                [$blueprint, $fluent] = $query['args'];
                FakeDB::dropTable($blueprint->getTable());
            } elseif ($query['type'] === 'rename') {
                [$blueprint, $fluent] = $query['args'];

                /**
                 * @var $blueprint \Illuminate\Database\Schema\Blueprint
                 */
                $from = $blueprint->getTable();
                $to = $blueprint->getCommands()[0]->getAttributes()['to'];

                FakeDB::renameTable($from, $to);
            } else {
                return (bool) FakeDB::insertGetId($query['value'], $query['builder']->from);
            }
        });
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->runFake($query->data, $bindings, []);
    }

    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        return $this->runFake($query->data, $bindings, []);
    }

    public function affectingStatement($query, $bindings = [])
    {
        $queryObj = $query;
        $query = $query->data;
        $type = $query['type'];
        if ('insertOrIgnore' === $type) {
            $this->insert($queryObj, $bindings);

            return Arr::isAssoc($query['value']) ? 1 : count($query['value']);
        }

        if (in_array($type, ['update', 'delete', 'truncate', 'upsert'])) {
            return $this->runFake($query, $bindings, 0);
        }
    }

    protected function runFake($data, $bindings, $pretend)
    {
        return $this->run($data['sql'], $bindings, function () use ($data, $pretend, $bindings) {
            if ($this->pretending()) {
                return $pretend;
            }

            $data['bindings'] = $bindings;

            return FakeDb::exec($data);
        });
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        $grammar = new FakeSchemaGrammar();

        return $this->withTablePrefix($grammar);
    }
}
