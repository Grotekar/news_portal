<?php

namespace Models;

require __DIR__ . '/../vendor/autoload.php';

$database = new Database();
$pdo = $database->getConnect();
$migrations = [];

foreach (glob(__DIR__ . "/*.sql") as $filename) {
    $migrations[] = basename($filename);
}

$i = 1;
$countMigrations = count(migrations);
$countSuccess = 0;
$countBad = 0;

foreach ($migrations as $key => $filename) {
        echo "[$i/$countMigrations] : $filename.\n";
    $i++;

    $query = file_get_contents(__DIR__ . '/' . $filename);
    $statment = $pdo->prepare($query);
    $isSuccess = $statment->execute();

    if ($isSuccess === false) {
        echo "Не удалось выполнить миграцию $filename.\n";
        $countBad++;
    } else {
        // Фиксация выполненной миграции в одноименной таблице
        $countSuccess++;
    }
}

echo "Успешно $countSuccess/$countMigrations, с ошибками $countBad/$countMigrations.";
