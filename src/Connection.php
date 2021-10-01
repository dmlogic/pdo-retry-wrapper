<?php

namespace Dmlogic\PdoRetryWrapper;

use PDO;
use Closure;
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

    public function __construct($connector = null, $exceptionCallback = null)
    {
        $this->connector = $connector;
        $this->exceptionCallback = $exceptionCallback;
    }

    public function reconnect(): void
    {
        if (!is_callable($this->connector)) {
            throw new LogicException('No database connection defined');
        }
        $this->pdo = call_user_func($this->connector);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function performQuery(string $sql, array $bindings = null)
    {
        $this->reconnectIfMissingConnection();
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute($bindings);
            return $query;
        } catch (\Throwable $e) {
            if ($this->causedByLostConnection($e)) {
                $connectionException = new ConnectionException($e, $this->currentAttempts, $sql, $bindings);
                if ($this->exceptionCallback) {
                    call_user_func($this->exceptionCallback, $connectionException);
                }
                throw $connectionException;
            }
            throw $e;
        }
    }

    protected function reconnectIfMissingConnection(): void
    {
        if (is_null($this->pdo)) {
            $this->reconnect();
        }
    }
}
