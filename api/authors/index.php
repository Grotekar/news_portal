<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\Author as Author;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$authors = new Author($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($authors->isAdmin() === true) {
            $authors->processinggetRequest();
        }
        break;
    case 'POST':
        if ($authors->isAdmin() === true) {
            $authors->createElement();
        }
        break;
    case 'PUT':
        if ($authors->isAdmin() === true) {
            parse_str(file_get_contents('php://input'), $putParams);
            $authors->updateElement($putParams);
        }
        break;
    case 'DELETE':
        if ($authors->isAdmin() === true) {
            $authors->deleteElement();
        }
        break;
    
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request'
        ]);
        break;
}
echo $authors->getResponse();
