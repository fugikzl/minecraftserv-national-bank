<?php

use App\Core\Container\Container;
use App\Core\Router\Router;
use App\Database\Database;

require_once __DIR__ . "/vendor/autoload.php";

$container = new Container();
$container->setSingletone(Database::class, function(){
    return Database::getInstance();
});

$router = new Router($container);

$router->setNamespace("\App\Controllers");
$router->get("/register/{username}/{password}", "MainController@register");
$router->get("/user-info/{username}/{password}", "MainController@userInfo");
$router->get("/receivments/{count}/{username}/{password}", "MainController@receivments");
$router->get("/spendings/{count}/{username}/{password}", "MainController@spendings");
$router->get("/change-pass/{username}/{password}/{newpassword}", "MainController@changePassword");
$router->get("/send/{targetUsername}/{amount}/{username}/{password}", "MainController@sendMoney");
$router->get("/user-info/{username}/{password}", "MainController@userInfo");
$router->get("/generate/{amount}/{username}/{password}", "MainController@generateMoney");

$router->get("/patents", "MainController@getPatents");
$router->get("/patents-by-user/{username}", "MainController@getUserPatents");
$router->get("/create-patent/{name}/{summ}/{username}/{password}", "MainController@createPatent");
$router->get("/buy-patent/{name}/{username}/{password}", "MainController@buyPatent");





// -------mayor PART
// $router->get("/user-info/{targerUsername}/{username}/{password}", function(string $targetUsername, string $username, string $password){

//     $user = ensureUser($username, $password, $db);
//     if(!$user){
//         echo json_encode([
//             "success" => false,
//             "message" => "Invalid credentials",
//             "data" => []
//         ]);
//         exit(400);
//     }

//     if(!$user["is_mayer"]){
//         echo json_encode([
//             "success" => false,
//             "message" => "user is not mayer",
//             "data" => []
//         ]);
//         exit(401);
//     }

//     $targetUser = $db->select("users", "username = '$targetUsername' LIMIT 1");
//     if(!isset($targetUser[0])){
//         echo json_encode([
//             "success" => false,
//             "message" => "Invalid target",
//             "data" => []
//         ]);
//         exit(400);
//     }

//     echo json_encode([
//         "success" => true,
//         "message" => "User info",
//         "data" => [
//             "username" => $targetUser[0]["username"],
//             "balance" => $targetUser[0]["balance"],
//             "is_mayer" => $targetUser[0]["is_mayer"]
//         ]
//     ]);
//     exit(200);
// });
// $router->get("/user-info/{targerUsername}/{username}/{password}", "MainController@userInfo");




$router->run();