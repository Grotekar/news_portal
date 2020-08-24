<?php

namespace Models;

use PDO;
use Utils\Logger;
use Dotenv\Dotenv;
use Psr\Log\LoggerInterface;

class Database
{
    private LoggerInterface $logger;
    private PDO $pdo;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function getConnect()
    {
        // Подключение конфигурационного файла
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        // Подключение к базе данных
        try {
            $this->pdo = new \PDO(
                'mysql:host=' . $_SERVER['DB_HOST'] . $_SERVER['DB_PORT']
                . ';dbname=' . $_SERVER['DB_DATABASE'] . ';charset=utf8',
                $_SERVER['DB_USERNAME'],
                $_SERVER['DB_PASSWORD']
            );
        } catch (PDOException $e) {
            $this->logger->exception(
                'Проблема с подключением к базе данных. Сообщение от PDO: {error_msg}.',
                ['error_msg' => $e->getMessage()]
            );
            die();
        }

        return $this->pdo;
    }
}
