<?php

namespace App\Controllers;

use App\Database\Database;

class MainController extends BaseController
{
    private function registerUser(string $username, string $password) : int
    {
        $userId = $this->database->insert("users", [
            "username" => $username,
            "password" => hash("sha256", $password),
        ]);

        return (int) $userId;
    }

    private function checkIfUserExistsAndDieIfTrue(string $username)
    {
        $user = $this->database->select("users", "username = '$username' LIMIT 1");
        if(isset($user[0])){
            $this->setStatus(false)->setCode(400)->setMessage("User with this username already exists")->response();
        }
    }

    private function ensureUser(string $username, string $password) : array|false
    {
        $user = $this->database->select("users", "username = '$username' LIMIT 1");
        if(!isset($user[0])){
            return false;
        }

        if($user[0]["password"] !== hash("sha256", $password)){
            return false;
        }

        return $user[0];
    }

    private function getUser(int $userId) : ?array
    {
        $user = $this->database->select("users", "id = $userId LIMIT 1");
        
        if(!isset($user[0])){
            return null;
        }

        return $user[0];
    }

    private function loadTransactionData(array $transactions) : array
    {
        foreach($transactions as &$transaction){
            $sender = $this->getUser((int) $transaction["sender_id"]);
            $senderData = $sender !== null ? $sender["username"] : null;

            $receiver = $this->getUser((int) $transaction["receiver_id"]);
            $receiverData = $receiver !== null ? $receiver["username"] : null;
            
            $transaction['sender'] = $senderData;
            $transaction['receiver'] = $receiverData;
        }

        return $transactions;
    }

    public function __construct(
        private Database $database
    ){}

    public function buyPatent(string $name, string $username, string $password)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }

        $patentUser = $this->database->select("patents_user", "username = '$username' AND patent = '$name'");
        if(isset($patentUser[0])){
            $this->setStatus(false)->setCode(400)->setMessage("User has already patent")->response();
        }

        $patent = $this->database->select("patents", "name = '$name' LIMIT 1");
        
        if(!isset($patent[0])){
            $this->setStatus(false)->setCode(400)->setMessage("patent doesn't exist")->response();
        }

        $patent = $patent[0];

        if((int)$patent["summ"] > $user["balance"]){
            $this->setStatus(false)->setCode(400)->setMessage("Not enough money")->response();
        }

        $this->database->update("users", [
            "balance" => (int)$user["balance"] - (int)$patent["summ"]
        ], "username = '" . $user["username"] ."'");

        $this->database->insert("patents_user", [
            "username" => $username,
            "patent" => $name
        ]);

        $this->setMessage("success")->response();
    }

    public function createPatent(string $name, int $summ, string $username, string $password)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }

        if(!$user["is_mayer"]){
            $this->setStatus(false)->setCode(401)->setMessage("user is not mayer")->response();
        }

        $patent = $this->database->select("patents", "name = '$name' LIMIT 1");
        
        if(isset($patent[0])){
            $this->setStatus(false)->setCode(400)->setMessage("patent exists")->response();
        }

        $patentId = $this->database->insert("patents", [
            "name" => $name,
            "summ" => $summ,
        ]);

        if(!$patentId){
            $this->setStatus(false)->setMessage("something went wrong")->setCode(500)->response();
        }

        $this->setStatus(true)->setMessage("Created")->setCode(201)->response();
    }

    public function getPatents()
    {
        $patents = $this->database->select("patents");

        $this->setStatus(true)->setMessage("patents")->setData($patents)->setCode(200)->response();
    }

    public function getUserPatents(string $username)
    {
        $patents = $this->database->select("patents_user", "username = '$username'");

        $this->setStatus(true)->setMessage("$username patents")->setData($patents)->setCode(200)->response();
    }

    public function register(string $username, string $password)
    {
        $this->checkIfUserExistsAndDieIfTrue($username);
        $userId = $this->registerUser($username, $password);
    
        $this->setStatus(true)->setCode(201)->setMessage("User has been created")->setData([
            "userId" => (int) $userId
        ])->response();
    }

    public function userInfo(string $username, string $password)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }
    
        $this->setStatus(true)->setCode(200)->setData([
            "username" => $user["username"],
            "balance" => $user["balance"],
            "is_mayer" => $user["is_mayer"]
        ])->setMessage("user info")->response();
    }

    public function receivments(int $count, string $username, string $password)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }
    
        $receiverId = $user["id"];
        $transactions = $this->database->select("transactions", "receiver_id = '$receiverId' ORDER BY id DESC LIMIT $count");
        $transactions = $this->loadTransactionData($transactions);

        $this->setData($transactions)->setMessage("History of last $count receivments")->response();
    }

    public function spendings(int $count, string $username, string $password)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }

        $spenderId = $user["id"];
        $transactions = $this->database->select("transactions", "sender_id = '$spenderId' ORDER BY id DESC LIMIT $count");
        $transactions = $this->loadTransactionData($transactions);
        
        $this->setData($transactions)->setMessage("History of last $count spendings")->response();
    }

    public function changePassword(string $username, string $password, string $newpassword)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }

        $this->database->update("users", [
            "password" => hash("sha256", $newpassword)
        ], "username = '" . $user["username"] ."'");
    
        $this->setStatus(true)->setCode(200)->setData([
            "username" => $user["username"],
            "balance" => $user["balance"],
            "is_mayer" => $user["is_mayer"]
        ])->setMessage("user info")->response();
    }

    public function generateMoney(int $amount, string $username, string $password)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }

        if(!$user["is_mayer"]){
            $this->setStatus(false)->setCode(401)->setMessage("user is not mayer")->response();
        }

        $this->database->update("users", [
            "balance" => (int)$user["balance"] + (int)$amount
        ], "username = '" . $user["username"] ."'");
    
        $this->setMessage("User info")->setData([
            "username" => $user["username"],
            "balance" => (int)$user["balance"] + (int)$amount,
            "is_mayer" => $user["is_mayer"]
        ])->response();
    }

    public function sendMoney(string $targetUsername, int $amount, string $username, string $password)
    {
        $user = $this->ensureUser($username, $password);
        if(!$user){
            $this->setStatus(false)->setCode(400)->setMessage("Invalid credentials")->response();
        }

        if($amount > $user["balance"]){
            echo json_encode([
                "success" => false,
                "message" => "Not enough money",
                "data" => []
            ]);
            exit(400);
        }
    
        $targetUser = $this->database->select("users", "username = '$targetUsername' LIMIT 1");
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
    
        $this->database->insert("transactions", [
            "sender_id" => $user["id"],
            "receiver_id" => $targetUser[0]["id"],
            "summ" => (int)$amount
        ]);
    
        $this->database->update("users", [
            "balance" => (int)$user["balance"] - (int)$amount
        ], "username = '" . $user["username"] ."'");
    
        $this->database->update("users", [
            "balance" => (int)$targetUser[0]["balance"] + (int)$amount
        ], "username = '" . $targetUser[0]["username"] ."'");
    
        $this->setData([
            "amount" => $amount,
            "receiver" => $targetUser[0]["username"]
        ])->setMessage("sended")->setCode(201)->response();
    }
}