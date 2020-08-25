<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\User as User;

require_once __DIR__ . '/../../vendor/autoload.php';

// необходимые HTTP-заголовки 
$database = new Database();
$pdo = $database->getConnect();

$users = new User($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = $users->getRequest();
        break;
    case 'POST':
        $response = $users->createUser();
        break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $putParam);
        $response = $users->update($putParam);
        break;
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $deleteParam);
        $response = $users->delete($deleteParam);
        break;
    
    default:
        # code...
        break;
}
//echo $_SERVER['REQUEST_METHOD']; 
print_r($response);