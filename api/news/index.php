<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\News as News;

require_once __DIR__ . '/../../vendor/autoload.php';

$database = new Database();
$pdo = $database->getConnect();

$news = new News($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $response = $news->processingGetRequest();
        break;
    case 'POST':
        $response = $news->createElement();
        break;
    case 'PUT':
        parse_str(file_get_contents('php://input'), $putParams);
        $response = $news->updateElement($putParams);
        break;
    case 'DELETE':
        $response = $news->deleteElement();
        break;
    default:
        $response = [
            'message_error' => 'Разрешенеы запросы только GET, POST, PUT и DELETE'
        ];
        break;
}
print_r($response);
