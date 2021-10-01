# PHP PDO retry wrapper

I wish I didn't have to make this. But RDS under AWS is not nearly as available as the metrics claim.

This is inspired by the dropped connection retry mechanisms built into illuminate/database. I want the same here but at a much lower level for PDO.

Requirements:

* Define a maximum number of attempts in any given action
* Define an exception to be thrown if maximum attempts reached
* Attempt to perform connections or queries up to the connection limit
* Throw the defined exception if threshold exceeded
* Provide an compatible interface to PDO and PDOStatement to allow drop-in replacement

So should be able to do:

```
$pdo->exec($sql);
```

As well as

```
$query = $pdo->prepare($sql);
$query->execute([$bind, $ings]);
$query->setFetchMode($to);
$query->fetchAll()
```


This in addition to magic __calls on all the underlying functionality
