<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\Category as Category;

require_once __DIR__ . '/../../vendor/autoload.php';

$database = new Database();
$pdo = $database->getConnect();

$categories = new Category($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = $categories->processingGetRequest();
        break;
    case 'POST':
        $response = $categories->createElement();
        break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $putParams);
        $response = $categories->updateElement($putParams);
        break;
    case 'DELETE':
        $response = $categories->deleteElement();
        break;
    
    default:
        $response = ['message_error' => 'Разрешенеы запросы только GET, POST, PUT и DELETE'];
        break;
}
print_r($response);
