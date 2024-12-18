<?php

class ORM
{
  protected $connection;
  protected $table;
  protected $conditions = [];
  protected $joins = [];
  protected $columns = '*';
  protected $orderBy = '';


  public function __construct()
  {
    global $conn;
    $this->connection = $conn;
  }

  public function table($table)
  {
    $this->table = $table;
    return $this;
  }

  public function get()
  {
    $sql = "SELECT {$this->columns} FROM {$this->table}" . $this->buildJoins() . $this->buildConditions() . $this->buildOrderBy();

    $result = $this->connection->query($sql);

    if ($result) {
      $data = [];
      while ($row = $result->fetch_assoc()) {
        $data[] = $row;
      }
      return $data;
    }
    return [];
  }

  public function select(...$columns)
  {
    $this->columns = implode(', ', $columns);
    return $this;
  }

  public function innetJoin($table, $firstColumn, $operator, $secondColumn)
  {
    $this->joins[] = "INNER JOIN $table ON $firstColumn $operator $secondColumn";
    return $this;
  }

  public function leftJoin($table, $firstColumn, $operator, $secondColumn)
  {
    $this->joins[] = "LEFT JOIN $table ON $firstColumn $operator $secondColumn";
    return $this;
  }

  protected function buildJoins()
  {
    return count($this->joins) ? ' ' . implode(' ', $this->joins) : '';
  }

  public function where($column, $operator, $value = null, $boolean = 'AND')
  {
    global $conexao;

    if ($value === null) {
      $value = $operator;
      $operator = '=';
    }

    $escapedValue = $conexao->real_escape_string($value);

    $this->conditions[] = [
      'column' => $column,
      'operator' => $operator,
      'value' => $escapedValue,
      'boolean' => $boolean,
    ];

    return $this;
  }


  protected function buildConditions()
  {
    if (!count($this->conditions)) {
      return '';
    }

    $query = ' WHERE ';
    foreach ($this->conditions as $index => $condition) {
      if ($index > 0) {
        $query .= " {$condition['boolean']} ";
      }
      $query .= "{$condition['column']} {$condition['operator']} '{$condition['value']}'";
    }

    return $query;
  }


  public function insert(array $data)
  {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_map(function ($value) {
      return "'{$this->connection->real_escape_string($value)}'";
    }, $data));

    $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
    return $this->connection->query($sql);
  }


  public function update(array $data)
  {
    $set = implode(', ', array_map(function ($key, $value) {
      $escapedValue = $this->connection->real_escape_string($value);
      return "$key = '$escapedValue'";
    }, array_keys($data), $data));

    $sql = "UPDATE {$this->table} SET $set" . $this->buildConditions();
    return $this->connection->query($sql);
  }

  public function delete()
  {
    $sql = "DELETE FROM {$this->table}" . $this->buildConditions();
    return $this->connection->query($sql);
  }

  public function orderBy($column, $direction = 'ASC')
  {
    $this->orderBy = " ORDER BY $column $direction";
    return $this;
  }

  protected function buildOrderBy()
  {
    return $this->orderBy;
  }

}
