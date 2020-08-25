<?php

namespace Models;

require_once __DIR__ . '/../vendor/autoload.php';

$argv = $_SERVER['argv'];

if (in_array('--migrate', $argv) === true || in_array('-m', $argv) === true) {
    $database = new Database();
    $pdo = $database->getConnect();

    $migration = new Migration();
    $migration->migrate($pdo);
} elseif (in_array('--help', $argv) === true || in_array('-h', $argv) === true) {
    echo "Доступные аргументы:\n"
            . "--help, -h\t Справка\n"
            . "--migrate, -m\t Выполнить миграцию базы данных.\n";
} else {
    echo "Невозможно выполнить без правильного аргумента. Введите аргумент --help, либо -h для справки.";
}
