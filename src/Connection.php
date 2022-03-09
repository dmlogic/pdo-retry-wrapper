<?php

namespace Dmlogic\PdoRetryWrapper;

use PDO;
use Closure;
use Throwable;
use PDOStatement;
use BadMethodCallException;
use Illuminate\Database\DetectsLostConnections;

class Connection extends PDO
{
    use DetectsLostConnections;

    private ?PDO $pdo = null;
    private ?Closure $connector = null;
    private int $maxAttempts = 3;
    private int $currentAttempts = 1;
    private ?Closure $exceptionCallback = null;

    public function __construct(Closure $connector, ?Closure $exceptionCallback = null)
    {
        $this->connector = $connector;
        $this->exceptionCallback = $exceptionCallback;
    }

    public function setMaxAttempts(int $value)
    {
        $this->maxAttempts = $value;
    }

    public function runQuery(string $sql, ?array $bindings = null, ?array $options = []): PDOStatement
    {
        $this->currentAttempts = 1;
        $forceReconnect = false;
        return $this->connectAndPerformQuery($sql, $bindings, $options, $forceReconnect);

        // while ($this->currentAttempts < $this->maxAttempts) {
        //     try {
        //         return $this->connectAndPerformQuery($sql, $bindings, $options, $forceReconnect);
        //     } catch (Throwable $e) {
        //         if (!$this->causedByLostConnection($e)) {
        //             throw $e;
        //         }
        //         $forceReconnect = true;
        //         $this->currentAttempts ++;
        //     }
        // }
        // return $this->throwConnectionException($e, $sql, $bindings);
    }

    private function throwConnectionException(Throwable $originalException, string $sql, ?array $bindings)
    {
        $connectionException = new ConnectionException(
            $originalException,
            $this->currentAttempts,
            $sql,
            $bindings
        );
        if ($this->exceptionCallback) {
            call_user_func($this->exceptionCallback, $connectionException);
        }
        throw $connectionException;
    }

    private function connectAndPerformQuery(string $sql, ?array $bindings, ?array $options = [], bool $forceReconnect = false): PDOStatement
    {
        if ($forceReconnect) {
            $this->reconnect();
        } else {
            $this->reconnectIfMissingConnection();
        }
        $query = $this->pdo->prepare($sql, $options);
        $query->execute($bindings);
        return $query;
    }

    private function reconnectIfMissingConnection(): void
    {
        if (!$this->pdo instanceof PDO) {
            $this->reconnect();
        }
    }

    private function reconnect(): void
    {
        $this->pdo = call_user_func($this->connector);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function getPdo(): PDO
    {
        $this->reconnectIfMissingConnection();
        return $this->pdo;
    }

    /**
     * Down here we provide the full interface to PDO so that
     * we can provide this class as a drop-in replacement
     */

    public function beginTransaction()
    {
        return $this->getPdo()->beginTransaction();
    }
    public function commit()
    {
        return $this->getPdo()->commit();
    }
    public function errorCode()
    {
        return $this->getPdo()->errorCode();
    }
    public function errorInfo()
    {
        return $this->getPdo()->errorInfo();
    }
    public function exec($statement)
    {
        return $this->getPdo()->exec($statement);
    }
    public function getAttribute($attribute)
    {
        return $this->getPdo()->getAttribute($attribute);
    }
    public function inTransaction()
    {
        return $this->getPdo()->inTransaction();
    }
    public function lastInsertId($name = null)
    {
        return $this->getPdo()->lastInsertId($name);
    }
    public function prepare($statement, $driver_options = [])
    {
        return $this->getPdo()->prepare($statement, $driver_options);
    }
    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        return $this->getPdo()->quote($string, $parameter_type);
    }
    public function rollBack()
    {
        return $this->getPdo()->rollBack();
    }
    public function setAttribute($attribute, $value)
    {
        return $this->getPdo()->setAttribute($attribute, $value);
    }
    /**
     * I don't need this, so I'm not putting in the legwork
     * Use runQuery() instead
     */
    public function query($statement, $fetchMode = null, ...$fetchModeArgs)
    {
        throw new BadMethodCallException('Not implemented');
    }
}
