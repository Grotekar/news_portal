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
        $categories->processingGetRequest();
        break;
    case 'POST':
        if ($categories->isAdmin() === true) {
            $categories->createElement();
        }
        break;
    case 'PUT':
        if ($categories->isAdmin() === true) {
            if (isset($categories->getParamsRequest()[3]) === true) {
                $categoryId = $categories->getParamsRequest()[3];
                parse_str(file_get_contents('php://input'), $putParams);
                $categories->updateElement($putParams, $categoryId);
            } else {
                $categories->getNotFound();
            }
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
