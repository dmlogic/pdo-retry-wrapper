<?php

namespace Tests\Database;

use PDO;

class Migrator
{
    public $pdo;

    public function __invoke($database)
    {
        $this->createConnection($database);
        $this->createTables();
        return $this->pdo;
    }

    protected function createTables()
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            email TEXT(255) NOT NULL);');
        $this->pdo->exec('insert into users(email) values("one@example.com")');
        $this->pdo->exec('insert into users(email) values("two@example.com")');
    }

    protected function createConnection($database)
    {
        $this->pdo = new PDO(
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
