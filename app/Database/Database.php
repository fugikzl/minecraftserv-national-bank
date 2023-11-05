<?php

namespace App\Database;

use App\Core\Contracts\Database as ContractsDatabase;
use App\Core\Contracts\Singleton;
use PDO;

final class Database implements Singleton, ContractsDatabase
{
    private static Database $instance;

    public static function getInstance() : static
    {
        if(!isset(static::$instance) || !(static::$instance instanceof static)){
            $pdo = new PDO("sqlite:".__DIR__."/../../database/db.db");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("PRAGMA foreign_keys = ON;");
            $instance = new static($pdo);

            static::$instance = $instance;
        }

        return static::$instance;
    }

    private function __construct(
        private PDO $pdo
    ){}

    public function insert(string $table, array $data) : string|false
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':'.implode(', :', array_keys($data));

        $stmt = $this->pdo->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $condition)
    {
        $fields = '';
        foreach($data as $key => $value){
            $fields .= "$key=:$key,";
        }
        $fields = rtrim($fields, ',');

        $stmt = $this->pdo->prepare("UPDATE $table SET $fields WHERE $condition");

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
    }

    public function delete(string $table, string $condition)
    {
        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE $condition");
        $stmt->execute();
    }

    public function select(string $table, string $condition = null) : array|false
    {
        $query = "SELECT * FROM $table";
        if($condition !== null){
            $query .= " WHERE $condition";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}