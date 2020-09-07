<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\Category as Category;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$categories = new Category($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = $categories->processingGetRequest();
        break;
    case 'POST':
        if ($categories->isAdmin() === true) {
            $categories->createElement();
        }
        break;
    case 'PUT':
        if ($categories->isAdmin() === true) {
            parse_str(file_get_contents('php://input'), $putParams);
            $categories->updateElement($putParams);
        }
        break;
    case 'DELETE':
        if ($categories->isAdmin() === true) {
            $categories->deleteElement();
        }
        break;
    
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request'
        ]);
        break;
}
echo $categories->getResponse();
