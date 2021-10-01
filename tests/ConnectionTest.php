<?php

namespace Tests;

use Throwable;
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
    public function it_returns_a_pdo_compatible_response_on_query()
    {
        $result = $this->realConnection()
                       ->runQuery('select * from users');
        $this->assertInstanceOf(PDOStatement::class, $result);
        $users = $result->fetchAll();
        $this->assertCount(2, $users);
        $this->assertEquals('two@example.com', $users[1]['email']);
    }

    /**
     * @test
     */
    public function it_throws_a_pdo_exception_on_bad_query()
    {
        $this->expectException(PDOException::class);
        $result = $this->realConnection()
                       ->runQuery('select * from notatable');
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
                        ->runQuery('select * from users');
    }

    /**
     * @test
     */
    public function it_throws_a_connection_exception_on_query_run()
    {
        $expectedException = 'SQLSTATE[HY000] [2002] Connection timed out';
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage($expectedException);
        $result = $this->mockedConnection(null, $expectedException)
                        ->runQuery('select * from users');
    }

    /**
    * @test
    */
    public function connection_exceptions_run_callbacks()
    {
        $_ENV['marker'] = 'original';
        $callBack = function (Throwable $e) {
            $_ENV['marker'] = 'edited to '.$e->getMessage();
        };

        try {
            $expectedException = 'server has gone away';
            $this->mockedConnection($expectedException, null, $callBack)
                        ->runQuery('select * from users');
            $this->assertTrue(false);
        } catch (ConnectionException $e) {
            $this->assertSame('edited to server has gone away', $_ENV['marker']);
        }
    }

    /**
    * @test
    */
    public function connection_failures_are_retried_up_to_the_limit()
    {
        try {
            $this->mockedConnection(null, 'server has gone away')
                        ->runQuery('select * from users');
            $this->assertTrue(false);
        } catch (ConnectionException $e) {
            $this->assertSame(3, $e->getAttempts());
        }
    }

    private function realConnection($database = 'sqlite::memory:')
    {
        return new Connection(
            (new Migrator)($database)
        );
    }

    private function mockedConnection($connectionMessage, $queryMessage = null, $callback = null)
    {
        $mock = new PDOExceptionThrower('sqlite::memory:');
        if ($connectionMessage) {
            $mock->throwOnConnection($connectionMessage);
        }
        if ($queryMessage) {
            $mock->throwOnQuery($queryMessage);
        }
        return new Connection(function () use ($mock) {
            return $mock;
        }, $callback);
    }
}
