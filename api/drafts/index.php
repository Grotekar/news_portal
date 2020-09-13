<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\Draft as Draft;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$drafts = new Draft($pdo);
$id = $drafts->getParamsRequest()[3];

$response = [];
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($drafts->isAuthor() === true) {
            $drafts->processingGetRequest();
        }
        break;
    case 'POST':
        $drafts->processingPostRequest();
        break;
    case 'PUT':
        if ($drafts->isAccessAllowed() === true) {
            parse_str(file_get_contents('php://input'), $putParams);
            $drafts->updateElement($putParams, $id);
        }
        break;
    case 'DELETE':
        if ($drafts->isAccessAllowed() === true) {
            $drafts->processingDeleteRequest();
        }
        break;
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request'
        ]);
        break;
}
echo $drafts->getResponse();
