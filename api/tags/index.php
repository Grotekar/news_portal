<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\Tag as Tag;

require_once __DIR__ . '/../../vendor/autoload.php';

$database = new Database();
$pdo = $database->getConnect();

$tags = new Tag($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = $tags->getRequest();
        break;
    case 'POST':
        $response = $tags->createElement();
        break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $putParams);
        $response = $tags->updateElement($putParams);
        break;
    case 'DELETE':
        $response = $tags->deleteElement();
        break;
    
    default:
        $response = ['message_error' => 'Разрешенеы запросы только GET, POST, PUT и DELETE'];
        break;
}
print_r($response);
