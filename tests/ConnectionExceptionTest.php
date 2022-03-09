<?php

namespace Tests;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use iHasco\PdoRetryWrapper\ConnectionException;

class ConnectionExceptionTest extends TestCase
{
    /**
    * @test
    */
    public function the_getters_provide_the_expected_data()
    {
        $originalException = new RuntimeException('boo');
        $queryAttempts = 1;
        $queryString = 'select * from users where id = ?';
        $queryBindings = [1];

        $exception = new ConnectionException(
            $originalException,
            $queryAttempts,
            $queryString,
            $queryBindings
        );

        $this->assertSame('boo', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($originalException, $exception->getOriginalException());
        $this->assertSame($queryAttempts, $exception->getAttempts());
        $this->assertSame($queryString, $exception->getQuery());
        $this->assertSame($queryBindings, $exception->getBindings());
    }
}
