<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\News as News;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$news = new News($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $news->processingGetRequest();
        break;
    case 'POST':
        $news->processingPostRequest();
        break;
    case 'DELETE':
        $news->processingDeleteRequest();
        break;
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request'
        ]);
        break;
}
echo $news->getResponse();
