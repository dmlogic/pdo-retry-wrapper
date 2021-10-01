<?php

namespace Dmlogic\PdoRetryWrapper;

use PDO;
use Illuminate\Database\DetectsLostConnections;

class Connection
{
    use DetectsLostConnections;

    private PDO $pdo;
    private int $maxAttempts = 3;
    private int $currentAttempts = 1;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo) {
            $this->setPdo($pdo);
        }
    }

    public function setPdo(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo = $pdo;
    }

    public function performQuery(string $sql, array $bindings = null)
    {
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute($bindings);
            return $query;
        } catch (\Throwable $e) {
            if ($this->causedByLostConnection($e)) {
                throw new ConnectionException($e, $this->currentAttempts, $sql, $bindings);
            }
            throw $e;
        }
    }
}
