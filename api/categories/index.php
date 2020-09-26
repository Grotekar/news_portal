<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\Category as Category;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$categories = new Category($pdo);

$categories->processingRequest();

echo $categories->getResponse();
