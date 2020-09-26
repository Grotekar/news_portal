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

$news->processingRequest();

echo $news->getResponse();
