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


$tags->processingRequest();

echo $tags->getResponse();
