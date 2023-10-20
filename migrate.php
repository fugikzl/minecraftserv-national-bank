<?php

use App\Database\Migration;

require_once __DIR__ . "/vendor/autoload.php";

$pdo = new PDO("sqlite:".__DIR__."/database/db.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA foreign_keys = ON;");

$migrations[] = new Migration("users", $pdo, [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT", 
    "username" => "TEXT|NOT NULL|UNIQUE", 
    "password" => "TEXT|NOT NULL", 
    "balance" => "INTEGER|DEFAULT 0",
    "is_mayer" => "INTEGER|DEFAULT 0"
]);

$migrations[] = new Migration("transactions", $pdo, [
    "id" => "INTEGER PRIMARY KEY AUTOINCREMENT", 
    "sender_id" => "INTEGER|NOT NULL REFERENCES users(id)", 
    "receiver_id" => "INTEGER|NOT NULL REFERENCES users(id)", 
    "summ" => "INTEGER|NOT NULL"
]);

try {
    foreach($migrations as $migration){
        $migration->migrate();
    }
} catch(PDOException $e) {
    die("ERROR: Could not able to execute " . $e->getMessage());
}