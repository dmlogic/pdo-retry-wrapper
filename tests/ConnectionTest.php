<?php

namespace Tests;

use PDOException;
use PDOStatement;
use Tests\Database\Migrator;
use PHPUnit\Framework\TestCase;
use Dmlogic\PdoRetryWrapper\Connection;
use Tests\Database\PDOExceptionThrower;
use Dmlogic\PdoRetryWrapper\ConnectionException;

class ConnectionTest extends TestCase
{
    /**
    * @test
    */
    public function it_instantiates()
    {
        $db = $this->realConnection();
        $this->assertInstanceOf(Connection::class, $db);
    }

    /**
     * @test
     */
    public function it_returns_a_statement_from_a_query()
    {
        $result = $this->realConnection()
                       ->performQuery('select * from users');
        $this->assertInstanceOf(PDOStatement::class, $result);
    }

    /**
     * @test
     */
    public function it_throws_a_pdo_exception_on_bad_query()
    {
        $this->expectException(PDOException::class);
        $result = $this->realConnection()
                        ->performQuery('select * from notatable');
    }

    /**
     * @test
     */
    public function it_throws_a_connection_exception_on_connection_error()
    {
        $expectedException = 'server has gone away';
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage($expectedException);
        $result = $this->mockedConnection($expectedException)
                        ->performQuery('select * from users');
    }

    /**
     * @test
     */
    public function it_throws_a_connection_exception_on_prepare()
    {
        $expectedException = 'SQLSTATE[HY000] [2002] Connection timed out';
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage($expectedException);
        $result = $this->mockedConnection(null, $expectedException)
                        ->performQuery('select * from users');
    }

    private function realConnection($database = 'sqlite::memory:')
    {
        return new Connection(
            (new Migrator)($database)
        );
    }

    private function mockedConnection($connectionMessage, $queryMessage = null)
    {
        $mock = new PDOExceptionThrower('sqlite::memory:');
        if ($connectionMessage) {
            $mock->throwOnConnection($connectionMessage);
        }
        if ($queryMessage) {
            $mock->throwOnQuery($queryMessage);
        }
        return new Connection($mock);
    }
}
