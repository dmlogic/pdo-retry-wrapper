<?php

namespace Dmlogic\PdoRetryWrapper;

use Exception;
use Throwable;

class ConnectionException extends Exception
{
    private Throwable $originalException;
    private int $attempts;
    private ?string $query;
    private ?array $bindings;

    public function __construct(Throwable $original, int $attempts, $query = null, $bindings = null)
    {
        $this->message = $original->getMessage();
        $this->code = 0;
        $this->originalException = $original;
        $this->attempts = $attempts;
        $this->query = $query;
        $this->bindings = $bindings;
    }

    public function getAttempts()
    {
        return $this->attempts;
    }

    public function getOriginalException()
    {
        return $this->originalException;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getBindings()
    {
        return $this->bindings;
    }
}
