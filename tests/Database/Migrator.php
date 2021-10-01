<?php

namespace Tests\Database;

use PDO;

class Migrator
{
    public $pdo;

    public function __invoke($database)
    {
        $pdo = $this->createConnection($database);
        $this->createTables($pdo);
        return function () use ($pdo) {
            return $pdo;
        };
    }

    protected function createTables($pdo)
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            email TEXT(255) NOT NULL);');
        $pdo->exec('insert into users(email) values("one@example.com")');
        $pdo->exec('insert into users(email) values("two@example.com")');
    }

    protected function createConnection($database)
    {
        return new PDO(
            $database,
            'root',
            'root',
            [
                // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 1,
            ]
        );
    }
}
