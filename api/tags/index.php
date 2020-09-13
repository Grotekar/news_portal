<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\Tag as Tag;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$tags = new Tag($pdo);

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $tags->processingGetRequest();
        break;
    case 'POST':
        if ($tags->isAdmin() === true) {
            $tags->createElement();
        }
        break;
    case 'PUT':
        if ($tags->isAdmin() === true) {
            if (isset($tags->getParamsRequest()[3]) === true) {
                $tagId = $tags->getParamsRequest()[3];
                parse_str(file_get_contents('php://input'), $putParams);
                $tags->updateElement($putParams, $tagId);
            } else {
                $tags->getNotFound();
            }
        }
        break;
    case 'DELETE':
        if ($tags->isAdmin() === true) {
            $tags->deleteElement();
        }
        break;
    
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request'
        ]);
        break;
}
echo $tags->getResponse();
