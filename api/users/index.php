<?php

namespace Models;
namespace Api;

use Models\Database as Database;
use Api\User as User;

require_once __DIR__ . '/../../vendor/autoload.php';

header("Content-Type: application/json; charset=utf-8");

$database = new Database();
$pdo = $database->getConnect();

$users = new User($pdo);

$users->processingRequest();

echo $users->getResponse();
