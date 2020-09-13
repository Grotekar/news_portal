<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\User as User;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$users = new User($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $users->processingGetRequest();
        break;
    case 'POST':
        $users->createElement();
        break;
    case 'PUT':
        if ($users->isAccessAllowed() === true) {
            $userId = $users->getParamsRequest()[3];
            parse_str(file_get_contents('php://input'), $putParams);
            $users->updateElement($putParams, $userId);
        }
        break;
    case 'DELETE':
        if ($users->isAdmin() === true) {
            $users->deleteElement();
        }
        break;
    
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request'
        ]);
        break;
}
echo $users->getResponse();
