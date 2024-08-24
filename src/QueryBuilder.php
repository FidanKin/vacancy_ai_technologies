<?php

require_once("./Exceptions.php");

class QueryBuilder
{
    const ORDER_DESC = 'DESC';
    const ORDER_ASC = 'ASC';

    private \PDO $connection;
    // строка запроса
    private string $query = '';

    private array $bindings = [];
    private string $tablePrefix = '';
    private bool $returnRows = false;
    public function __construct(array $config)
    {
        $this->connect($config);
        $this->tablePrefix = $config['db_prefix'];
    }

    public function select(array $columns = ['*']): self
    {
        $this->query .= ' SELECT ' . implode(',', $columns) . ' ';
        $this->returnRows = true;
        return $this;
    }

    /**
     * @param       $table - название таблицы
     * @param array $values - массив вида: колонка => значение.
     *
     * @return $this
     */
    public function update(string $table, array $values): self
    {
        if (! $this->isValuesScalar(array_values($values))) {
            throw new InvalidValuesForQuery("Value must be scalar !!!");
        }

        $sets = implode(' = ?,', array_keys($values)) . ' = ? ';

        $this->bindings = array_values($values);

        $this->query .= ' UPDATE ' . $this->getTableNameWithPrefix($table) . ' SET ' . $sets;
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

    /**
     * Выполнить запрос и вернуть результат
     * Если необходимо вернуть результат запроса SELECT, то возвращаем все строки
     * В остальных случаях возвращает bool: true - запрос выполнени успешно, false - неуспешно
     *
     * @return mixed
     */
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

    public function from(string $table): self
    {
        $this->query .= ' FROM ' . $this->getTableNameWithPrefix($table) . ' ';
        return $this;
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

    private function getTableNameWithPrefix(string $table): string
    {
        return $this->tablePrefix . $table;
    }

    /**
     * Проверяем, что в переданном массиве только скалярные значения
     *
     * @param $values - массив значений
     *
     * @return bool
     */
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
