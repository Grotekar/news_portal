<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\User as User;

require_once __DIR__ . '/../../vendor/autoload.php';

$database = new Database();
$pdo = $database->getConnect();

$users = new User($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = $users->processinggetRequest();
        break;
    case 'POST':
        $response = $users->createElement();
        break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $putParams);
        $response = $users->updateElement($putParams);
        break;
    case 'DELETE':
        $response = $users->deleteElement();
        break;
    
    default:
        $response = ['message_error' => 'Разрешенеы запросы только GET, POST, PUT и DELETE'];
        break;
}
print_r($response);
