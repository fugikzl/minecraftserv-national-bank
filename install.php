<?php

use App\Database\Database;

if(!isset($argv[1]) || !isset($argv[2])){
    echo("No mayer name or password is called");
    die();
}
require_once __DIR__ . "/migrate.php";

$db = new Database($pdo);
$user = $db->insert("users", [
    "username" => $argv[1],
    "password" => hash("sha256", $argv[2]),
    "is_mayer" => 1
]);
