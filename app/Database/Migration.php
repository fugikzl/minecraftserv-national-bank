<?php

namespace App\Database;
use PDO;

class Migration
{
    public function __construct(
        private string $table, 
        private PDO $pdo, 
        private array $fields,
        private array $foreignKeys = []
    ){}

    public function migrate()
    {
        if(!$this->checkIfTableExists()){
            $table = $this->table;
            $sql = "CREATE TABLE $table(";
            $fields = $this->fields;
            
            foreach($fields as $column=>$description){
                $sql = $sql."$column ".implode(" ",explode("|",$description)).",";
            }

            foreach ($this->foreignKeys as $column => $relation) {
                $sql .= "FOREIGN KEY($column) REFERENCES $relation,";
            }

            $sql = substr($sql,0,-1).")";
            try {
                $this->pdo->exec($sql);
                
            } catch (\Throwable $th) {
                echo("\nThe query:\n$sql\ncreated error");
                echo("\n".$th->getMessage());
                die();
            }
            echo("\nTable $table created");
        }  
    }

    private function checkIfTableExists() : bool
    {
        $table = $this->table;
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='$table';";
        $result = $this->pdo->query($sql);
        
        if ($result->fetchColumn()) {
            echo("\nWARNING table $table exists");
            return true;
        } else {
            return false;
        }
    }
}