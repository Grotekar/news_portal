<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Author extends AbstractTable
{
    protected PDO $pdo;
    protected string $tableName = 'authors';
    protected LoggerInterface $logger;
    
    /**
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->logger = new Logger();
        $this->pdo = $pdo;
    }

    /**
     * Подготовка данных перед передачей
     *
     * @return void
     */
    public function processingGetRequest(): void
    {
        // Получение данных
        $this->isGetRequestSuccess();
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $query = "SELECT u.user_id,
                        u.firstname,
                        u.lastname,
                        u.avatar,
                        u.created,
                        u.is_admin,
                        a.description
                FROM users u
                JOIN authors a
                    ON u.user_id = a.user_id";
        $this->statment = $this->pdo->prepare($query);
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос для получения элемента
     *
     * @return bool
     */
    public function isGetElementComplited(int $id): bool
    {
        $query = "SELECT u.user_id,
                        u.firstname,
                        u.lastname,
                        u.avatar,
                        u.created,
                        u.is_admin,
                        a.description
                FROM users u
                JOIN authors a
                    ON u.user_id = a.user_id
                WHERE a.user_id = (:user_id)";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(":user_id", $id);
        
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * @param array $params - параметры запроса
     *
     * @return bool
     */
    protected function isExistsParamsInArray(array $params): bool
    {
        if (
            array_key_exists('user_id', $params) &&
            array_key_exists('description', $params)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Запрос на создание элемента
     *
     * @param array $postParams
     *
     * @return bool
     */
    public function isCreateElementCompleted(array $postParams): bool
    {
        $query = "INSERT INTO authors (user_id, description) 
                VALUES (:user_id, :description)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':description', $postParams['description']);

        $status = $this->statment->execute();

        return $status;
    }
    
    /**
     * Запрос для обновления элемента
     *
     * @param int $id
     * @param array $putParams - параметры запроса
     *
     * @return bool
     */
    public function isUpdateElementCompleted(int $id, array $putParams): bool
    {
        $query = "UPDATE authors SET
                description = :description WHERE user_id = :user_id";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(':description', $putParams['description']);
        $this->statment->bindParam(':user_id', $id);
        
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return bool
     */
    public function isDeleteteElementCompleted(int $id): bool
    {
        $query = "DELETE FROM authors WHERE user_id = :user_id";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':user_id', $id);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 13);
    }
}
