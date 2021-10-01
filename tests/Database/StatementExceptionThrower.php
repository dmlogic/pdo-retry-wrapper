<?php

namespace Tests\Database;

use PDOException;
use PDOStatement;

class StatementExceptionThrower extends PDOStatement
{
    private ?string $queryException;

    public function __construct(string $queryException = null)
    {
        if ($queryException) {
            $this->queryException = $queryException;
        }
    }

    public function execute($bindings = null)
    {
        if ($this->queryException) {
            throw new PDOException($this->queryException);
        }
    }
}
