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
        if ($news->isAuthor() === true) {
            $news->createElement();
        }
        break;
    case 'PUT':
        if ($news->isAuthor() === true) {
            parse_str(file_get_contents('php://input'), $putParams);
            $news->updateElement($putParams);
        }
        break;
    case 'DELETE':
        if ($news->isAuthor() === true) {
            $news->deleteElement();
        }
        break;
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request'
        ]);
        break;
}
echo $news->getResponse();
