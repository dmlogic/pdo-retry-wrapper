<?php

namespace Dmlogic\PdoRetryWrapper;

use PDO;
use Closure;
use Throwable;
use PDOStatement;
use LogicException;
use Illuminate\Database\DetectsLostConnections;

class Connection
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

    public function runQuery(string $sql, ?array $bindings = null): PDOStatement
    {
        $this->reconnectIfMissingConnection();
        try {
            $query = $this->prepareAndExecuteQuery($sql, $bindings);
        } catch (Throwable $e) {
            if (!$this->causedByLostConnection($e)) {
                throw $e;
            }
            $query = $this->retryQuery($sql, $bindings);
        }
        return $query;
    }

    private function retryQuery(string $sql, ?array $bindings = null): PDOStatement
    {
        $this->currentAttempts = 1;
        while ($this->currentAttempts < $this->maxAttempts) {
            try {
                $query = $this->prepareAndExecuteQuery($sql, $bindings);
                return $query;
            } catch (Throwable $e) {
                $this->currentAttempts ++;
                if (!$this->causedByLostConnection($e)) {
                    throw $e;
                }
            }
        }
        return $this->throwConnectionException($e, $sql, $bindings);
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

    private function prepareAndExecuteQuery(string $sql, ?array $bindings): PDOStatement
    {
        $query = $this->pdo->prepare($sql);
        $query->execute($bindings);
        return $query;
    }

    private function reconnectIfMissingConnection(): void
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }

    private function reconnect(): void
    {
        if (!is_callable($this->connector)) {
            throw new LogicException('No database connection defined');
        }
        $this->pdo = call_user_func($this->connector);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
