<?php

require_once("./QueryBuilder.php");

$config = require_once("./config.php");

try {
    $builder = new QueryBuilder($config);
//    $result = $builder->insert('builder', ['name' => 'new1', 'qara' => 'asdas', 'type' => 'sup'])->execute();
//    $result = $builder->select()->from('builder')->orderBy('id', QueryBuilder::ORDER_DESC)->limit(3)->execute();
//    $result = $builder->delete()->from('builder')->where('id', '=', 1)->execute();
//    $result = $builder->update('builder', ['name' => 'updated1', 'qara' => 'updated2'])->where('id', '=', 6)->execute();
//    var_dump($result);
} catch (\PDOException $pdoException) {
    echo sprintf("Connection to database failed. Reason: %s", $pdoException->getMessage());
    die;
} catch (\InvalidValues $invalidValuesException) {
    echo sprintf("Invalid data for query. Reason: %s", $invalidValuesException->getMessage());
    die;
}
