<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Tag extends AbstractTable
{
    protected PDO $pdo;
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
     * Подготовка данных перед выдачей
     *
     * @return void
     */
    public function processingGetRequest(): void
    {
        $this->isGetRequestSuccess();
    }
    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $query = "SELECT * FROM tags";
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
        $query = "SELECT * FROM tags WHERE tag_id=(:tag_id)";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(":tag_id", $id);
        
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
        if (array_key_exists('name', $params)) {
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
        $query = "INSERT INTO tags (name) 
                VALUES (:name)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':name', $postParams['name']);

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
        $query = "UPDATE tags SET
            name = :name
            WHERE tag_id = :tag_id";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':name', $putParams['name']);
        $this->statment->bindParam(':tag_id', $id);

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
        $query = "DELETE FROM tags WHERE tag_id = :tag_id";
        $statment = $this->pdo->prepare($query);
        
        $statment->bindParam(':tag_id', $id);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 10);
    }
}
