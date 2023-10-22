<?php

use App\Database\Database;
use Bramus\Router\Router;

require_once __DIR__ . "/vendor/autoload.php";

$pdo = new PDO("sqlite:".__DIR__."/database/db.db");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA foreign_keys = ON;");
$db = new Database($pdo);

$router = new \Bramus\Router\Router();

function ensureUser(string $username, string $password, Database $db) : array|false
{
    $user = $db->select("users", "username = '$username' LIMIT 1");
    if(!isset($user[0])){
        return false;
    }

    if($user[0]["password"] !== hash("sha256", $password)){
        return false;
    }

    return $user[0];
}

$router->get("/register/{username}/{password}", function(string $username, string $password) use ($db){
    $user = $db->select("users", "username = '$username' LIMIT 1");
    if(isset($user[0])){
        echo json_encode([
            "success" => false,
            "message" => "User with this username already exists",
            "data" => []
        ]);
        exit(400);
    }

    $user = $db->insert("users", [
        "username" => $username,
        "password" => hash("sha256", $password),
    ]);

    echo json_encode([
        "success" => true,
        "message" => "User has been created",
        "data" => [
            "userId" => (int) $user
        ]
    ]);
    exit(201);
});

$router->get("/user-info/{username}/{password}", function(string $username, string $password) use ($db){
    $user = ensureUser($username, $password, $db);
    if(!$user){
        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials",
            "data" => []
        ]);
        exit(400);
    }

    echo json_encode([
        "success" => true,
        "message" => "User info",
        "data" => [
            "username" => $user["username"],
            "balance" => $user["balance"],
            "is_mayer" => $user["is_mayer"]
        ]
    ]);
    exit(200);
});

$router->get("/receivments/{count}/{username}/{password}", function(int $count, string $username, string $password) use ($db){
    $user = ensureUser($username, $password, $db);
    if(!$user){
        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials",
            "data" => []
        ]);
        exit(400);
    }

    $receiverId = $user["id"];
    $transactions = $db->select("transactions", "receiver_id = '$receiverId' ORDER BY id DESC LIMIT $count");
    echo json_encode([
        "success" => true,
        "message" => "History of last $count receivments",
        "data" => $transactions
    ]);
    exit(200);
});

$router->get("/spendings/{count}/{username}/{password}", function(int $count, string $username, string $password) use ($db){
    $user = ensureUser($username, $password, $db);
    if(!$user){
        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials",
            "data" => []
        ]);
        exit(400);
    }

    $spenderId = $user["id"];
    $transactions = $db->select("transactions", "sender_id = '$spenderId' ORDER BY id DESC LIMIT $count");
    echo json_encode([
        "success" => true,
        "message" => "History of last $count spendings",
        "data" => $transactions
    ]);
    exit(200);
});


$router->get("/change-pass/{username}/{password}/{newpassword}", function(string $username, string $password, string $newpassword) use ($db){
    $user = ensureUser($username, $password, $db);
    if(!$user){
        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials",
            "data" => []
        ]);
        exit(400);
    }

    $db->update("users", [
        "password" => hash("sha256", $newpassword)
    ], "username = '" . $user["username"] ."'");

    echo json_encode([
        "success" => true,
        "message" => "User info",
        "data" => [
            "username" => $user["username"],
            "balance" => $user["balance"],
            "is_mayer" => $user["is_mayer"]
        ]
    ]);
    exit(200);
});

$router->get("/send/{targetUsername}/{amount}/{username}/{password}", function(string $targetUsername, int $amount, string $username, string $password) use ($db){
    $user = ensureUser($username, $password, $db);
    if(!$user){
        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials",
            "data" => []
        ]);
        exit(400);
    }

    if($amount > $user["balance"]){
        echo json_encode([
            "success" => false,
            "message" => "Not enough money",
            "data" => []
        ]);
        exit(400);
    }

    $targetUser = $db->select("users", "username = '$targetUsername' LIMIT 1");
    if(!isset($targetUser[0])){
        echo json_encode([
            "success" => false,
            "message" => "Invalid target",
            "data" => []
        ]);
        exit(400);
    }

    if($amount <= 0){
        echo json_encode([
            "success" => false,
            "message" => "Invalid target",
            "data" => []
        ]);
        exit(400);
    }

    $db->insert("transactions", [
        "sender_id" => $user["id"],
        "receiver_id" => $targetUser[0]["id"],
        "summ" => (int)$amount
    ]);

    $db->update("users", [
        "balance" => (int)$user["balance"] - (int)$amount
    ], "username = '" . $user["username"] ."'");

    $db->update("users", [
        "balance" => (int)$targetUser[0]["balance"] + (int)$amount
    ], "username = '" . $targetUser[0]["username"] ."'");

    echo json_encode([
        "success" => true,
        "message" => "Sended",
        "data" => [
            "amount" => $amount,
            "receiver" => $targetUser[0]["username"]
        ]
    ]);
    exit(201);
});

//-------mayor PART
$router->get("/user-info/{targerUsername}/{username}/{password}", function(string $targetUsername, string $username, string $password) use ($db){
    $user = ensureUser($username, $password, $db);
    if(!$user){
        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials",
            "data" => []
        ]);
        exit(400);
    }

    if(!$user["is_mayer"]){
        echo json_encode([
            "success" => false,
            "message" => "user is not mayer",
            "data" => []
        ]);
        exit(401);
    }

    $targetUser = $db->select("users", "username = '$targetUsername' LIMIT 1");
    if(!isset($targetUser[0])){
        echo json_encode([
            "success" => false,
            "message" => "Invalid target",
            "data" => []
        ]);
        exit(400);
    }

    echo json_encode([
        "success" => true,
        "message" => "User info",
        "data" => [
            "username" => $targetUser[0]["username"],
            "balance" => $targetUser[0]["balance"],
            "is_mayer" => $targetUser[0]["is_mayer"]
        ]
    ]);
    exit(200);
});

$router->get("/generate/{amount}/{username}/{password}", function(int $amount, string $username, string $password) use ($db){

    // echo json_encode([
    //     "success" => false,
    //     "message" => "Can't emit more money",
    //     "data" => []
    // ]);
    // exit(400);

    $user = ensureUser($username, $password, $db);
    if(!$user){
        echo json_encode([
            "success" => false,
            "message" => "Invalid credentials",
            "data" => []
        ]);
        exit(400);
    }

    if(!$user["is_mayer"]){
        echo json_encode([
            "success" => false,
            "message" => "user is not mayer",
            "data" => []
        ]);
        exit(401);
    }

    $db->update("users", [
        "balance" => (int)$user["balance"] + (int)$amount
    ], "username = '" . $user["username"] ."'");

    echo json_encode([
        "success" => true,
        "message" => "User info",
        "data" => [
            "username" => $user["username"],
            "balance" => (int)$user["balance"] + (int)$amount,
            "is_mayer" => $user["is_mayer"]
        ]
    ]);
    exit(200);
});


$router->run();