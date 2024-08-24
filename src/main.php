<?php

require_once("./QueryBuilder.php");
$config = require_once("./config.php");

try {
    $builder = new QueryBuilder($config);
    // do some query
} catch (\PDOException $pdoException) {
    echo sprintf("Connection to database failed. Reason: %s", $pdoException->getMessage());
    die;
} catch (\InvalidValues $invalidValuesException) {
    echo sprintf("Invalid data for query. Reason: %s", $invalidValuesException->getMessage());
    die;
}
