# PHP PDO retry wrapper

I wish I didn't have to make this. But RDS under AWS is not nearly as available as the metrics claim.

This is inspired by the dropped connection retry mechanisms built into illuminate/database. I want the same here but at a much lower level for PDO.

Provides this functionality:

* Retry queries that failed due to connection issues up to a maximum number of attempts
* Define a callback to run if the limit is reached
* Combines `$pdo->prepare($sql)` and `$pdo->execute($bindings)` into a single `$connection->runQuery($sql, $bindings)` call

Usage

```php
// Connector is a Closure for simpler reconnects
$pdoConnector = function() {
    return new PDO(
            'dsnString',
            'username',
            'password',
            [
                PDO::OPTION_NAME => VALUE,
                ...
            ]
        );
};

// Optional exception callback can be anything at all
// It's a handy place to centralise the error handling
// logic if you don't want queries inside try/catch
$callback = function(ConnectionException $exception) {
    $exception->getAttempts();
    $exception->getOriginalException();
    $exception->getQuery();
    $exception->getBindings();
}

// Create the wrapper
$dbConnection = new Connection($pdoConnector, $callback);

// Generate a PDOStatement with results
try {
    $query = $dbConnection->runQuery('select * from users where id = ?', [123]);
    $user = $query->fetch(PDO::FETCH_OBJ);
} catch(ConnectionException $e) {
    // We know we failed on connection
    // $callback has been invoked at this stage
} catch(Exception $e) {
    // Something else went down
}
```
