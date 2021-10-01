<?php

namespace Tests\Database;

use PDO;
use PDOException;

class PDOExceptionThrower extends PDO
{
    private ?string $connectionException = null;
    private ?string $queryException = null;

    public function prepare($statement, $options = null)
    {
        if ($this->connectionException) {
            throw new PDOException($this->connectionException);
        }
        return new StatementExceptionThrower($this->queryException);
    }

    public function throwOnConnection($message)
    {
        $this->connectionException = $message;
    }

    public function throwOnQuery($message)
    {
        $this->queryException = $message;
    }
}
