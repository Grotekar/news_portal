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


$authors->processingRequest();

echo $authors->getResponse();
