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

$drafts->processingRequest();

echo $drafts->getResponse();
