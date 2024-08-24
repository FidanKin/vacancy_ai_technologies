<?php

require_once("./Exceptions.php");

class QueryBuilder
{
    const ORDER_DESC = 'DESC';
    const ORDER_ASC = 'ASC';
    private \PDO $connection;
    private string $query = '';

    private array $bindings = [];
    private string $tablePrefix = '';
    private bool $returnRows = false;
    public function __construct(array $config)
    {
        $this->connect($config);
        $this->tablePrefix = $config['db_prefix'];
    }

    private function connect(array $config): void
    {
        $dsn = "{$config['db_type']}:dbname={$config['db_name']};host={$config['host']};charset=UTF8";
        $options = [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,

        ];
        $this->connection = new PDO($dsn, $config['user'], $config['password'], $options);
    }

    public function select(array $columns = ['*']): self
    {
        $this->query .= 'SELECT ' . implode(',', $columns) . ' ';
        $this->returnRows = true;
        return $this;
    }

    public function update($table, array $values): self
    {
        $sets = '';
        $this->bindings = [];

        foreach ($values as $column => $value)
        {
            if (! is_scalar($value)) {
                continue;
            }
            $sets .= $column . ' = ?,';
            $this->bindings[] = $value;
        }

        $this->query .= 'UPDATE ' . $this->getTableNameWithPrefix($table) . ' SET ' . trim($sets, ',');
        return $this;
    }

    public function delete(): self
    {
        $this->query .= ' DELETE ';
        return $this;
    }

    public function insert(string $table, $values): self
    {
        $bindings = array_values($values);
        if (! $this->isValuesScalar(array_values($bindings))) {
            throw new InvalidValuesForQuery("Value must be scalar !!!");
        }
        $columns = ' (' . implode(',', array_keys($values)) . ')';
        $placeholders = ' (' . implode(',', array_fill(0, count($values), '?')) . ') ';
        $this->query .= ' INSERT INTO ' . $this->getTableNameWithPrefix($table) . $columns . ' VALUES ' . $placeholders;
        $this->bindings = $bindings;

        return $this;

    }

    public function execute(): mixed
    {
        $stmt = $this->connection->prepare($this->query);
        $result = $stmt->execute($this->bindings);
        if ($this->returnRows) {
            $result = $stmt->fetchAll();
        }

        return $result;
    }

    public function where(string $param, string $operator, $value): self
    {
        $this->query .= ' where ' . $param . ' ' . $operator . ' ? ';
        $this->bindings[] = $value;
        return $this;
    }

    public function orderBy(string $column, string $type = 'DESC'): self
    {
        if (! in_array($type, [static::ORDER_DESC, static::ORDER_ASC])) {
            throw new InvalidValuesForQuery("Invalid order type - {$type}");
        }
        $this->query .= " ORDER BY {$column} {$type} ";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query .= " LIMIT {$limit} ";
        return $this;
    }

    public function from(string $tableName): self
    {
        $this->query .= ' FROM ' . $this->getTableNameWithPrefix($tableName) . ' ';
        return $this;
    }

    private function getTableNameWithPrefix(string $table): string
    {
        return $this->tablePrefix . $table;
    }

    private function isValuesScalar($values): bool
    {
        $scalar = true;

        foreach ($values as $value) {
            if (! is_scalar($value)) {
                $scalar = false;
                break;
            }
        }

        return $scalar;
    }

}